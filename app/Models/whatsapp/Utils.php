<?php

namespace App\Models\whatsapp;

class Utils
{

    //User roles
    public const USER_ROLE_USER = "USER";
    public const USER_ROLE_SUPER_ADMIN = "SUPER ADMIN";
    public const USER_ROLE_ADMIN = "ADMIN";
    public const USER_ROLE_DISPATCH_RIDER = "DISPATCHER";

    //Admin events
    public const ADMIN_PROCESS_USER_ORDER = "admin-process-order";
    public const ADMIN_PROCESS_USER_ORDER_ASSIGN_DISPATCHER = "admin-process-order-assign-disptcher";
    public const ADMIN_PROCESS_USER_ORDER_DISPATCHER_RECIEVED_ADMIN = "admin-process-order-dispatcher-recieved-admin";
    public const ADMIN_PROCESS_USER_ORDER_DISPATCHER_RECIEVED_USER = "admin-process-order-dispatcher-recieved-user";
    public const ADMIN_WALLET_EVENTS = "admin-wallet-events";
    public const ADMIN_USER_ORDER_NOTIFY = "admin-user-order-notify";
    public const ADMIN_USER_ORDER_CONFIRM = "admin-user-order-confirm";
    public const ADMIN_USER_ORDER_ACCEPTED = "admin-user-order-accepted";

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
    public const BUTTONS_ADD_EASY_LUNCH_SUB = "[easylunch-monthly-add-sub]";
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
    public const ORDER_STATUS_PROCESSED = 4;
    public const ORDER_STATUS_DELIVERED_DISPATCHER = 5;
    public const ORDER_STATUS_CANCELLED = -1;
    public const ORDER_STATUS_DELIVERED = 0;
    public const ORDER_STATUS_INITIATED = 2;
    public const ORDER_STATUS_ENROUTE = 3;

    //Order types
    public const ORDER_CATEGORY_OTHERS = "OTHERS";
    public const ORDER_CATEGORY_AIRTIME = "AIRTIME PURCHASE";
    public const ORDER_CATEGORY_DATA = "DATA SUBSCRIPTION";

    public const ORDER_STATUS_MESSAGE = [
        self::ORDER_STATUS_CANCELLED => "CANCELLED",
        self::ORDER_STATUS_PROCESSING => "PROCESSING",
        self::ORDER_STATUS_DELIVERED => "DELIVERED",
        self::ORDER_STATUS_INITIATED => "INITIATED",
        self::ORDER_STATUS_ENROUTE => "ENROUTE",
        self::ORDER_STATUS_PROCESSED => "PROCESSED",
    ];

    //Order types
    public const ORDER_TYPE_EASY_LUNCH = 'ORDER-EASY-LUNCH';
    public const ORDER_TYPE_FOOD = 'ORDER-FOOD';

    //Easylunch types
    public const EASY_LUNCH_TYPE_WEEKLY = "weekly";
    public const EASY_LUNCH_TYPE_MONTHLY = "monthly";
    public const EASYLUNCH_CURRENT = "CURRENT";

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
    public const USER_INPUT_CONTACT_ADMIN = "Contact Admin"; 
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
        self::USER_INPUT_CONTACT_ADMIN => "Chat with an admin",
    ];

    //Transaction purchase status
    public const TRANSACTION_STATUS_SUCCESS = 0;
    public const TRANSACTION_STATUS_INSUFFICIENT_BALANCE = -1;
    public const TRANSACTION_STATUS_UNSUCCESSFUL = 1;
    public const TRANSACTION_STATUS_PENDING = 2;
    public const TRANSACTION_STATUS_ENROUTE = 4;

    public const TRANSACTION_STATUS = [
        self::TRANSACTION_STATUS_INSUFFICIENT_BALANCE => "Unsuccessful",
        self::TRANSACTION_STATUS_SUCCESS => "Success",
        self::TRANSACTION_STATUS_UNSUCCESSFUL => "Unsuccessful",
        self::TRANSACTION_STATUS_PENDING => "Pending",
        self::TRANSACTION_STATUS_ENROUTE => "Pending",
    ];

    //Data purchase status
    public const DATA_STATUS_NOT_FOUND = -1;
    public const DATA_STATUS_FOUND = 0;

    //Admin Wallets
    public const ADMIN_WALLET_EASY_ACCESS = "Easy Access";
    public const ADMIN_WALLET_FLUTTERWAVE = "Flutterwave";

    //payment methods 
    public const PAYMENT_METHOD_EASY_LUNCH = "EASY LUNCH";
    public const PAYMENT_METHOD_TRANSFER = "TRANSFER";
    public const PAYMENT_METHOD_WALLET = "WALLET";
    public const PAYMENT_METHOD_ON_DELIVERY = "ON DELIVERY";
    public const PAYMENT_METHOD_ONLINE = "ONLINE";

    public const EASY_BUY_4_ME_FOOTER = "@easybuy4me";

    //Regular Expressions
    public const DATA_PURCHASE_INPUT = "/(mtn|9mobile|airtel|glo|\d+[A-Za-z]{2}| \d{11})/i";
    public const DATA_PURCHASE_NETWORK_NAME = '/^(mtn|9mobile|airtel|glo|\d+[A-Za-z]+)/';
    public const AIRTIME_PURCHASE_INPUT_MATCH = '/^\d{11} (?:[1-9]\d{2,}|[1-9]\d{1,}|[1-9])$/';
    public const AIRTIME_PURCHASE_INPUT_SPLITTER = '/(\d{11}) | (?:[1-9]\d{3,}|[1-9]\d{1,}|[1-9])/';

    public const NETWORK_CODES = [
        "MTN" => ["0803", "0806", "0703", "0706", "0813", "0816", "0810", "0814", "0903", "0906", "0913", "0916", "0702", "0702", "0704"],
        "GLO" => ["0805", "0807", "0705", "0815", "0811", "0905", "0915"],
        "9MOBILE" => ["0809", "0818", "0817", "0909", "0908"],
        "AIRTEL" => ["0802", "0808", "0708", "0812", "0701", "0902", "0901", "0904", "0907", "0912"],
    ];

    public const AIRTIME_INVALID_AMOUNT = -5;


}
