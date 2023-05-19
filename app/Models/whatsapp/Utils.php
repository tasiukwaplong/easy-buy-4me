<?php

namespace App\Models\whatsapp;

class Utils
{

    //Possible origins of requests
    public const ORIGIN_WHATSAPP = "whatsapp";
    public const ORIGIN_FACEBOOK = "facebook";
    public const ORIGIN_TWITTER = "twitter";
    public const ORIGIN_TELEGRAM = "telegram";
    public const ORIGIN_VERIFICATION = "email_verify";

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
    public const GREETINGS_FROM_CUSTOMER = ['hi', "what's up", 'xup', 'hey', 'hello', 'hey bot!', 'greetings', 'whatsup', 'watsup', 'sup', 'wassup', 'wasup'];
    public const GREETINGS_TO_CUSTOMER = ['Hi', 'Yo!', 'Howdy!', "What's up!", 'Hey!', 'Hello', 'Greetings'];


    //Dashboard menu
    public const DASHBOARD_MENU = [
        self::MY_WALLET => ["Manage my wallet", "See balance, fund wallet"],
        self::ERRAND => ['Run a physical errand', "Let's get you your grocery and lunch from your favorite restaurant"],
        self::DATA => ['Purchase Data', "Data (Internet subscription): Purchase data at very affordable rate"],
        self::AIRTIME => ['Purchase Airtime', "Airtime (VTU): Purchase airtime at 2% commision"],
        self::MORE => ['Find out more', "Learn about EasyBuy4Me, pricing, FAQ and more"]
        
        // self::EASY_LUNCH => "For as low as NGN4,900 subscription, you can get lunch delivered to your home/office every day of the week",
    ];

    //Reply Buttons
    public const BUTTONS_START_AGAIN = '[button-start-again]';
    public const BUTTONS_GUEST_BEGIN = '[button-guest-begin]';
    public const BUTTONS_VIEW_SERVICES = '[button-view-services]';
    public const BUTTONS_GO_TO_DASHBOARD = '[button-go-to-dashboard]';
    public const BUTTONS_ORDER_ADD_ITEM = '[button-order-add-item]';
    public const BUTTONS_ORDER_ADD_MORE_ITEM = '[button-order-add-more-item]';
    public const BUTTONS_ORDER_CHECKOUT = '[button-order-checkout]';

    //List reply IDs
    public const ERRAND = "[errand]";
    public const MY_WALLET = "[my-wallet]";
    public const DATA = "[data]";
    public const AIRTIME = "[airtime]";
    public const MORE = "[more]";
    public const EASY_LUNCH = "[easy-lunch]";

    //Image urls
    public const ERRAND_BANNAER = "https://awazone.s3.amazonaws.com/public/errands.jpg";
    public const SERVICES_BANNAER = "https://awazone.s3.amazonaws.com/public/errands2.jpg";

    //Order statuses
    public const ORDER_STATUS_PROCESSING = 1;
    public const ORDER_STATUS_CANCELLED = -1;
    public const ORDER_STATUS_DELIVERED = 0;
    public const ORDER_STATUS_INITIATED = 2;

}
