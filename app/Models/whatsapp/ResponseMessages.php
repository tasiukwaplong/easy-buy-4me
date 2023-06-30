<?php

namespace App\Models\whatsapp;

use App\Models\DataPlan;
use App\Models\EasyLunch;
use App\Models\EasyLunchSubscribers;
use App\Models\Errand;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Models\whatsapp\messages\InteractiveSendMessage;
use App\Models\whatsapp\messages\partials\BodyText;
use App\Models\whatsapp\messages\partials\interactive\Action;
use App\Models\whatsapp\messages\partials\interactive\Header;
use App\Models\whatsapp\messages\partials\interactive\Interactive;
use App\Models\whatsapp\messages\partials\interactive\Row;
use App\Models\whatsapp\messages\partials\interactive\Section;
use App\Models\whatsapp\messages\SendMessage;
use App\Models\whatsapp\messages\TextSendMessage;
use App\Services\EasyLunchService;
use App\Services\ErrandService;
use App\Services\OrderService;
use App\Services\UserService;
use Illuminate\Support\Str;

class ResponseMessages
{
    public static function notifyAdminUserOrderAccepted($order) {
        
        $errand = $order->errand;
        $amount = "PAID";

        $body = "User Order Accepted!\n\n";
        $fee = $errand->delivery_fee;
        $location = $errand->delivery_address;
        $contact = $order->user->phone;

        if($order->transaction->method === Utils::PAYMENT_METHOD_ON_DELIVERY) {
            $amount = $order->total_amount + $fee;
        }

        $body = "From *CR*\n\n";
        $body .= OrderService::orderItems($order);
        $body .= "\n";
        $body .= "Total payable amount: $amount\nContact: $contact\nLocation: $location\n\n";
        $body .= "Reply *NOTED* to acknowledge reciept of this order";

        
        return self::textMessage($body, UserService::getAdmin()->phone, false);
    }

    public static function orderCancelled($customerPhoneNumber) {
        $body = "Order Cancelled";
        $header = new Header(Utils::TEXT, "Order Status");

        return self::menuOptions($customerPhoneNumber, $header, $body);
    }

    public static function orderConfirmSent($customerPhoneNumber) {
        return self::textMessage('Confirmation Sent', $customerPhoneNumber, false);
    }

    public static function askUserToConfirmOrder(Order $order, $fee, $address) {

        $orderSummary = OrderService::orderSummary($order);
        $totalAmount = ($order->errand) ? $order->total_amount + $order->errand->delivery_fee : $order->total_amount; 

        $body = "Please confirm the order below\n\n$orderSummary";
        $body .= "Delivery Address: $address\nDelivery Fee: $fee\n\n*Total Amount: $totalAmount*\n\nDo you confirm this order?";

        $header = new Header(Utils::TEXT, "Confirm Order");

        $action = ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[buttons-order-user-confirm-yes:$order->id]",
                    "title" => "YES, CONFIRM"
                ]
                ],
                [
                    "type" => Utils::REPLY,
                    "reply" => [
                        "id" => "[buttons-order-user-confirm-no:$order->id]",
                        "title" => "NO, CANCEL"
                    ]
                ]
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);

        $interactiveSendMessage = new InteractiveSendMessage($order->user->phone, Utils::INTERACTIVE, $interactive);
        return $interactiveSendMessage;

    }

    public static function thanksForService($customerPhoneNumber) {
        return self::textMessage('Thanks for being part of the team', $customerPhoneNumber, false);
    }

    public static function thanksForPatronage($customerPhoneNumber, $order) {

        $header = ["type" => "document", "document" => ["link" => $order->transaction->orderInvoice->url]];
        $body = "Thanks for believing in me. I hope to run errands for you next time";

        return self::menuOptions($customerPhoneNumber, $header, $body);
    }

    public static function disptcherAcknoledged($user, $order) {

        $header = new Header(Utils::TEXT, "Acknoledgment");
        $body = "Acknoledgement sent\nORDER ID:$order->order_id\n\nKindly tap *DELIVERED* when delivery is completed";

        $action = ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[buttons:order-delivered:$order->id]",
                    "title" => "DELIVERED"
                ]
            ]
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);

        $interactiveSendMessage = new InteractiveSendMessage($user->phone, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function noPendingOrder($customerPhoneNumber) {

        $header = new Header(Utils::TEXT, "Error!");
        $body = "Sorry, you do not have a pending order";

        return self::menuOptions($customerPhoneNumber, $header, $body);
    }

    public static function easylunchUsed($customerPhoneNumber, EasyLunch $easyLunch) {

            $body = "Sorry, \nYou have used your Easylunch ($easyLunch->name) subscription for today.\nThe items includes:\n\n$easyLunch->description\n\nThank you.";
            $header = "Easylunch Service";

            return self::menuOptions($customerPhoneNumber, $header, $body);
    }

    public static function viewEasyLunchSubscriptions(User $user, $subscriptions) {

        $bodyContent = "Tap *SUBSCRIPTIONS* to view your Easylunch Packages.";
        $allSections = EasyLunchService::getEasylunches($subscriptions);
         //Build Action
         $action = new Action("SUBSCRIPTIONS", $allSections);

         //Build a footer
         $footer = ['text' => Utils::EASY_BUY_4_ME_FOOTER];
 
         $body = ['text' => $bodyContent];
 
         $header = new Header(Utils::TEXT, "EASY LUNCH SERVICE");
 
         $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);
 
         $interactiveSendMessage = new InteractiveSendMessage($user->phone, Utils::INTERACTIVE, $interactive);
 
         return $interactiveSendMessage;
    }

    public static function orderOnTheWayNotifyerAdmin($order) {
        $admin = UserService::getAdmin();
        return self::textMessage("Order with ID: $order->order_id NOTED!", $admin->phone, false);
    }

    public static function orderOnTheWayNotifyer($order)
    {

        $body = "";

        $errand = Errand::where('order_id', $order->id)->first();

        $orderSummary = OrderService::orderSummary($order);
        $body = "*Order Dispatched*\n\nYour order with the following details has been dispatched and is on the way\n\n$orderSummary\nDelivery fee: $errand->delivery_fee\nDispatcher Phone:$errand->dispatcher\n\nTap *RECEIVED* when you recieve this order";

        $header = ["type" => "document", "document" => ["link" => $order->transaction->orderInvoice->url]];

        $action = ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[buttons-order-recieved:$order->id]",
                    "title" => "RECEIVED"
                ]
            ]
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);

        $interactiveSendMessage = new InteractiveSendMessage($order->user->phone, Utils::INTERACTIVE, $interactive);
        return $interactiveSendMessage;
    }

    public static function messageDispatcher($order, $fee, $location, $contact, $dispatcher) {

        $body = "From *CR*\n\n";
        $body .= OrderService::orderSummary($order);
        $body .= "\n";
        $body .= "Delivery fee: $fee\nContact: $contact\nLocation: $location\n\n";
        $body .= "Tap *NOTED* to acknowledge reciept of this order";

        $header = new Header(Utils::TEXT, "New Order!");

        $action = ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[dispatcher-confirm:noted:$order->id]",
                    "title" => "NOTED"
                ]
            ],
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);
        $interactiveSendMessage = new InteractiveSendMessage($dispatcher->phone, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function confirmDeliveryAddress($customerPhoneNumber, $order, $address, $easylunchRequest = false) {

        $body = "Your delivery address is *$address*.\n\nIs this correct?";
        $header = new Header(Utils::TEXT, "Confirm Address");

        $action = ['buttons' => ($easylunchRequest) ? array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[address-confirm:yes:$order->id:el]",
                    "title" => "YES, CONTINUE"
                ]
            ],
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[address-confirm:no:$order->id:el]",
                    "title" => "CHANGE ADDRESS"
                ]
            ],
        ) :

        array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[address-confirm:yes:$order->id]",
                    "title" => "YES, CONTINUE"
                ]
            ],
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[address-confirm:no:$order->id]",
                    "title" => "CHANGE ADDRESS"
                ]
            ],
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);
        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function enterOrderDeliveryAddress($customerPhoneNumber, $easylunchRequest = false) {

        $body = "Please enter a delivery address in the following format\n\n";
        $message = ($easylunchRequest) ? "*DELIVERY ADDRESS EASYLUNCH [address]*\n\nE.g DELIVERY ADDRESS EASYLUNCH Room 13, Block 4, MYB Lodge, Gandu" : "*DELIVERY ADDRESS [address]*\n\nE.g DELIVERY ADDRESS Room 13, Block 4, MYB Lodge, Gandu";
        return self::textMessage("$body$message", $customerPhoneNumber, false);
    }

    public static function showContactAdmin($customerPhoneNumber) {
        $admin = UserService::getAdmin();
        $body = "Chat with an admin by clicking https://wa.me/$admin->phone";
        
        return self::textMessage($body, $customerPhoneNumber, true);
    }

    public static function orderAlreadyProcessed($customerPhoneNumber, Order $order) {
        $body = "Order with ID: $order->order_id already processed!";
        $header = "Order Status";

        return self::menuOptions($customerPhoneNumber, $header, $body);
    }

    public static function walletLowNotification(User $admin, array $accounts, $currenntBalance, $checkDate, $walletType)
    {
        $body = "Hello Boss, Your $walletType balance is low.\nCurrent Bal.: *$currenntBalance*\nDate: *$checkDate*\n\nFund your wallet via any of the accounts below\n\n";

        foreach ($accounts as $account) {
            $body .= "$account->account\n$account->accountName\n$account->bank\n\n";
        }
        return self::textMessage($body, $admin->phone, false);
    }

    public static function sendDataPurchaseResponse($customerPhoneNumber, DataPlan $dataPlan, $status)
    {
        $body = "";

        $header = new Header(Utils::TEXT, "Data Purchase");

        if ($status === Utils::TRANSACTION_STATUS_SUCCESS) {
            $body .= "Purchase of data plan of $dataPlan->description successful";

        } elseif ($status === Utils::TRANSACTION_STATUS_INSUFFICIENT_BALANCE) {
            $body .= "Purchase of data plan of $dataPlan->description was not successful\nReason: *Insufficient fund*";
        
        } else {
            $body .= "Purchase of data plan of $dataPlan->description was not successful\nReason: *Unknown Error! Please try again*";
        }

        return self::menuOptions($customerPhoneNumber, $header, $body);
    }
    public static function wrongDataPlanEntry($customerPhoneNumber, $entry, $incompleteNumber = false)
    {

        $body = ($incompleteNumber) ? "Destination Number must be 11 digits" : "Oops! Looks like you've mistyped a data plan purchase command.\nThe right command is *NETWORK PLAN NUMBER* (e.g *MTN1GB 09033456789* to purchase MTN 1GB plan for 09033456789)\nYou typed *$entry*. Please try again with the right command or tap any of the buttons below to start again";

        $header = new Header(Utils::TEXT, "Error!");

        return self::menuOptions($customerPhoneNumber, $header, $body);

        
    }

    public static function confirmDataPurchase($customerPhoneNumber, $dataPlan, $destinationPhone)
    {

        $body = "Are you sure you want to send $dataPlan->description to $destinationPhone?";

        $header = new Header(Utils::TEXT, "Purchase Data Plan Confimation");

        $action = ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[data-confirm:$dataPlan->id:$destinationPhone]",
                    "title" => "YES, CONTINUE"
                ]
            ],
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => Utils::BUTTONS_DATA_PLAN_CANCEL,
                    "title" => "CANCEL"
                ]
            ],
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function showNetworkDataPlans($customerPhoneNumber, $dataPlans)
    {
        $body = "To buy data, enter command in the following format [DataCode Phone] e.g: MTN1GB 080xxxxxxx. \nSkip the *Phone* input if you want to purchase for this whatsapp number.\n\n*List of available Data Plans*\n\n";

        // dd($dataPlans->all());
        foreach ($dataPlans as $dataPlan) {

            foreach ($dataPlan as $network_name => $plans) {

                $body .= "*$network_name Data Plans*\n";

                foreach ($plans as $plan) {

                    $body .= "$plan->network_name$plan->name - $plan->description\n";
                }
            }

            $body .= "\n";
        }

        $header = new Header(Utils::TEXT, "Purchase Data Plan");

        return self::menuOptions($customerPhoneNumber, $header, $body);

    }

    public static function showDataNetworks($customerPhoneNumber, $availableNetworks)
    {
        $bodyContent = "Choose your network provider from the *MENU* below";

        //Build all rows 
        $selectionRows = [];

        foreach ($availableNetworks as $network) {
            $row = new Row("[select-network:$network->network_name]", $network->network_name, "Buy $network->network_name data plan");
            array_push($selectionRows, $row);
        }

        //Build section
        $section = new Section("Select Network", $selectionRows);

        //Build Action
        $action = new Action("MENU", array($section));

        //Build a footer
        $footer = ['text' => Utils::EASY_BUY_4_ME_FOOTER];

        $body = ['text' => $bodyContent];

        $header = new Header(Utils::TEXT, "SELECT NETWORK");

        $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function showUserTransactionHistory($customerPhoneNumber, $userTransactionsData)
    {

        $body = $userTransactionsData['transactions'];
        $nextPage = $userTransactionsData['nextPage'];
        $lastPage = $userTransactionsData['lastPage'];

        $header = new Header(Utils::TEXT, "Transaction History");

        if($lastPage) {
            return self::menuOptions($customerPhoneNumber, $header, $body);
        } 
        
        else {

            $action = ['buttons' => array(
                [
                    "type" => Utils::REPLY,
                    "reply" => [
                        "id" => Utils::BUTTONS_GO_TO_DASHBOARD,
                        "title" => "GO TO MENU"
                    ]
                ],
                [
                    "type" => Utils::REPLY,
                    "reply" => [
                        "id" => "[buttons-transaction-history:$nextPage]",
                        "title" => "NEXT PAGE"
                    ]
                ]
            )];

            $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);

            $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

            return $interactiveSendMessage;
        }

        
    }

    public static function dataPurchaseStatus($customerPhoneNumber, $transactionStatusMessage)
    {
        $header = new Header(Utils::TEXT, "Airtime Purchase");
        return self::menuOptions($customerPhoneNumber, $header, $transactionStatusMessage);

    }

    public static function showAirtime($customerPhoneNumber)
    {

        $body = "Kindly enter airtime recharge destination phone number and the amount in the following format\n *PHONE* *AMOUNT*\n E.g 09012345678 100 if you are to recharge 09012345678 with N100 airtime.";
        $header = new Header(Utils::TEXT, "Airtime Purchase");

        return self::menuOptions($customerPhoneNumber, $header, $body);
        
    }

    public static function showFundMyWallet(User $user)
    {

        $body = "Kindly transfer money to the following accounts:\n\n";

        foreach ($user->monnifyAccounts as $account) {
            $body .= "$account->account_number\n$account->account_name\n$account->bank\n\n";
        }

        $body .= "Fund online? click here -> https://easybuy4me.com/fund/?user=$user->phone";

        $header = new Header(Utils::TEXT, "Fund My Account");

        return self::menuOptions($user->phone, $header, $body);

    }

    public static function showOrderStatus($orders, User $user)
    {
        $thisOrders = "These are your current orders\n\n";

        if ($orders) {

            foreach ($orders as $order) {

                $status = "";

                if ($order->status == Utils::ORDER_STATUS_INITIATED) {
                    $status = "Payment Pending";
                } 
                else if ($order->status == Utils::ORDER_STATUS_PROCESSING) {
                    $status = "Processing";
                }
                else if ($order->status == Utils::ORDER_STATUS_ENROUTE) {
                     $status = "On the way!";
                }

                $thisOrders .= Str::replaceLast("\n", "", $order->description);
                $thisOrders .= "\nCreated at $order->created_at\nStatus: *$status*\n\n";
            }

        } else {

            $thisOrders = "Sorry, you do not have any pending order";
        }

        $header = new Header(Utils::TEXT, "ORDER STATUS");
        return self::menuOptions($user->phone, $header, $thisOrders);

    }

    public static function easyLunchSubscribed($customerPhoneNumber, EasyLunchSubscribers $easyLunchSubscriber)
    {
        $body = "You have opted for Easy lunch subscription\n\nKindly tap *PAY NOW* to complete this purchase";
        $header = new Header(Utils::TEXT, "Easylunch Services");

        $action = ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[button-easy-lunch-sub-pay-now:$easyLunchSubscriber->id]",
                    "title" => "PAY NOW"
                ]
            ]
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function easyLunchHome($customerPhoneNumber)
    {

        $easyLunchInfo = EasyLunchService::easylunchHome();

        $allSections = $easyLunchInfo['sections'];
        $bodyContent = $easyLunchInfo['body'];

        //Build Action
        $action = new Action("EASY LUNCH MENU", $allSections);

        //Build a footer
        $footer = ['text' => Utils::EASY_BUY_4_ME_FOOTER];

        $body = ['text' => $bodyContent];

        $header = new Header(Utils::TEXT, "EASY LUNCH SERVICE");

        $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
         
    }

    public static function easyLunchPackages(User $user)
    {

        $easyLunchInfo = EasyLunchService::getEasylunchPackages($user);

        $allSections = $easyLunchInfo['sections'];
        $bodyContent = $easyLunchInfo['body'];

        if(empty($allSections)) {

            $header = "Easylunch Service";
            $body = "Sorry, No more packages available.\n\nTap *MENU*, for other services";

            return self::menuOptions($user->phone, $header, $body);
        } 
        
        else {

            //Build Action
            $action = new Action("PACKAGES", $allSections);

            //Build a footer
            $footer = ['text' => Utils::EASY_BUY_4_ME_FOOTER];

            $body = ['text' => $bodyContent];

            $header = new Header(Utils::TEXT, "EASY LUNCH PACKAGES");

            $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);

            $interactiveSendMessage = new InteractiveSendMessage($user->phone, Utils::INTERACTIVE, $interactive);

            return $interactiveSendMessage;
        }
         
    }
    public static function showMore($customerPhoneNumber)
    {
        $body = "EasyBuy4Me is an innovative errands and logistics company that specializes in providing physical and digital errands services to our customers, leveraging technology and our experienced team to deliver seamless and reliable services.\n\n" .
            "*Website:* https://www.easybuy4me.com\n" .
            "*Facebook:* https://facebook.com/easybuy4me\n" .
            "*Twitter:* https://twitter.com/easybuy4me\n" .
            "*Instagram:* https://www.instagram.com/easybuy4me\n\n" .
            "*Upcoming Features*\n" .
            "_*Subscribe to trends, sports, news*_\n" .
            "_*Pay remitta*_\n\n" .
            "info.easybuy4me@gmail.com | +2349031514346";

        $header = ['type' => Utils::MEDIA_IMAGE, 'image' => ['link' => Utils::ERRAND_BANNAER]];

        $action = ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => Utils::BUTTONS_SUPPORT,
                    "title" => "SUPPORT"
                ]
            ],
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function userWallets(User $user)
    {

        $balance = $user->wallet->balance;
        $monnifyAccounts = "";

        foreach ($user->monnifyAccounts as $account) {
            $monnifyAccounts .= "$account->account_name\n$account->bank\n$account->account_number\n\n";
        }

        $body = "*Total Balance:* $balance\n\nTo fund your wallet, make transfer to any of the accounts below\n\n$monnifyAccounts\n*Fund online? click here* -> https://easybuy4me.com/fund/?user=$user->phone";

        $header = new Header(Utils::TEXT, "My Wallet");

        return self::menuOptions($user->phone, $header, $body);
    }

    public static function userCartCleared($customerPhoneNumber)
    {
        $body = "Your Cart is cleared";
        $header = new Header(Utils::TEXT, "My Cart");

        return self::menuOptions($customerPhoneNumber, $header, $body);
    }

    public static function sendUserCart($customerPhoneNumber, $order, $empty )
    {

        $body = ($empty and !$order) ? "Your Cart is empty" : "Your cart contains\n\n" . OrderService::orderSummary($order);
        $header = new Header(Utils::TEXT, "My Cart");

        if($empty) {

            return self::menuOptions($customerPhoneNumber, $header, $body);
        }
        else {

            $action = ['buttons' => array(
                [
                    "type" => Utils::REPLY,
                    "reply" => [
                        "id" => Utils::BUTTONS_CLEAR_CART,
                        "title" => "Clear Cart"
                    ]
                ],

                [
                    "type" => Utils::REPLY,
                    "reply" => [
                        "id" => "[button-order-checkout:$order->id]",
                        "title" => "CHECKOUT"
                    ]
                ]
            )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;

        }
    }

    public static function userOrderProcessed($customerPhoneNumber, $order, $dispatcher, $fee)
    {

        $body = "Kindly find the details below\n\n" . OrderService::orderSummary($order) . "Customer: $customerPhoneNumber\nService charge: $fee\nOrder ID: $order->order_id\n\nThe dispatch rider ($dispatcher) assigned to you will contact you shortly.";
        $header = new Header(Utils::TEXT, "Your order has been placed");

        return self::menuOptions($customerPhoneNumber, $header, $body);
    }

    public static function adminOrderProcessedSuccess($customerPhoneNumber, $order)
    {

        $orderId = strtoupper($order->order_id);
        $body = $order ? "Order: *$orderId* processed successfully" : "Order: *$orderId* could not be processed. Check if order is still valid";

        return self::textMessage($body, $customerPhoneNumber, false);
    }

    public static function userOrderPlaced(string $customerPhoneNumber, $order, $easylunchRequest = false)
    {
        $errand = Errand::where('order_id', $order->id)->first();
        $body = "";
        $header = "";

        if($easylunchRequest) {
            $body = "Easylunch Subscription added Successfully";
            $header = new Header(Utils::TEXT, 'EasyLunch Subscrition');
        }

        elseif ($order->status == Utils::ORDER_STATUS_PROCESSING and $errand->status == Utils::ORDER_STATUS_PROCESSING) {

            $body = "Order with ID: *" . $order->order_id . "* already in process. Kindly be patient, I will soon update you on the status";
            $header = new Header(Utils::TEXT, 'Order Status');

        }
        else {
            $body = "Your Order has been placed, you will be notified shortly. Reply with *Order Status* to view status of all your pending orders\n\nTap *MENU* to checkout other services";
            $header = ["type" => "text", "text" => "Order Placed"];
        }

        return self::menuOptions($customerPhoneNumber, $header, $body);
    }

    public static function userPayMethod(string $customerPhoneNumber, $orderId, $method)
    {
        $order = Order::findOrFail($orderId);
        $errand = $order->errand;
        $orderSummary = OrderService::orderSummary($order);
        $totalAmount = ($errand) ? $order->total_amount + $errand->delivery_fee : $order->total_amount;

        $body = "Your order with the details below has been placed successfully. A dispatch rider will contact you soon. \nAttached is an invoice of this order.\n\n*Order Content*\n$orderSummary*Total Amount + delivery fee:* $totalAmount\n\n";

        if ($order) {

            if ($method === Utils::PAYMENT_METHOD_ON_DELIVERY) {
                $body .= "Kindly pay the stated amount when you receive the order\n\n";
            }

            elseif ($method === Utils::PAYMENT_METHOD_ONLINE) {
                $body .= "Click on the link below to make your payment\n\nhttps://easybuy4me.com/pay/?amount=$order->total_amount&user=$customerPhoneNumber&order=$order->order_id";
            }

            elseif ($method === Utils::PAYMENT_METHOD_TRANSFER) {

                $userAccounts = $order->user->monnifyAccounts;

                $accounts = "";

                foreach ($userAccounts as $account) {
                    $accounts .= "*$account->account_number*\n$account->account_name\n$account->bank\n\n";
                }

                $body .= "Kindly Transfer the above stated amount to any of the account numbers below to proceed\n\n$accounts";
            }

            $body .= "Tap *RECEIVED* once you have received this order";

            $header = ["type" => "document", "document" => ["link" => $order->transaction->orderInvoice->url]];

        $action = ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[buttons-order-recieved:$order->id]",
                    "title" => "RECEIVED"
                ]
            ]
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);

        $interactiveSendMessage = new InteractiveSendMessage($order->user->phone, Utils::INTERACTIVE, $interactive);
        return $interactiveSendMessage;
    

        }
    }

    public static function letsBegin(string $customerPhoneNumber): TextSendMessage
    {
        $body = "*Here We Go!*\n\nKindly provide your email to begin your registration.\nIf you were referred by someone, enter the referral code he/she gave you";
        return self::textMessage($body, $customerPhoneNumber, false);
    }

    public static function viewOurServices(string $customerPhoneNumber)
    {

        $greetingIndex = array_rand(Utils::GREETINGS_TO_CUSTOMER);
        $greeting = Utils::GREETINGS_TO_CUSTOMER[$greetingIndex];

        $bodyContent = "$greeting\nThese are the things I can do for you:\n\n*Purchase data (MTN, GLO, AIRTEL etc) for as low as NGN228 for 1GB*\n\n*Purchase Airtime with 2% commission*\n\n*Run and track your physical goods errands*\n\n*Send you daily updates: News, sports and Trends from Twitter*";

        $header = ["type" => Utils::TEXT, "text" => "Our Services"];

        $action = ['buttons' => array([
            "type" => Utils::REPLY,
            "reply" => [
                "id" => Utils::BUTTONS_GUEST_BEGIN,
                "title" => "LET'S BEGIN HERE"
            ]
        ])];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $bodyContent], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);
        return $interactiveSendMessage;
    }

    public static function insufficientBalnce($customerPhoneNumber, $orderId)
    {
        $order = Order::findOrFail($orderId);

        $userService = new UserService();
        $user = $userService->getUserByPhoneNumber($customerPhoneNumber);
        $walletBalance = $user->wallet->balance;

        $totalAmount = ($order->errand) ? $order->total_amount + $order->errand->delivery_fee : $order->total_amount;

        $body = "Ooops!\nAm sorry I was not able to complete your request, this is because your wallet balance is low. \n\nTotal Amount: $totalAmount\nWallet balance: $walletBalance\n\nTap *ADD MONEY* to fund your wallet";
        $header = ["type" => "text", "text" => "INSUFFICIENT BALANCE"];

        $action = ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => Utils::BUTTONS_FUND_MY_WALLET,
                    "title" => "ADD MONEY"
                ]
            ],
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);
        return $interactiveSendMessage;
    }

    public static function notifyAdmin(User $customer, Order $order, $method)
    {

        $errand = Errand::where('order_id', $order->id)->first();

        $deliveryAddress = $errand->delivery_address;

            if ($order->status === Utils::ORDER_STATUS_PROCESSING and ($errand and $errand->status == Utils::ORDER_STATUS_INITIATED)) {

                $orderSummary = "NEW ORDER PLACED!!\n\nOrder ID: *" . strtoupper($order->order_id) . "*\n\n";

                $orderSummary .= OrderService::orderSummary($order);

                $phone = $customer->phone;

                $orderSummary .= "*Customer phone:* $phone \n*Payment Method:* $method\n";
                $orderSummary .= "*Delivery address:* $deliveryAddress\n\n";

                $orderSummary .= "Kindly confirm and reply with the format below\n\n*confirm-order-$order->order_id:[FEE]*";

                return self::textMessage($orderSummary, UserService::getAdmin()->phone, false);
            }
    }

    public static function chooseWallet($customerPhoneNumber, $order)
    {

        if ($order) {

            $user = $order->user;

            $header = ['type' => Utils::TEXT, 'text' => "SELECT PAYMENT SOURCE"];

            $selectionRows = [];

            $wallet = $user->wallet;

            $id = "[select-wallet-$wallet->id:$order->id]";
            $description = "Account Balance: $wallet->balance";

            $row = new Row($id, "My Wallet", $description);

            array_push($selectionRows, $row);

            if(!Str::startsWith($order->description, "Easy lunch package")) {
                
                array_push($selectionRows, new Row("[select-wallet-online:$order->id]", "Pay Online", "Pay with your debit card"));
                array_push($selectionRows, new Row("[select-wallet-transfer:$order->id]", "Transfer", "Transfer money to my account"));
                array_push($selectionRows, new Row("[select-wallet-delivery:$order->id]", "Pay on delivery", "Pay me when I am done running your errand"));
            }

            //Build section
            $section = new Section("Total: $order->total_amount", $selectionRows);

            //Build Action
            $button = "PAYMENT SOURCE";
            $action = new Action($button, array($section));

            //Build a footer
            $footer = ['text' => Utils::EASY_BUY_4_ME_FOOTER];

            $body = ['text' => "Select any of the source of payment listed below to complete your transaction\n\nTap *$button* to view all"];

            $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);

            $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

            return $interactiveSendMessage;
        }
    }

    public static function confirmOrderCheckout($customerPhoneNumber, Order $order, bool $easylunchRequest, $easyluchSubId = 0)
    {

        $orderSummary = OrderService::orderSummary($order);

        $orderSummary .= $easylunchRequest ? "Are you sure you want to use your easy lunch package for today?" : "Are you sure you want to continue with this order?";

        $header = ["type" => "text", "text" => "ORDER SUMMARY"];

        $action = ($easyluchSubId === 0) ? ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => $easylunchRequest ? "[order-confirm-yes:$order->id:easylunch]" : "[order-confirm-yes:$order->id]",
                    "title" => "YES, CONTINUE"
                ]
            ],
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[order-confirm-no:$order->id]",
                    "title" => "NO"
                ]
            ]
        )] :

        ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => $easylunchRequest ? "[order-confirm-yes:$order->id:easylunch_$easyluchSubId]" : "[order-confirm-yes:$order->id]",
                    "title" => "YES, CONTINUE"
                ]
            ],
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[order-confirm-no:$order->id]",
                    "title" => "NO"
                ]
            ]
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $orderSummary], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);
        return $interactiveSendMessage;
    }

    public static function vendor($customerPhoneNumber, Vendor $vendor)
    {

        $greetingIndex = array_rand(Utils::GREETINGS_TO_CUSTOMER);
        $greeting = Utils::GREETINGS_TO_CUSTOMER[$greetingIndex];

        $body = "$greeting,\nWelcome to *$vendor->name*. Tap *CATALOG* to view all their items";

        $header = ["type" => "image", "image" => ["link" => $vendor->imageUrl]];

        $action = ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[Order from $vendor->id]",
                    "title" => "CATALOG"
                ]
            ],
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => Utils::BUTTONS_GO_TO_DASHBOARD,
                    "title" => "MENU"
                ]
            ]
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);
        return $interactiveSendMessage;
    }

    public static function welcomeMessage(string $customerPhoneNumber, bool $withRef): SendMessage
    {
        $greetingIndex = array_rand(Utils::GREETINGS_TO_CUSTOMER);
        $greeting = Utils::GREETINGS_TO_CUSTOMER[$greetingIndex];

        $body = $withRef ? "$greeting,\nMy name is *EasyBuy4Me*. I am a BOT. I can help you with your physical and digital errands.\nKindly provide your email to begin your registration." : "$greeting,\nMy name is *EasyBuy4Me*. I am a BOT. I can help you with your physical and digital errands.\nKindly enter a referral code if you have one or provide your email to begin your registration.";

        $header = ["type" => "image", "image" => ["link" => Utils::ERRAND_BANNAER]];

        $action = ['buttons' => array([
            "type" => Utils::REPLY,
            "reply" => [
                "id" => Utils::BUTTONS_VIEW_SERVICES,
                "title" => "VIEW OUR SERVICES"
            ]
        ])];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);
        return $interactiveSendMessage;
    }

    public static function currentOrder($customerPhoneNumber, Order $order, $easylunchRequest = false)
    {
        
        $body = $easylunchRequest ?  $order->description . "\n\n*Total amount:* $order->total_amount" : $order->description . "\n\n*Total amount:* $order->total_amount \n\n";

        if(!Str::startsWith($order->description, 'Easy lunch package')) {
            $body .= "*ADD MORE* to add items from current vendor or *OTHERS* to add from other vendors";
        }

        $header = new Header(Utils::TEXT, "Order Details");

        $action = ($easylunchRequest or Str::startsWith($order->description, "Easy lunch package")) ? ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => $easylunchRequest ? "[button-order-checkout:$order->id:el]" : "[button-order-checkout:$order->id]",
                    "title" => "CHECKOUT"
                ]
            ]
        )] :
            ['buttons' => array(
                [
                    "type" => Utils::REPLY,
                    "reply" => [
                        "id" => Utils::BUTTONS_ORDER_ADD_ITEM,
                        "title" => "ADD MORE"
                    ]
                ],

                [
                    "type" => Utils::REPLY,
                    "reply" => [
                        "id" => Utils::BUTTONS_ORDER_ADD_MORE_ITEM,
                        "title" => "OTHERS"
                    ]
                ],

                [
                    "type" => Utils::REPLY,
                    "reply" => [
                        "id" => "[button-order-checkout:$order->id]",
                        "title" => "CHECKOUT"
                    ]
                ]
            )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);
        return $interactiveSendMessage;
    }

    public static function vendorCatalog($customerPhoneNumber, Vendor $vendor, $easylunchRequest)
    {

        $vendorItems = $vendor->items;

        if ($vendorItems->count() > 0) {

            $header = ['type' => Utils::TEXT, 'text' => "ITEMS FROM " . $vendor->name];

            //Build all rows
            $selectionRows = [];

            foreach ($vendorItems as $vendorItem) {

                $id = $easylunchRequest ? "[order-" . $vendor->id . ":" . $vendorItem->id . ":easylunch" . "]" : "[order-" . $vendor->id . ":" . $vendorItem->id . "]";
                $description = $vendorItem->item_name . " - " . "N" . $vendorItem->item_price . " per " . $vendorItem->unit_name;

                $row = new Row($id, $vendorItem->item_name, $description);

                array_push($selectionRows, $row);
            }

            //Build section
            $section = new Section("Items Menu", $selectionRows);

            //Build Action
            $button = "ITEM CATALOG";
            $action = new Action($button, array($section));

            //Build a footer
            $footer = ['text' => Utils::EASY_BUY_4_ME_FOOTER];

            $body = ['text' => "All available items from $vendor->name\n\nTap *$button* to view all"];

            $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);

            $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

            return $interactiveSendMessage;
        }
    }

    public static function errandCustom($customerPhoneNumber)
    {

        $errandService = new ErrandService();
        $pickUpErrandService = $errandService->getErrandService(Utils::ERRAND_CUSTOM);

        $body = $pickUpErrandService['message'];

        $header = new Header(Utils::TEXT, "Custom Errand");

        return self::menuOptions($customerPhoneNumber, $header, $body);
    }

    public static function errandOthers($customerPhoneNumber, bool $urlPreview = false)
    {
        $errandService = new ErrandService();

        $errandServicesOptions = $errandService->getErrandService(Utils::ERRAND_OTHER_ITEMS);
        $bodyContent = $errandServicesOptions['message'];
        $optionsArray = $errandServicesOptions['options'];

        if (count($optionsArray) > 0) {

            $header = ['type' => Utils::TEXT, 'text' => "UNCATEGORIZED ORDERS"];

            //Build all rows
            $selectionRows = [];

            foreach ($optionsArray as $id => $description) {
                $row = new Row($id, str_replace("Order From ", "", ucwords(str_replace("]", "", str_replace("[", "", str_replace("-", " ", $id))))), $description);
                array_push($selectionRows, $row);
            }

            //Build section
            $section = new Section("Select Vendor", $selectionRows);

            //Build Action
            $action = new Action("OTHER ITEMS", array($section));

            //Build a footer
            $footer = ['text' => 'Tap *OTHER ITEMS* for other'];

            $body = ['text' => $bodyContent];

            $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);

            $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

            return $interactiveSendMessage;
        } else {
            return self::errandHome($customerPhoneNumber, "Sorry no availble vendor for this service. Try again leter or select another\n");
        }
    }

    public static function errandItemPickUp($customerPhoneNumber)
    {

        $admin = UserService::getAdmin();

        $errandService = new ErrandService();
        $pickUpErrandService = $errandService->getErrandService(Utils::ERRAND_ITEM_PICK_UP);

        $body = $pickUpErrandService['message']. " via -> https://wa.me/$admin->phone";

        $header = new Header(Utils::TEXT, "Item Pickup");

        return self::menuOptions($customerPhoneNumber, $header, $body);

    }

    public static function errandGroceryShopping($customerPhoneNumber)
    {

        $errandService = new ErrandService();

        $errandServicesOptions = $errandService->getErrandService(Utils::ERRAND_GROCERY_SHOPPING);
        $bodyContent = $errandServicesOptions['message'];
        $optionsArray = $errandServicesOptions['options'];

        if (count($optionsArray) > 0) {
            $header = ['type' => Utils::TEXT, 'text' => strtoupper("GROCERY SHOPPING")];

            //Build all rows
            $selectionRows = [];

            foreach ($optionsArray as $id => $description) {
                $row = new Row($id, str_replace("Order From ", "", ucwords(str_replace("]", "", str_replace("[", "", str_replace("-", " ", $id))))), $description);
                array_push($selectionRows, $row);
            }

            //Build section
            $section = new Section("Select Vendor", $selectionRows);

            //Build Action
            $action = new Action("GROCERY VENDORS", array($section));

            //Build a footer
            $footer = ['text' => 'Tap *GROCERY VENDORS* to select a grocery vendor'];

            $body = ['text' => $bodyContent];

            $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);

            $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

            return $interactiveSendMessage;
        } 
        
        else {
            $admin = User::where("role", Utils::USER_ROLE_ADMIN)->first();
            return self::errandHome($customerPhoneNumber, "Sorry no availble vendor for this service. Try again leter or select another vendor from *MENU* below. You can chat admin to find alternatives by clicking https://wa.me/$admin->phone");
        }
    }

    public static function allVendors($customerPhoneNumber)
    {

        $errandService = new ErrandService();

        $errandServicesOptions = $errandService->getErrandService(Utils::ERRAND_VENDORS);
        $bodyContent = $errandServicesOptions['message'];
        $optionsArray = $errandServicesOptions['options'];

        if (count($optionsArray) > 0) {
            $header = ['type' => Utils::TEXT, 'text' => "AVAILABLE VENDORS"];

            //Build all rows
            $selectionRows = [];

            foreach ($optionsArray as $id => $ven) {

                $row = new Row($id, $ven[0], $ven[1]);
                array_push($selectionRows, $row);
            }

            array_push($selectionRows, new Row(Utils::BUTTONS_GO_TO_DASHBOARD, "Menu", "Go back to main menue"));

            //Build section
            $section = new Section("Select Vendor", $selectionRows);

            //Build Action
            $action = new Action("VENDORS", array($section));

            //Build a footer
            $footer = ['text' => 'Tap *VENDORS* to view all available vendors'];

            $body = ['text' => $bodyContent];

            $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);

            $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

            return $interactiveSendMessage;
        } 
        
        else {
            return self::errandHome($customerPhoneNumber, "Sorry no availble vendor for this service. Try again leter or select another\n");
        }
    }

    public static function errandOrderFood($customerPhoneNumber, $easyLunch)
    {

        $errandService = new ErrandService();

        $errandServicesOptions = $errandService->getErrandService(Utils::ERRAND_ORDER_FOOD, $easyLunch);
        $bodyContent = "Need to buy food but don’t want to move out?\n\nCheck out the list of vendors, I can help you buy and deliver to your doorstep.\n\nTap *FOOD VENDORS* to select a food vendor";
        $optionsArray = $errandServicesOptions['options'];

        if (count($optionsArray) > 0) {

            $header = ['type' => Utils::TEXT, 'text' => strtoupper("FOOD VENDORS")];

            //Build all rows
            $selectionRows = [];

            foreach ($optionsArray as $id => $description) {
                $vendor = Vendor::find(str_replace("Order From ", "", ucwords(str_replace("]", "", str_replace("[", "", str_replace("-", " ", $id))))));
                $row = new Row($id, $vendor->name, $description);
                array_push($selectionRows, $row);
            }

            //Build section
            $section = new Section("Select Vendor", $selectionRows);

            //Build Action
            $action = new Action("FOOD VENDORS", array($section));

            //Build a footer
            $footer = ['text' => Utils::EASY_BUY_4_ME_FOOTER];

            $body = ['text' => $bodyContent];

            $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);

            $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

            return $interactiveSendMessage;
        } 
        
        else {
            return self::errandHome($customerPhoneNumber, "Sorry no availble vendor for this service. Try again leter or select another\n");
        }
    }

    public static function errandHome($customerPhoneNumber, $optional = "")
    {

        $errandService = new ErrandService();
        $errandServiceInit = $errandService->init();

        $bodyContent = (strlen($optional) > 1) ? $optional : $errandServiceInit['message'];
        $button = $errandServiceInit['button'];

        $errandServicesOptions = $errandService->getErrandServicesOptions();
        $title = $errandServicesOptions['message'];
        $optionsArray = $errandServicesOptions['options'];

        $header = ['type' => Utils::TEXT, 'text' => strtoupper("EASYBUY " . $title)];

        //Build all rows
        $selectionRows = [];

        foreach ($optionsArray as $id => $description) {
            $row = new Row($id, str_replace("Errand ", "", ucwords(str_replace("]", "", str_replace("[", "", str_replace("-", " ", $id))))), $description);
            array_push($selectionRows, $row);
        }

        array_push($selectionRows, new Row(Utils::BUTTONS_GO_TO_DASHBOARD, 'Menu', 'Go back to main menu'));

        //Build section
        $section = new Section($title, $selectionRows);

        //Build Action
        $action = new Action($button, array($section));

        //Build a footer
        $footer = ['text' => Utils::EASY_BUY_4_ME_FOOTER];

        $body = ['text' => $bodyContent];

        $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function errorMessage(string $customerPhoneNumber, bool $registered, bool $urlPreview)
    {
        $body = "";

        if ($registered) {
            $registeredCustomerBody = "Or reply with the following\n\n";

            foreach (Utils::USER_INPUT_MESSAGES as $input => $message) {
                $registeredCustomerBody .= "*$input* : $message\n";
            }

            $header = new Header(Utils::TEXT, "Wrong Input!");

            $body = "Ooops!\nLooks like you entered unknown response, kindly reply with *Hi* to get started. $registeredCustomerBody";

            return self::menuOptions($customerPhoneNumber, $header, $body);
            
        } 
        else {

            $body = "Ooops!\nLooks like you entered unknown response, kindly reply with *Hi* to get started. ";
            return self::textMessage($body, $customerPhoneNumber, $urlPreview);
        }
    }

    /**
     * Function to prepare message to notify user to enter his/her name during registration
     *
     * @param string $email
     * @param string $customerPhoneNumber
     * @param boolean $urlPreview
     * @return TextSendMessage
     */
    public static function enterNameMessage(string $email, string $customerPhoneNumber, bool $urlPreview): InteractiveSendMessage
    {
        $body = "Great, I have registered your email as *$email*.\nKindly Provide your full name in the order: FirstName LastName e.g: John Doe.";

        $header = ["type" => "image", "image" => ["link" => Utils::REG_BANNAER]];

        $action = ['buttons' => array([
            "type" => Utils::REPLY,
            "reply" => [
                "id" => Utils::BUTTONS_START_AGAIN,
                "title" => "START AGAIN"
            ]
        ])];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => Utils::EASY_BUY_4_ME_FOOTER], $action);
        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function sendVerificationNotificationMessage($email, $customerPhoneNumber, $urlPreview): TextSendMessage
    {
        $body = "Welcome again, I have sent a message to $email. Kindly click on the link to verify your email.";
        return self::textMessage($body, $customerPhoneNumber, $urlPreview);
    }

    public static function invalidTokenMessage($customerPhoneNumber, $code, $urlPreview)
    {
        $body = "Verification token: *$code* does not match our record. Kindly enter correct code or reply *Hi* to start again";
        return self::textMessage($body, $customerPhoneNumber, $urlPreview);
    }

    /**
     * Functio to return user dashboard
     *
     * @param User $user
     * @return InteractiveSendMessage
     */
    public static function dashboardMessage(User $user): InteractiveSendMessage
    {
        $totalWalletBalance = $user->wallet->balance;

        $bodyContent = "WALLET BALANCE: ₦$totalWalletBalance\n\nHello $user->first_name, I can't express how happy I am to assist you at this moment.\n\nI am always here to help you run an errand, tap *MENU* and tell me what to do for you now.";

        $header = new Header(Utils::TEXT, "WELCOME TO EASYBUY4ME");

        return self::menuOptions($user->phone, $header, $bodyContent);
    }

    private static function textMessage($body, $customerPhoneNumber, $urlPreview)
    {
        $text = new BodyText($body, $urlPreview);

        $textSendMessage = new TextSendMessage($customerPhoneNumber, Utils::TEXT, $text);
        return $textSendMessage;
    }


    private static function menuOptions($customerPhoneNumber, $header, $bodyContent) {

        $body = ['text' => $bodyContent];

         //Build all rows 
         $selectionRows = [];

         foreach (Utils::DASHBOARD_MENU as $id => $description) {
             $row = new Row($id, $description[0], $description[1]);
             array_push($selectionRows, $row);
         }
 
         //Build section
         $section = new Section("Services", $selectionRows);
 
         //Build Action
         $action = new Action("MENU", array($section));

          //Build a footer
        $footer = ['text' => Utils::EASY_BUY_4_ME_FOOTER];

        $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;

    }
}
