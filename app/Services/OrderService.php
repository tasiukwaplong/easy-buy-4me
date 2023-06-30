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

    public function performCheckout($orderId, $walletId, $paymentMethod) {
        //Todo: 1. Check if this order is for easylunch subscription package

        $order = Order::find($orderId);
        $user = $order->user;
        $errand = Errand::where('order_id', $orderId)->first();
        $isEasylunchPackageSub = EasyLunchService::isEasyLunchSub($order);
        

        if ((($errand and ($errand->status == Utils::ORDER_STATUS_INITIATED)) or !$errand ) and $order) {
                 
                 //Todo: 2. Check for payment method
                 //Todo: 2.1. Check for easy lunch payment method
             if ($walletId === Utils::PAYMENT_METHOD_EASY_LUNCH) {
                $order = EasyLunchService::useEasyLunchSub($user, $order, $paymentMethod);
            } 

                //Todo: 2.2 Check for online, on delivery and transfer methods
            elseif (in_array($walletId, [
                Utils::PAYMENT_METHOD_ON_DELIVERY,
                Utils::PAYMENT_METHOD_ONLINE,
                Utils::PAYMENT_METHOD_TRANSFER
            ])) {

                $order->status = Utils::ORDER_STATUS_PROCESSING;
                $paymentMethod = $walletId;

                $transactionService = new TransactionService();
                $transactionService->updateTransaction($order->transaction, ['status' => Utils::TRANSACTION_STATUS_PENDING, 'method' => $paymentMethod]);
            }
            else {

                //Check if user has the funds

                $walletService = new WalletService();

                $fundsAvailable = $walletService->isFundsAvailable($user, $order->total_amount);

                if ($fundsAvailable) {

                    $order->status = Utils::ORDER_STATUS_PROCESSING;

                    //check for easy lunch subscription
                    if ($isEasylunchPackageSub) {

                        $walletService->alterBalance($order->total_amount, $user->wallet, false);

                        $easylunchsub = EasyLunchSubscribers::where(['user_id' => $order->user->id, 'paid' => false])->whereNull('last_used')->orderByDesc('id')->first();
                        $easylunchsub->paid = true;
                        $easylunchsub->save();

                        $order->status = Utils::ORDER_STATUS_DELIVERED;

                        
                        $transactionService = new TransactionService();
                        $transactionService->updateTransaction($order->transaction, ['status' => Utils::TRANSACTION_STATUS_SUCCESS, 'method' => $paymentMethod]);            

                        $order->transaction->orderInvoice->url = OrderService::getOrderInvoice($order);
                        $order->transaction->orderInvoice->save();
                    }
                    else {
                        $transactionService = new TransactionService();
                        $transactionService->updateTransaction($order->transaction, ['status' => Utils::TRANSACTION_STATUS_PENDING, 'method' => $paymentMethod]);            
                    }

                }

            }

            $order->save();

            //Attach invoice
            $orderInvoice = $order->transaction->orderInvoice;
            $orderInvoice->save();
    
            return  $order;

        }
    
        //Todo: 2.3 Check for wallet payment 

    }

    public function orderAccepted(Order $order, $totalAmount) {

        $order->status = Utils::ORDER_STATUS_ENROUTE;
        $orderTransaction = $order->transaction;

        $walletService = new WalletService();
        $transactionService = new TransactionService();

        if($orderTransaction->method === Utils::PAYMENT_METHOD_WALLET) {

            $walletService->alterBalance($totalAmount, $order->user->wallet, false);
            $transactionService->updateTransaction($orderTransaction, array('status' => Utils::TRANSACTION_STATUS_SUCCESS));
            
        }
        else {
            $transactionService->updateTransaction($orderTransaction, array('status' => Utils::TRANSACTION_STATUS_PENDING));
        }

        $orderInvoice = $orderTransaction->orderInvoice;
        $orderInvoice->url = self::getOrderInvoice($order);

        $orderInvoice->save();
        $order->save();

        return $order;

    }

    public function performCheckoutOld($orderId, $walletId, $paymentMethod)
    {
        $order = Order::find($orderId);
        $user = $order->user;
        $isEasylunchPackageSub = substr($order->description, 0, 18) === "Easy lunch package";

        $errand = Errand::where('order_id', $orderId)->first();

        if ((($errand and ($errand->status == Utils::ORDER_STATUS_INITIATED)) or !$errand ) and $order) {

            //if this is an easy lunch order
            if ($walletId === Utils::PAYMENT_METHOD_EASY_LUNCH) {
                $order = EasyLunchService::useEasyLunchSub($user, $order, $paymentMethod);
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

    public function processOrder($orderId, $dispatcher)
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

                //create event for user order enroute
                event(new OrderProcessedEvent($order, $dispatcher, $errand->delivery_fee, $order->user->phone));
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

        return strlen($orderSummary) > 1 ? $orderSummary . "\nOrder Total: *$order->total_amount*\n\n" : $order->description;
    }

    public static function orderItems(Order $order)
    {
        $orderSummary = "";

        foreach (OrderedItem::where('order_id', $order->id)->get() as $orI) {
            $i = Item::find($orI->item_id);
            $vendor = $i->vendor->name;
            $orderSummary .= "$i->item_name ($vendor) - $orI->quantity$i->unit_name\n";
        }

        return "$orderSummary";
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
        $errand = $order->errand;
        $paymentMethod = $order->transaction->method;
        
        $fileName = date_timestamp_get(now()) . ".pdf";

        $orderedItems = $order->orderedItems;
        
        $del = Storage::disk(env('STORAGE_LOCATION'))->delete("/easybuy4me/order/invoice/$fileName");

        $pdf = Pdf::loadView('components.order-invoice', compact(['order', 'transactionStatus', 'orderStatus', 'orderedItems', 'errand', 'paymentMethod']));
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
