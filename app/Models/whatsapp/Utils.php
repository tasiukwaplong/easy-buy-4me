<?php

namespace App\Models\whatsapp;

class Utils
{
    //Admin events
    public const ADMIN_PROCESS_USER_ORDER = "admin-process-order";
    public const ADMIN_WALLET_EVENTS = "admin-wallet-events";
    public const ADMIN_USER_ORDER_NOTIFY = "admin-user-order-notify";

    //Possible origins of requests
    public const ORIGIN_WHATSAPP = "whatsapp";
    public const ORIGIN_FACEBOOK = "facebook";
    public const ORIGIN_TWITTER = "twitter";
    public const ORIGIN_TELEGRAM = "telegram";
    public const ORIGIN_VERIFICATION = "email_verify";
    public const ADMIN_EVENTS = "admin_event";

    //Expected type of messages from whatsapp
    public const BUTTON_REPLY = "button_reply";
    public const REPLY = "reply";
    public const LIST_REPLY = "list_reply";
    public const TEXT = "text";
    public const INTERACTIVE = "interactive";
    public const MEDIA_IMAGE = "image";
    public const LIST = "list";
    public const BUTTON = "button";

    //Errand IDs
    const ERRAND_ORDER_FOOD = '[errand-order-food]';
    const ERRAND_GROCERY_SHOPPING = '[errand-grocery-shopping]';
    const ERRAND_ITEM_PICK_UP = '[errand-item-pick-up]';
    const ERRAND_OTHER_ITEMS = '[errand-other-items]';
    const ERRAND_VENDORS = '[errand-vendors]';
    const ERRAND_CUSTOM = '[errand-custom]';

    //Expected greetings
    public const GREETINGS_TO_CUSTOMER = ['Hi', 'Yo!', 'Howdy!', "What's up!", 'Hey!', 'Hello', 'Greetings'];

    //Dashboard menu
    public const DASHBOARD_MENU = [
        self::MY_WALLET => ["Manage my wallet", "See balance, fund wallet"],
        self::ERRAND => ['Run a physical errand', "Let's get you your grocery and lunch from your favorite restaurant"],
        self::DATA => ['Purchase Data', "Data (Internet subscription): Purchase data at very affordable rate"],
        self::AIRTIME => ['Purchase Airtime', "Airtime (VTU): Purchase airtime at 2% commision"],
        self::EASY_LUNCH => ["Easy Lunch", "Get lunch delivered to your home/office every day of the week"],
        self::MY_CART => ['Cart', 'View my shopping cart'],
        self::TRANSACTION_HISTORY => ['Transaction', 'View my transaction history'],
        self::MORE => ['Find out more', "Learn about EasyBuy4Me, pricing, FAQ and more"],
    ];

    //Reply Buttons
    public const BUTTONS_START_AGAIN = '[button-start-again]';
    public const BUTTONS_EASY_LUNCH_SUB_PAY_NOW = '[button-easy-lunch-sub-pay-now]';
    public const BUTTONS_EASY_LUNCH_SUB_PAY_LATER = '[button-easy-lunch-sub-pay-later]';
    public const BUTTONS_SUPPORT = '[button-support]';
    public const BUTTONS_GUEST_BEGIN = '[button-guest-begin]';
    public const BUTTONS_VIEW_SERVICES = '[button-view-services]';
    public const BUTTONS_GO_TO_DASHBOARD = '[button-go-to-dashboard]';
    public const BUTTONS_DATA_PLAN_NETWORKS = '[button-data-plan-networks]';
    public const BUTTONS_DATA_PLAN_CANCEL = '[button-data-plan-cancel]';
    public const BUTTONS_ORDER_ADD_ITEM = '[button-order-add-item]';
    public const BUTTONS_ORDER_ADD_MORE_ITEM = '[button-order-add-more-item]';
    public const BUTTONS_ORDER_CHECKOUT = '[button-order-checkout]';
    public const BUTTONS_FUND_MY_WALLET = '[button-fund-my-wallet]';
    public const BUTTONS_USER_WALLET_HISTORY = '[button-user-wallet-history]';
    public const BUTTONS_CLEAR_CART = '[button-clear-cart]';

    //List reply IDs
    public const ERRAND = "[errand]";
    public const MY_WALLET = "[my-wallet]";
    public const MY_CART = "[my-cart]";
    public const DATA = "[data]";
    public const AIRTIME = "[airtime]";
    public const MORE = "[more]";
    public const EASY_LUNCH = "[easy-lunch]";
    public const TRANSACTION_HISTORY = "[transaction-history]";

    //Image urls
    public const ERRAND_BANNAER = "https://awazone.s3.amazonaws.com/public/errands.jpg";
    public const SERVICES_BANNAER = "https://awazone.s3.amazonaws.com/public/errands2.jpg";
    public const REG_BANNAER = "https://awazone.s3.amazonaws.com/public/reg_imge.jpg";

    //Order statuses
    public const ORDER_STATUS_PROCESSING = 1;
    public const ORDER_STATUS_CANCELLED = -1;
    public const ORDER_STATUS_DELIVERED = 0;
    public const ORDER_STATUS_INITIATED = 2;
    public const ORDER_STATUS_ENROUTE = 3;

    //Order types
    public const ORDER_TYPE_EASY_LUNCH = 'ORDER-EASY-LUNCH';
    public const ORDER_TYPE_FOOD = 'ORDER-FOOD';

    //Easylunch types
    public const EASY_LUNCH_TYPE_WEEKLY = "weekly";
    public const EASY_LUNCH_TYPE_MONTHLY = "monthly";

    //Expected User commands
    public const USER_INPUT_ORDER_STATUS = "Order status"; 
    public const USER_INPUT_CART = "Cart"; 
    public const USER_INPUT_MENU = "Menu"; 
    public const USER_INPUT_WALLET = "Wallet"; 
    public const USER_INPUT_ERRAND = "Errand"; 
    public const USER_INPUT_AIRTIME = "Airtime"; 
    public const USER_INPUT_DATA = "Data"; 
    public const USER_INPUT_TRANSACTIONS = "Transactions"; 
    public const USER_INPUT_EASY_LUNCH = "Easy lunch"; 
    public const USER_INPUT_MORE = "More"; 
    public const USER_INPUT_VENDORS = "Vendors"; 
    public const USER_INPUTS_GREETINGS = ['Hi', 'hi', 'Yo!', 'Yo', 'Howdy!', "What's up!", 'Hey!', 'Hey', 'Hello', 'hello', 'Greetings', 'xup', 'yaa', 'guy', 'babe', 'yaa ne'];

    public const USER_INPUT_MESSAGES = [
        self::USER_INPUT_MENU => "To view all services",
        self::USER_INPUT_AIRTIME => "To purchase airtime",
        self::USER_INPUT_DATA => "To purchase data bundle",
        self::USER_INPUT_WALLET => "To manage your wallet",
        self::USER_INPUT_ERRAND => "To view errands we can run for you",
        self::USER_INPUT_TRANSACTIONS => "To view recent transactions",
        self::USER_INPUT_CART => "To view shopping cart",
        self::USER_INPUT_ORDER_STATUS => "To view orders with either *PAID*, *TRANSFER* or *PAY ON DELIVERY* status",
        self::USER_INPUT_EASY_LUNCH => "To manage Easy Lunch subscription",
        self::USER_INPUT_MORE => "To find out more about our product and services",
        self::USER_INPUT_VENDORS => "View several vendors and their products",
    ];

    //Transaction purchase status
    public const TRANSACTION_STATUS_SUCCESS = 0;
    public const TRANSACTION_STATUS_INSUFFICIENT_BALANCE = -1;
    public const TRANSACTION_STATUS_UNSUCCESSFUL = 1;
    public const TRANSACTION_STATUS_PENDING = 2;

    public const TRANSACTION_STATUS = [
        self::TRANSACTION_STATUS_INSUFFICIENT_BALANCE => "Unsuccessful",
        self::TRANSACTION_STATUS_SUCCESS => "Success",
        self::TRANSACTION_STATUS_UNSUCCESSFUL => "Unsuccessful",
        self::TRANSACTION_STATUS_PENDING => "Pending",
    ];

    //Data purchase status
    public const DATA_STATUS_NOT_FOUND = -1;
    public const DATA_STATUS_FOUND = 0;

    //Admin Wallets
    public const ADMIN_WALLET_EASY_ACCESS = "Easy Access";
    public const ADMIN_WALLET_FLUTTERWAVE = "Flutterwave";


}
