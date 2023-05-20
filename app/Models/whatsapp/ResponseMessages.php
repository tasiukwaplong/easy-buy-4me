<?php

namespace App\Models\whatsapp;

use App\Models\Errand;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderedItem;
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

class ResponseMessages
{

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

        $header = ["type" => "image", "image" => ["link" => Utils::SERVICES_BANNAER]];

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

    public static function insufficientBalnce($customerPhoneNumber)
    {
    }

    public static function notifyAdmin($customerPhoneNumber, Errand $errand)
    {

        $order = Order::where("id", $errand->order_id)->first();

        if ($order->status === Utils::ORDER_STATUS_INITIATED) {
            $orderSummary = "ORDER ID: *" . strtoupper($order->order_id) . "*\n\n";

            foreach (OrderedItem::where('order_id', $order->id)->get() as $orI) {
                $i = Item::find($orI->item_id);
                $orderSummary = $orderSummary . "$i->item_name - N$i->item_price per $i->unit_name ($orI->quantity$i->unit_name)\n";
            }

            $orderSummary = $orderSummary . "\nTotal Amount: *$order->total_amount*\nCustomer phone: $errand->destination_phone\n\nKindly reply with a dispatcher phone number in the format \nprocess-order-OREDER_ID:DISPATCHER_PHONE";

            return self::textMessage($orderSummary, "2347035002025", false);
        }
        else {
            $body = "Order with ID: *" . strtoupper($order->order_id) . "* already in process. Kindly be patient, I will soon update you on the status";
            return self::textMessage($body, $customerPhoneNumber, false);
        }
    }

    public static function chooseWallet($customerPhoneNumber, $orderId)
    {

        $order = Order::find($orderId);

        if ($order) {

            $user = $order->user;

            $header = ['type' => Utils::TEXT, 'text' => "SELECT WALLET"];

            $userWallets = $user->wallets;
            $selectionRows = [];

            foreach ($userWallets as $wallet) {

                $id = "[select-wallet-$wallet->id:$orderId]";
                $name = "$wallet->bank - $wallet->account_number";
                $description = "Account Balance: $wallet->balance";

                $row = new Row($id, $name, $description);

                array_push($selectionRows, $row);
            }

            array_push($selectionRows, new Row("[select-wallet-all:$orderId", "All Wallets", "Check all my wallet for funds"));
            array_push($selectionRows, new Row("[select-wallet-aonline:$orderId", "Pay Online", "Pay with your debit card"));

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

    public static function confirmOrderCheckout($customerPhoneNumber, Order $order)
    {

        $orderSummary = "";

        foreach (OrderedItem::where('order_id', $order->id)->get() as $orI) {
            $i = Item::find($orI->item_id);
            $orderSummary = $orderSummary . "$i->item_name - N$i->item_price per $i->unit_name ($orI->quantity$i->unit_name)\n";
        }

        $orderSummary = $orderSummary . "\nTotal Amount: *$order->total_amount*\n\nAre you sure you want to continue with this order?";

        $header = ["type" => "text", "text" => "ORDER SUMMARY"];

        $action = ['buttons' => array(
            [
                "type" => Utils::REPLY,
                "reply" => [
                    "id" => "[order-confirm-yes:$order->id]",
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

    public static function currentOrder($customerPhoneNumber, Order $order)
    {

        $body = $order->description . "\n\n*Total amount:* $order->total_amount \n\n*ADD MORE* to add items from current vendor or *OTHERS* to add from other vendors";

        $header = new Header(Utils::TEXT, "Order Details");

        $action = ['buttons' => array(
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

        $interactive = new Interactive(Utils::BUTTON, $header, ['text' => $body], ['text' => "@easyBuy4me"], $action);

        $interactiveSendMessage = new InteractiveSendMessage($customerPhoneNumber, Utils::INTERACTIVE, $interactive);
        return $interactiveSendMessage;
    }

    public static function vendorCatalog($customerPhoneNumber, Vendor $vendor)
    {

        $vendorItems = $vendor->items;

        if ($vendorItems->count() > 0) {

            $header = ['type' => Utils::TEXT, 'text' => "ITEMS FROM " . $vendor->name];

            //Build all rows
            $selectionRows = [];

            foreach ($vendorItems as $vendorItem) {

                $id = "[order-" . $vendor->id . ":" . $vendorItem->id."]";
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



    public static function errandOrderFood($customerPhoneNumber)
    {

        $errandService = new ErrandService();

        $errandServicesOptions = $errandService->getErrandService(Utils::ERRAND_ORDER_FOOD);
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
