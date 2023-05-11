<?php

namespace App\Models\whatsapp;

class Utils
{

    //Possible origins of requests
    public const ORIGIN_WHATSAPP = "whatsapp";
    public const ORIGIN_FACEBOOK = "facebook";
    public const ORIGIN_TWITTER = "twitter";
    public const ORIGIN_TELEGRAM = "telegram";

    //Expected type of messages from whatsapp
    public const BUTTON_REPLY = "button_reply";
    public const LIST_REPLY = "list_reply";
    public const TEXT = "text";
    public const INTERACTIVE = "interactive";
    public const MEDIA_IMAGE = "image";

    //Expected greetings
    public const GREETINGS_FROM_CUSTOMER = ['hi', "what's up", 'xup', 'hey', 'hello', 'hey bot!', 'greetings', 'whatsup', 'watsup', 'sup', 'wassup', 'wasup'];
    public const GREETINGS_TO_CUSTOMER = ['Hi', 'Yo!', 'Howdy!', "What's up!", 'Hey!', 'Hello', 'Greetings'];

}
