<?php

namespace App\Services;

use App\Events\OrderProcessedEvent;
use App\Models\EasyLunchSubscribers;
use App\Models\Errand;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\User;
use App\Models\Wallet;
use App\Models\whatsapp\Utils;
use DateTime;
use Nette\Utils\Random;

class OrderService
{

    public function findUserCurrentOrder(User $user, $description = false, $amount = false)
    {

        $order = $this->getUserPendingOrder($user);

        if (!$order) {

            $userCurrentOrders = $user->orders->filter(function ($order) {
                return $order->status === Utils::ORDER_STATUS_INITIATED;
            })->all();

            foreach ($userCurrentOrders as $o) {
                $o->status = Utils::ORDER_STATUS_CANCELLED;
                $o->save();
            }

            $order = Order::create([
                'order_id' => strtoupper(Random::generate(35)),
                'description' => $description ? $description : "",
                'total_amount' => $amount ? $amount : 0.0,
                'status' => Utils::ORDER_STATUS_INITIATED,
                'user_id' => $user->id
            ]);

            return $order;
        }

        return $order;
    }

    public function addOrderedItem(Order $order, string $itemId, $vendorId)
    {
        $item = Item::where(['id' => $itemId, 'vendor_id' => $vendorId])->first();

        if ($item) {

            //Update orderedItem quantity if it exist
            $thisOrderedItem = $order->orderedItems->filter(function ($orderedItem) use ($item) {
                return $orderedItem->item_id == $item->id;
            })->first();

            if ($thisOrderedItem) {

                $thisOrderedItem->quantity = $thisOrderedItem->quantity + 1;
                $thisOrderedItem->save();
            } else {
                $thisOrderedItem = OrderedItem::create([
                    'item_id' => $item->id,
                    'quantity' => 1,
                    'order_id' => $order->id
                ]);
            }

            //Build order description
            $description = "";

            foreach (OrderedItem::where('order_id', $order->id)->get() as $orI) {
                $i = Item::find($orI->item_id);
                $description = $description . "$i->item_name - N$i->item_price per $i->unit_name ($orI->quantity$i->unit_name)\n";
            }

            $order->description = $description;
            $order->total_amount = $order->total_amount + ($item->item_price * $thisOrderedItem->quantity);

            $order->save();

            return $order;
        }
    }

    public function getPreviousOrder(User $user)
    {
        $userOrder = $user->orders->filter(function ($order) {
            return $order->status == Utils::ORDER_STATUS_INITIATED;
        })->sortBy(function ($order) {
            return $order->created_at;
        })
            ->first();

        return $userOrder;
    }

    public function cancelOrder($orderId)
    {
        $order = Order::find($orderId);
        if ($order) {
            $order->status = Utils::ORDER_STATUS_CANCELLED;
            $order->save();
        }
    }

    public function clearCart(User $user)
    {
        $order = $user->orders->filter(function ($order) {
            return $order->status == Utils::ORDER_STATUS_INITIATED;
        })
            ->first();

        return Order::destroy($order->id);
    }

    public function performCheckout($orderId, $walletId)
    {
        $order = Order::find($orderId);
        $user = $order->user;

        $errand = Errand::where('order_id', $orderId)->first();

        if ((($errand and ($errand->status == Utils::ORDER_STATUS_INITIATED)) or !$errand)  and $order) {

            if ($walletId == "easylunch") {

                $easylunchsub = EasyLunchSubscribers::where('user_id', $user->id)->first();
                $easylunchsub->orders_remaining -= 1;
                $easylunchsub->last_used = date("Y-m-d");
                $easylunchsub->last_order = $order->order_id;
                $easylunchsub->save();

                $errand = Errand::create([
                    'destination_phone' => $user->phone,
                    'dispatcher' => "",
                    'status' => Utils::ORDER_STATUS_INITIATED,
                    'order_id' => $order->id
                ]);

                $order->status = Utils::ORDER_STATUS_PROCESSING;
                $order->save();

                return $errand;
                
            } elseif (in_array($walletId, ['delivery', 'transfer', 'online'])) {

                //Create a new Errand

                if ($errand) {

                    $errand->update([
                        'destination_phone' => $user->phone,
                        'dispatcher' => "",
                        'status' => Utils::ORDER_STATUS_INITIATED,
                    ]);
                } else {
                    $errand = Errand::create([
                        'destination_phone' => $user->phone,
                        'dispatcher' => "",
                        'status' => Utils::ORDER_STATUS_INITIATED,
                        'order_id' => $order->id
                    ]);
                }


                $order->status = Utils::ORDER_STATUS_PROCESSING;
                $order->save();

                return $errand;

            } else {

                $walletService = new WalletService();

                $wallet = $walletService->getWallet($user);
                $fundsAvailable = $walletService->isFundsAvailable($user, $order->total_amount);

                if ($fundsAvailable) {

                    $walletService->alterBalance($order->total_amount, $wallet, false);

                    $order->status = Utils::ORDER_STATUS_PROCESSING;
                    $order->save();

                    //check for easy lunch subscription
                    if (substr($order->description, 0, 18) === "Easy lunch package") {

                        $easylunchsub = EasyLunchSubscribers::where('user_id', $order->user->id)->first();
                        $easylunchsub->paid = true;
                        $easylunchsub->save();

                        $order->status = Utils::ORDER_STATUS_DELIVERED;
                        $order->save();
                    }

                    if ($errand) {

                        $errand->update([
                            'destination_phone' => $user->phone,
                            'dispatcher' => "",
                            'status' => Utils::ORDER_STATUS_INITIATED,
                        ]);
                    } else {
                        $errand = Errand::create([
                            'destination_phone' => $user->phone,
                            'dispatcher' => "",
                            'status' => Utils::ORDER_STATUS_INITIATED,
                            'order_id' => $order->id
                        ]);
                    }

                    return $errand;
                }
            }

            return false;
        }

        return $errand;
    }

    public function processOrder($orderId, $dispatcher, $fee)
    {

        $order = Order::where('order_id', $orderId)->first();

        if ($order) {

            $order->status = Utils::ORDER_STATUS_ENROUTE;
            $order->save();

            //check for errand
            $errand = Errand::where('order_id', $order->id)->first();

            if ($errand and $errand->status == Utils::ORDER_STATUS_INITIATED) {
                $errand->dispatcher = $dispatcher;
                $errand->status = Utils::ORDER_STATUS_ENROUTE;
                $errand->save();
            }

            //check for easy lunch subscription
            if (substr($order->description, 0, 18) === "Easy lunch package") {
                $easylunchsub = EasyLunchSubscribers::where('user_id', $order->user->id)->first();
                $easylunchsub->paid = true;
                $easylunchsub->save();
            }

            //create event for user order placed
            event(new OrderProcessedEvent($order, $dispatcher, $fee, $order->user->phone));
            return $order;
        }

        return false;
    }

    public static function orderSummary(Order $order)
    {

        $orderSummary = "";

        foreach (OrderedItem::where('order_id', $order->id)->get() as $orI) {
            $i = Item::find($orI->item_id);
            $orderSummary = $orderSummary . "$i->item_name - N$i->item_price per $i->unit_name ($orI->quantity$i->unit_name)\n";
        }

        return strlen($orderSummary) > 1 ? $orderSummary . "\nTotal Amount: *$order->total_amount*\n\n" : $order->description;
    }

    public function getUserPendingOrder($user)
    {

        return $user->orders->filter(function ($order) {

            $expiryTime = new DateTime($order->created_at);
            $expiryTime->modify("+1 hour");
            $now = new DateTime(now());

            return ($order->status == Utils::ORDER_STATUS_INITIATED) and
                $expiryTime > $now;
        })->first();
    }

    public function getUserPendingOrders($user, $status = [Utils::ORDER_STATUS_INITIATED])
    {

        return $user->orders->filter(function ($order) use ($status) {

            $expiryTime = new DateTime($order->created_at);
            $expiryTime->modify("+1 hour");
            $now = new DateTime(now());

            return (in_array($order->status, $status)) and
                ($expiryTime > $now or ($order->status === Utils::ORDER_STATUS_ENROUTE or $order->status === Utils::ORDER_STATUS_PROCESSING));
        })->all();
    }

    public function findEasyLunchOrder(User $user)
    {

        //Check if this user has a subscription that is not yet paid for
        $easylunchsub = EasyLunchSubscribers::where(["user_id" => $user->id, 'paid' => false])->first();

        if ($easylunchsub) {

            $order = Order::where('user_id', $user->id)
                ->where('description', 'LIKE', "Easy lunch package%")
                ->first();

            if ($order) {

                $expiryTime = new DateTime($order->created_at);
                $expiryTime->modify("+1 hour");
                $now = new DateTime(now());

                if ($now < $expiryTime) {
                    return $order;
                }
            }
        }

        return false;
    }

    public function addEasyLunchOrder(User $user, $description, $amount): Order
    {

        $order = Order::where('user_id', $user->id)
            ->where('description', 'LIKE', "Easy lunch package%")
            ->first();

        if ($order) {

            $order->description = $description;
            $order->total_amount = $amount;
            $order->save();
        } else {

            $order = Order::create([
                'order_id' => strtoupper(Random::generate(35)),
                'description' => $description,
                'total_amount' => $amount,
                'status' => Utils::ORDER_STATUS_INITIATED,
                'user_id' => $user->id
            ]);
        }

        return $order;
    }
}
