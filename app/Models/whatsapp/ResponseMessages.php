<?php

namespace App\Models\whatsapp;

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
use App\Services\ErrandService;
use App\Services\OrderService;
use App\Services\UserService;

class ResponseMessages
{

    public static function easyLunchPayLater($customerPhoneNumber) {
        $body = "Your current order have been saved\nKindly pay as soon as possible.\n\n*Kindly note that this order will no longer exist after 15 munites from now.";
        $header = new Header(Utils::TEXT, "Pay Later");

        $action = ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => Utils::BUTTONS_GO_TO_DASHBOARD,
                    "title" => "DASHBOARD"
                ]
            ]
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "@easyBuy4me"], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function easyLunchSubscribed($customerPhoneNumber, EasyLunchSubscribers $easyLunchSubscriber)
    {
        $body = "Subscription added successfully\n\nKindly choose one of the options below";
        $header = new Header(Utils::TEXT, "Subsription Success");

        $action = ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[button-easy-lunch-sub-pay-now:$easyLunchSubscriber->id]",
                    "title" => "PAY NOW"
                ]
            ],
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => Utils::BUTTONS_EASY_LUNCH_SUB_PAY_LATER,
                    "title" => "PAY LATER"
                ]
            ]
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "@easyBuy4me"], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function easyLunchHome(User $user, $easyLunches, $activeSub)
    {
        if(!$activeSub) {
            $bodyContent = $easyLunches->reduce(function ($initial, $easyLunch) {
                return $initial . "The *$easyLunch->name package* which costs N$easyLunch->cost_per_week per week and N$easyLunch->cost_per_month for a month";
            }, "EasyLaunch is a subscription package designed to provide customers with a meal 🍲 choice option within the 5 working days, ensuring that they receive a daily meal of their choice.\nThere are two package options available for customers\n\n");
    
            $bodyContent .= "Customers are expected to choose their preferred meal for the day from the list of meal available to them on the *EASY LUNCH* menu on the *DASHBOARD* every morning.\n\nTap the *MENU* below to subscribe";
    
            $weeklyPackages = [];
            $monthlyPackages = [];
    
            foreach ($easyLunches as $easyLunch) {
                if ($easyLunch->cost_per_week > 0)
                    array_push($weeklyPackages, new Row("[subscribe-easylunch-weekly:$easyLunch->id:$easyLunch->cost_per_week]", ucwords($easyLunch->name . " (weekly)"), "Costs N$easyLunch->cost_per_week"));
                if ($easyLunch->cost_per_month > 0)
                    array_push($monthlyPackages, new Row("[subscribe-easylunch-monthly:$easyLunch->id:$easyLunch->cost_per_month]", ucwords($easyLunch->name . " (monthly)"), "Costs N$easyLunch->cost_per_month"));
            }
    
            //Build section
            $weeklySection = new Section("Weekly Packages", $weeklyPackages);
            $monthlySection = new Section("Monthly Packages", $monthlyPackages);
    
            $allSections = array();
            if (count($weeklySection->rows) > 0)
                array_push($allSections, $weeklySection);
    
            if (count($monthlySection->rows) > 0)
                array_push($allSections, $monthlySection);
    
            //Build Action
            $action = new Action("EASY LUNCH", $allSections);
    
            //Build a footer
            $footer = ['text' => "@easybuy4me"];
    
            $body = ['text' => $bodyContent];
    
            $header = new Header(Utils::TEXT, "EASY LUNCH SERVICE");
    
            $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);
    
            $interactiveSendMessage = new InteractiveSendMessage($user->phone, Utils::INTERACTIVE, $interactive);
    
            return $interactiveSendMessage;
        }
        else return self::errandOrderFood($user->phone, true);
    }

    public static function showSupport($customerPhoneNumber, User $admin)
    {

        $body = "You can support me via the following accounts\n\n*3676367393*\nAccess Bank\nMNFY/ Tasiu Kwap\n\n*983983786*\nMonie Point Bank\nMNFY/ Tasiu Kwap\n\n*001972626*\nWema Bank\nMNFY/ Tasiu Kwap\n\nOr chat with an admin via https://wa.me/$admin->phone";
        $header = new Header(Utils::TEXT, "Support Me");

        $action = ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => Utils::BUTTONS_GO_TO_DASHBOARD,
                    "title" => "DASHBOARD"
                ]
            ]
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "@easyBuy4me"], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
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
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => Utils::BUTTONS_GO_TO_DASHBOARD,
                    "title" => "DASHBOARD"
                ]
            ]
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "@easyBuy4me"], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function userWallets($customerPhoneNumber, $userWallets)
    {

        $totalWalletBalance = $userWallets->reduce(function ($initial, $wallet) {
            return $initial + $wallet->balance;
        }, 0);

        $walletDetails = $userWallets->reduce(function ($initial, $wallet) {
            return $initial . "*$wallet->account_number*\n$wallet->account_name\n$wallet->bank\n$wallet->balance\n\n";
        }, "*Your Wallets:*\n\n");

        $body = $walletDetails . "*Total Balance:* $totalWalletBalance\n\nTo fund your wallet, make transfer to any of the accounts above\n\n*Fund online? click here -> https://easybuy4me.com/fund/?user=$customerPhoneNumber";

        $header = new Header(Utils::TEXT, "My Wallets");

        $action = ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => Utils::BUTTONS_USER_WALLET_HISTORY,
                    "title" => "HISTORY"
                ]
            ],
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => Utils::BUTTONS_GO_TO_DASHBOARD,
                    "title" => "DASHBOARD"
                ]
            ]
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "@easyBuy4me"], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function userCartCleared($customerPhoneNumber)
    {
        $body = "Your Cart is cleared";
        $header = new Header(Utils::TEXT, "My Cart");

        $action = ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => Utils::BUTTONS_GO_TO_DASHBOARD,
                    "title" => "DASHBOARD"
                ]
            ]
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "@easyBuy4me"], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function sendUserCart($customerPhoneNumber, $order, $empty)
    {

        $body = ($empty and !$order) ? "Your Cart is empty" : "Your cart contains\n\n" . OrderService::orderSummary($order);
        $header = new Header(Utils::TEXT, "My Cart");

        $action = $empty ? ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => Utils::BUTTONS_GO_TO_DASHBOARD,
                    "title" => "DASHBOARD"
                ]
            ]
        )]

            :

            ['buttons' => array(
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
                        "id" => Utils::BUTTONS_GO_TO_DASHBOARD,
                        "title" => "DASHBOARD"
                    ]
                ]
            )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "@easyBuy4me"], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function userOrderProcessed($customerPhoneNumber, $order, $dispatcher, $fee)
    {

        $body = "Kindly find the details below\n\n" . OrderService::orderSummary($order) . "Customer: $customerPhoneNumber\nService charge: $fee\nOrder ID: $order->order_id\n\nThe dispatch rider ($dispatcher) assigned to you will contact you shortly.";
        $header = new Header(Utils::TEXT, "Your order has been placed");

        $action = ['buttons' => array([
            "type" => Utils::REPLY,
            "reply" => [
                "id" => Utils::BUTTONS_GO_TO_DASHBOARD,
                "title" => "DASHBOARD"
            ]
        ])];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "@easyBuy4me"], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function adminOrderProcessedSuccess($customerPhoneNumber, $order)
    {

        $orderId = strtoupper($order->order_id);
        $body = $order ? "Order: *$orderId* placed successfully" : "Order: *$orderId* could not be processed. Check if order is still valid";

        return self::textMessage($body, $customerPhoneNumber, false);
    }

    public static function userOrderPlaced(string $customerPhoneNumber)
    {

        $body = "Hey! Thanks for believing in me. I am on my way to run your errand. I will update you as soon as possible. Meanwhile, tap *DASHBOARD* to checkout other stuff I can do for you";

        $header = ["type" => "image", "image" => ["link" => Utils::ERRAND_BANNAER]];

        $action = ['buttons' => array([
            "type" => Utils::REPLY,
            "reply" => [
                "id" => Utils::BUTTONS_GO_TO_DASHBOARD,
                "title" => "DASHBOARD"
            ]
        ])];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "@easyBuy4me"], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);
        return $interactiveSendMessage;
    }

    public static function userPayMethod(string $customerPhoneNumber, $orderId, $method): TextSendMessage
    {
        $order = Order::find($orderId);
        $body = "";

        if ($order) {

            $orderID = strtoupper($order->order_id);

            if ($method === "PAY ON DELIVERY") {
                $body = "ORDER ID: *$orderID*\nTotal Amount: $order->total_amount\n\nI will soon deliver the above order to you. kindly pay the stated amount when I come";
            }

            if ($method === "ONLINE") {
                $body = "Click on the link below to make your payment\n\nhttps://easybuy4me.com/pay/?amount=$order->total_amount&user=$customerPhoneNumber&order=$order->order_id";
            }

            if ($method === "TRANSFER") {
                $body = "ORDER ID: *$orderID*\nTotal Amount: $order->total_amount\n\nKindly Transfer the above stated amount to any of the account numbers below\n\n*3676367393*\nAccess Bank\nMNFY/ Tasiu Kwap\n\n*983983786*\nMonie Point Bank\nMNFY/ Tasiu Kwap\n\n*001972626*\nWema Bank\nMNFY/ Tasiu Kwap";
            }

            return self::textMessage($body, $customerPhoneNumber, true);
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

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $bodyContent], ['text' => "@easyBuy4me"], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);
        return $interactiveSendMessage;
    }

    public static function insufficientBalnce($customerPhoneNumber, $orderId)
    {
        $order = Order::find($orderId);

        $userService = new UserService();
        $user = $userService->getUserByPhoneNumber($customerPhoneNumber);

        $totalWalletBalance = $user->wallets->reduce(function ($initial, $wallet) {
            return $initial + $wallet->balance;
        }, 0);

        $body = "Ooops!\nAm sorry I was not able to complete your request, this is because your wallet balance is low. \n\nTotal Amount: $order->total_amount\nWallet balance: $totalWalletBalance\n\nTap *ADD MONEY* to fund your wallet";
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

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "@easyBuy4me"], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);
        return $interactiveSendMessage;
    }

    public static function notifyAdmin($customerPhoneNumber, Errand $errand, $method)
    {

        $order = Order::where("id", $errand->order_id)->first();

        if ($order->status === Utils::ORDER_STATUS_INITIATED) {
            $orderSummary = "NEW ORDER PLACED!!\n\nOrder ID: *" . strtoupper($order->order_id) . "*\n\n";

            $orderSummary .= OrderService::orderSummary($order);

            $orderSummary .= "Customer phone: $errand->destination_phone\nPayment Method: $method\n\nKindly reply with a dispatcher phone number in the format \nprocess-order-OREDER_ID:DISPATCHER_PHONE:FEE";

            $admin = User::where('is_admin', true)->first();
            return self::textMessage($orderSummary, $admin->phone, false);
        } else {
            $body = "Order with ID: *" . strtoupper($order->order_id) . "* already in process. Kindly be patient, I will soon update you on the status";
            return self::textMessage($body, $customerPhoneNumber, false);
        }
    }

    public static function chooseWallet($customerPhoneNumber, $orderId)
    {

        $order = Order::find($orderId);

        if ($order) {

            $user = $order->user;

            $header = ['type' => Utils::TEXT, 'text' => "SELECT PAYMENT SOURCE"];

            $userWallets = $user->wallets;
            $selectionRows = [];

            foreach ($userWallets as $wallet) {

                $id = "[select-wallet-$wallet->id:$orderId]";
                $name = "$wallet->bank - $wallet->account_number";
                $description = "Account Balance: $wallet->balance";

                $row = new Row($id, $name, $description);

                array_push($selectionRows, $row);
            }

            array_push($selectionRows, new Row("[select-wallet-all:$orderId]", "All Wallets", "Check all my wallet for funds"));
            array_push($selectionRows, new Row("[select-wallet-online:$orderId]", "Pay Online", "Pay with your debit card"));
            array_push($selectionRows, new Row("[select-wallet-transfer:$orderId]", "Transfer", "Transfer money to my account"));
            array_push($selectionRows, new Row("[select-wallet-delivery:$orderId]", "Pay on delivery", "Pay me when I am done running your errand"));

            //Build section
            $section = new Section("Total: $order->total_amount", $selectionRows);

            //Build Action
            $button = "SELECT WALLET";
            $action = new Action($button, array($section));

            //Build a footer
            $footer = ['text' => "@easybuy4me"];

            $body = ['text' => "Select any of your wallet listed below to complete your transaction\n\nTap *$button* to view all"];

            $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);

            $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

            return $interactiveSendMessage;
        }
    }

    public static function confirmOrderCheckout($customerPhoneNumber, Order $order, $easylunchRequest)
    {

        $orderSummary = OrderService::orderSummary($order);

        $orderSummary .= "Are you sure you want to continue with this order?";

        $header = ["type" => "text", "text" => "ORDER SUMMARY"];

        $action = ['buttons' => array(
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
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $orderSummary], ['text' => "@easyBuy4me"], $action);

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
                    "id" => "[Order from $vendor->name]",
                    "title" => "CATALOG"
                ]
            ],
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => Utils::BUTTONS_GO_TO_DASHBOARD,
                    "title" => "GO TO DASHBOARD"
                ]
            ]
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "@easyBuy4me"], $action);

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

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "@easyBuy4me"], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);
        return $interactiveSendMessage;
    }

    public static function currentOrder($customerPhoneNumber, Order $order, $easylunchRequest = false)
    {

        $body = $order->description . "\n\n*Total amount:* $order->total_amount \n\n*ADD MORE* to add items from current vendor or *OTHERS* to add from other vendors";

        $header = new Header(Utils::TEXT, "Order Details");

        $action = $easylunchRequest ? ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[button-order-checkout:$order->id]",
                    "title" => "PAY NOW"
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
                    "title" => "PAY NOW"
                ]
            ]
        )];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "@easyBuy4me"], $action);

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
            $footer = ['text' => "@easybuy4me"];

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

        $action = ['buttons' => array([
            "type" => Utils::REPLY,
            "reply" => [
                "id" => Utils::BUTTONS_GO_TO_DASHBOARD,
                "title" => "DASHBOARD"
            ]
        ])];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "Tap *DASHBOARD* to return"], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);
        return $interactiveSendMessage;
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

        $errandService = new ErrandService();
        $pickUpErrandService = $errandService->getErrandService(Utils::ERRAND_ITEM_PICK_UP);

        $body = $pickUpErrandService['message'];

        $header = new Header(Utils::TEXT, "Item Pickup");

        $action = ['buttons' => array([
            "type" => Utils::REPLY,
            "reply" => [
                "id" => Utils::BUTTONS_GO_TO_DASHBOARD,
                "title" => "DASHBOARD"
            ]
        ])];

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "Tap *DASHBOARD* to return"], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);
        return $interactiveSendMessage;
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
        } else {
            return self::errandHome($customerPhoneNumber, "Sorry no availble vendor for this service. Try again leter or select another\n");
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
        } else {
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
                $row = new Row($id, str_replace("Order From ", "", ucwords(str_replace("]", "", str_replace("[", "", str_replace("-", " ", $id))))), $description);
                array_push($selectionRows, $row);
            }

            //Build section
            $section = new Section("Select Vendor", $selectionRows);

            //Build Action
            $action = new Action("FOOD VENDORS", array($section));

            //Build a footer
            $footer = ['text' => '@easybuy4me'];

            $body = ['text' => $bodyContent];

            $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);

            $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

            return $interactiveSendMessage;
        } else {
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

        //Build section
        $section = new Section($title, $selectionRows);

        //Build Action
        $action = new Action($button, array($section));

        //Build a footer
        $footer = ['text' => '@easybuy4me'];

        $body = ['text' => $bodyContent];

        $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function errorMessage(string $customerPhoneNumber, bool $urlPreview)
    {
        $body = "Ooops!\nLooks like you entered unknown response, kindly reply with *Hi* to get started.";
        return self::textMessage($body, $customerPhoneNumber, $urlPreview);
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

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "@easyBuy4me"], $action);
        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    public static function sendVerificationNotificationMessage($email, $customerPhoneNumber, $urlPreview): TextSendMessage
    {
        $body = "Welcome again, I have sent a message to $email. Kindly click on the link to verify your email.";
        return self::textMessage($body, $customerPhoneNumber, $urlPreview);
    }

    public static function invalidTokenMessge($customerPhoneNumber, $code, $urlPreview)
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
        $userWallets = $user->wallets;

        $totalWalletBalance = $userWallets->reduce(function ($initial, $wallet) {
            return $initial + $wallet->balance;
        }, 0);

        $bodyContent = "WALLET BALANCE: ₦$totalWalletBalance\n\nHello $user->first_name, I can't express how happy I am to assist you at this moment.\n\nI am always here to help you run an errand, tap *SEND ME* and tell me what to do for you now.";

        //Build all rows 
        $selectionRows = [];

        foreach (Utils::DASHBOARD_MENU as $id => $description) {
            $row = new Row($id, $description[0], $description[1]);
            array_push($selectionRows, $row);
        }

        //Build section
        $section = new Section("Errands I can run", $selectionRows);

        //Build Action
        $action = new Action("SEND ME", array($section));

        //Build a footer
        $footer = ['text' => "@easybuy4me"];

        $body = ['text' => $bodyContent];

        $header = new Header(Utils::TEXT, "WELCOME TO EASYBUY4ME");

        $interactive = new Interactive(Utils::LIST, $header, $body, $footer, $action);

        $interactiveSendMessage = new InteractiveSendMessage($user->phone, Utils::INTERACTIVE, $interactive);

        return $interactiveSendMessage;
    }

    private static function textMessage($body, $customerPhoneNumber, $urlPreview)
    {
        $text = new BodyText($body, $urlPreview);

        $textSendMessage = new TextSendMessage($customerPhoneNumber, Utils::TEXT, $text);
        return $textSendMessage;
    }
}