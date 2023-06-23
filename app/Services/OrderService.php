<?php

namespace App\Services;

use App\Events\OrderProcessedEvent;
use App\Models\EasyLunch;
use App\Models\EasyLunchSubscribers;
use App\Models\Errand;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\User;
use App\Models\whatsapp\Utils;
use Barryvdh\DomPDF\Facade\Pdf;
use DateTime;
use Illuminate\Support\Facades\Storage;
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

            // dd('');
        $this->addTransaction($user, $order, "OTHER ITEMS");

        }
        return $order;
    }

    public function addEasyLunchOrderItems(EasyLunch $easyLunch, Order $order) {
        
        $items = $easyLunch->items;

        foreach ($items as $item) {
            $this->addOrderedItem($order, $item->id, $item->vendor->id);
        }
    }

    public function addOrderedItem(Order $order, string $itemId, $vendorId, $easylunchRequest = false)
    {
        $item = Item::where(["vendor_id" => $vendorId, 'id' => $itemId])->first();

        //Check if this order is for easy lunch request
        // Easy lunch request must not contain more than one item
        if ($easylunchRequest and count($order->orderedItems) == 1) {
            return $order;
        }

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
          
            $order->total_amount += $item->item_price;
            $order->save();

            // dd($currentAmount, $order->total_amount);

            $transactionService = new TransactionService();
            $transactionService->updateTransaction($order->transaction, ['amount' => $order->total_amount, 'description' => $order->description]);

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

            $transactionService = new TransactionService();
            $transactionService->updateTransaction($order->transaction, ['status' => Utils::ORDER_STATUS_CANCELLED]);
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

    public function performCheckout($orderId, $walletId, $paymentMethod)
    {
        $order = Order::find($orderId);
        $user = $order->user;
        $isEasylunchPackageSub = substr($order->description, 0, 18) === "Easy lunch package";

        $errand = Errand::where('order_id', $orderId)->first();

        if ((($errand and ($errand->status == Utils::ORDER_STATUS_INITIATED)) or !$errand)  and $order) {

            //if this is an easy lunch order
            if ($walletId === Utils::PAYMENT_METHOD_EASY_LUNCH) {

                $easylunchsub = EasyLunchSubscribers::where('user_id', $user->id)->first();
                $easylunchsub->orders_remaining -= 1;
                $easylunchsub->last_used = date("Y-m-d");
                $easylunchsub->last_order = $order->order_id;
                $easylunchsub->save();

                $order->status = Utils::ORDER_STATUS_PROCESSING;
                $transactionService = new TransactionService();
                $transactionService->updateTransaction($order->transaction, ['status' => Utils::TRANSACTION_STATUS_SUCCESS, 'method' => $paymentMethod]);            


            } 
            
            elseif (in_array($walletId, [
                Utils::PAYMENT_METHOD_ON_DELIVERY,
                Utils::PAYMENT_METHOD_ONLINE,
                Utils::PAYMENT_METHOD_TRANSFER
            ])) {

                //Create a new Errand
                if ($errand) {

                    $errand->update([
                        'destination_phone' => $user->phone,
                        'dispatcher' => "",
                        'status' => Utils::ORDER_STATUS_INITIATED,
                    ]);
                } 
                
                else {
                    $errand = Errand::create([
                        'destination_phone' => $user->phone,
                        'dispatcher' => "",
                        'delivery_address' => "",
                        'status' => Utils::ORDER_STATUS_INITIATED,
                        'order_id' => $order->id
                    ]);
                }

                $order->status = Utils::ORDER_STATUS_PROCESSING;
                $paymentMethod = $walletId;

                $transactionService = new TransactionService();
                $transactionService->updateTransaction($order->transaction, ['status' => Utils::TRANSACTION_STATUS_PENDING, 'method' => $paymentMethod]);
            } 
            
            else {

                $walletService = new WalletService();

                $wallet = $walletService->getWallet($user);
                $fundsAvailable = $walletService->isFundsAvailable($user, $order->total_amount);

                if ($fundsAvailable) {

                    $walletService->alterBalance($order->total_amount, $wallet, false);

                    $order->status = Utils::ORDER_STATUS_PROCESSING;

                    //check for easy lunch subscription
                    if ($isEasylunchPackageSub) {

                        $easylunchsub = EasyLunchSubscribers::where(['user_id' => $order->user->id, 'paid' => false])->whereNull('last_used')->orderByDesc('id')->first();
                        $easylunchsub->paid = true;
                        $easylunchsub->save();

                        $order->status = Utils::ORDER_STATUS_DELIVERED;
                    }

                    elseif ($errand) {

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

                    $transactionService = new TransactionService();
                    $transactionService->updateTransaction($order->transaction, ['status' => Utils::TRANSACTION_STATUS_SUCCESS, 'method' => $paymentMethod]);            

                }
            }

        }

        $order->save();

        //Attach invoice
        $orderInvoice = $order->transaction->orderInvoice;
        $orderInvoice->update(["url" => self::getOrderInvoice($order)]);
        $orderInvoice->save();

        return  $order;
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
                $errand->delivery_fee = $fee;
                $errand->status = Utils::ORDER_STATUS_ENROUTE;
                $errand->save();

                //create event for user order placed
                event(new OrderProcessedEvent($order, $dispatcher, $fee, $order->user->phone));

            }

            //check for easy lunch subscription
            elseif (substr($order->description, 0, 18) === "Easy lunch package") {
                $easylunchsub = EasyLunchSubscribers::where('user_id', $order->user->id)->first();
                $easylunchsub->paid = true;
                $easylunchsub->save();
            }

            //Update order transaction status
            $transactionService = new TransactionService();
            $transactionService->updateTransaction($order->transaction, ['status' => $order->status]);

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
        } 
        
        else {

            $order = Order::create([
                'order_id' => strtoupper(Random::generate(35)),
                'description' => $description,
                'total_amount' => $amount,
                'status' => Utils::ORDER_STATUS_INITIATED,
                'user_id' => $user->id
            ]);
        }

        $this->addTransaction($user, $order, "EASY LUNCH SUBSCRIPTION");

        return $order;
    }

    public function addTransaction(User $user, Order $order, $type) {
         //Create transaction for this service
         $transactionService = new TransactionService();
         $transactionService->addTransaction([
             'amount' => $order->total_amount,
             'transaction_reference' => "trans-".Random::generate(64),
             'date' => now(),
             'description' => $order->description,
             'status' => $order->status,
             'user_id' => $user->id,
             'order_id' => $order->id
         ], $type);
    }

    public static function getOrderInvoice(Order $order)
    {
        $basePath = env('AWS_BUCKET_BASE_PATH') . "/order/invoice/";
        
        $transactionStatus = strtoupper(Utils::TRANSACTION_STATUS[$order->transaction->status]);
        $orderStatus = Utils::ORDER_STATUS_MESSAGE[$order->status];
        
        $fileName = "invoice-$order->order_id.pdf";

        $orderedItems = $order->orderedItems;
        
        $del = Storage::disk(env('STORAGE_LOCATION'))->delete("/easybuy4me/order/invoice/$fileName");

        $pdf = Pdf::loadView('components.order-invoice', compact(['order', 'transactionStatus', 'orderStatus', 'orderedItems']));
        $pdf->save($basePath . $fileName, env('STORAGE_LOCATION'));

        return "https://" . env('AWS_BUCKET') . ".s3.amazonaws.com/$basePath" . $fileName;
    }

    public function createEasylunchOrder(User $user, $easyLunchId) {

        $order = Order::create([
            'order_id' => strtoupper(Random::generate(35)),
            'description' => "",
            'total_amount' => 0.0,
            'status' => Utils::ORDER_STATUS_INITIATED,
            'user_id' => $user->id
        ]);

        $easyLunch = EasyLunch::find($easyLunchId);
        $orderedItems = [];

        foreach ($easyLunch->items as $item) {

            $orderedItem = OrderedItem::create([
                'item_id' => $item->id, 
                'quantity' => 1,
                'order_id' => $order->id
            ]); 
            
            array_push($orderedItems, $orderedItem);
        }

        $description = "Easylunch purchase ($easyLunch->name)";
        $totalAmount = collect($orderedItems)->reduce(function ($initial, $i) {
            return $initial + $i->item->item_price;
        }, 0);

        $order->description = $description;
        $order->total_amount = $totalAmount;

        $order->save();

        $this->addTransaction($user, $order, "EASY LUNCH PURCHASE");

        return $order;
    }
}
