<?php

namespace App\Models\whatsapp;

class ResponseMessages
{

    public static function welcomeMessage()
    {

        $greetingIndex = array_rand(Utils::GREETINGS_TO_CUSTOMER);
        $greeting = Utils::GREETINGS_TO_CUSTOMER[$greetingIndex];

        return "$greeting,\nMy name is EasyBuy4Me. I am a BOT. I can help you with your physical and digital errands.\nKindly provide your email to begin your registration";
    }

    public static function errorMessage()
    {
        return "Ooops!\nLooks like you entered unknown response, kindly reply with *Hi* to get started.";
    }

    public static function enterNameMessage($email)
    {
        return "Great, I have registered your email as $email.\nKindly Provide your full name in the order: FirstName LastName E.G: John Doe.";
    }
}
