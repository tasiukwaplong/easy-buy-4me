<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Newsletter\Facades\Newsletter;

class NotificationService
{

    public static function sendEmail($recipient, $subject, $email) {

        $response = Http::post(env('GSUITE_URL'), ['sk' => env('GSUITE_SECRET_KEY'), 'email' => $recipient, "subject" => $subject, "body" => $email]);
        return ($response->successful()) ? $response->json()['errored'] : false;
    }

    public static function verificationEmail($name, $veriToken, $veriUrl) {

        $body = "Hello $name.<br><br>Thank you for creating account with <strong>EasyBuy4me</strong><br><br>Your Verification Token is <strong>$veriToken</strong><br><br>Kindly reply with this token on the bot or click on the button below to complete your registration:<br><br>";
        $body .= "<a href='$veriUrl' style='padding: 8px 20px; outline: none; text-decoration: none; font-size: 16px; letter-spacing: 0.5px; transition: all 0.3s; font-weight: 600; border-radius: 6px; background-color: #980f08; color: #ffffff;'>Confirm Email Address</a>";
        $body .= "<p style='color: #00000;'>This link will be active for 10 min from the time this email was sent</p>";

        return $body;

    }



}
