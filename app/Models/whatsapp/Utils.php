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
    public const LIST_REPLY = "list_reply";
    public const TEXT = "text";
    public const INTERACTIVE = "interactive";
    public const MEDIA_IMAGE = "image";
    public const LIST = "list";

    //Expected greetings
    public const GREETINGS_FROM_CUSTOMER = ['hi', "what's up", 'xup', 'hey', 'hello', 'hey bot!', 'greetings', 'whatsup', 'watsup', 'sup', 'wassup', 'wasup'];
    public const GREETINGS_TO_CUSTOMER = ['Hi', 'Yo!', 'Howdy!', "What's up!", 'Hey!', 'Hello', 'Greetings'];


    //Dashboard menu
    public const DASHBOARD_MENU = [
        "[my-wallet]" => "See balance, fund wallet",
        // "[errand]" => "I can help you with your grocery shopping, getting lunch from your favorite restaurant",
        "[errand]" => "Let's get you your grocery and lunch from your favorite restaurant",
        "[data]" => "Data (Internet subscription): Purchase data at very affordable rate",
        // "[airtime]" => "Airtime (VTU): Purchase airtime at 2% commision for yourself or loved ones",
        "[airtime]" => "Airtime (VTU): Purchase airtime at 2% commision",
        // "[easy-lunch]" => "For as low as NGN4,900 subscription, you can get lunch delivered to your home/office every day of the week",
        // "[easy-lunch]" => "For as low as NGN4,900 subscription, you can get lunch delivered to your home/office every day of the week",
        "[more]" => "Learn about EasyBuy4Me, pricing, FAQ and more"
    ];
}
