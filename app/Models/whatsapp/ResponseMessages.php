<?php

namespace App\Models\whatsapp;

use App\Models\User;
use App\Models\whatsapp\messages\InteractiveSendMessage;
use App\Models\whatsapp\messages\partials\BodyText;
use App\Models\whatsapp\messages\partials\interactive\Action;
use App\Models\whatsapp\messages\partials\interactive\Header;
use App\Models\whatsapp\messages\partials\interactive\Interactive;
use App\Models\whatsapp\messages\partials\interactive\Row;
use App\Models\whatsapp\messages\partials\interactive\Section;
use App\Models\whatsapp\messages\SendMessage;
use App\Models\whatsapp\messages\TextSendMessage;

class ResponseMessages
{

    public static function welcomeMessage(string $customerPhoneNumber, bool $urlPreview, bool $withRef): SendMessage
    {

        $greetingIndex = array_rand(Utils::GREETINGS_TO_CUSTOMER);
        $greeting = Utils::GREETINGS_TO_CUSTOMER[$greetingIndex];

        $body = $withRef ? "$greeting,\nMy name is *EasyBuy4Me*. I am a BOT. I can help you with your physical and digital errands.\nKindly provide your email to begin your registration." : "$greeting,\nMy name is *EasyBuy4Me*. I am a BOT. I can help you with your physical and digital errands.\nKindly enter a referral code if you have one or provide your email to begin your registration.";
        return self::textMessage($body, $customerPhoneNumber, $urlPreview);
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
    public static function enterNameMessage(string $email, string $customerPhoneNumber, bool $urlPreview): TextSendMessage
    {
        $body = "Great, I have registered your email as *$email*.\nKindly Provide your full name in the order: FirstName LastName E.G: John Doe.";
        return self::textMessage($body, $customerPhoneNumber, $urlPreview);
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
    public static function dashboardMessage(User $user) : InteractiveSendMessage
    {
        $userWallets = $user->wallets;

        $totalWalletBalance = $userWallets->reduce(function ($initial, $wallet) {
            return $initial + $wallet->balance;
        }, 0);

        $bodyContent = "[Image]\nWELCOME TO EASYBUY4ME\nWALLET BALANCE: ₦$totalWalletBalance\nI am your one-stop plug for both digital and physical errands.\nI can't express how happy I am to assist you.\nHere are a list of things I can help you do:\n1. Help you purchase data (MTN, GLO, AIRTEL etc) for as low as NGN228 for 1GB\n2. Help you purchase Airtime 2% commission\n3. Run and track your physical goods errands\n4. Send you daily updates: News, sports and Trends from Twitter (Coming soon)\n5. Manage your wallet";

        //Build all rows 
        $selectionRows = [];

        foreach (Utils::DASHBOARD_MENU as $id => $description) {
            $row = new Row($id, ucwords(str_replace("]", "", str_replace("[", "", str_replace("-", " ", $id)))), $description);
            array_push($selectionRows, $row);
        }

        //Build section
        $section = new Section("Our Services", $selectionRows);

        //Build Action
        $action = new Action("CHOOSE SERVICE", array($section));

        //Build a footer
        $footer = ['text' => 'Tap to select item'];

        $body = ['text' => $bodyContent];

        $header = new Header(Utils::TEXT, "Welcome");

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
