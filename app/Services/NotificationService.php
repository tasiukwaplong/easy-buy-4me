<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class NotificationService
{

    public static function sendEmail($recipient, Mailable $mailable)
    {
        Mail::to($recipient)->send($mailable);
    }

    // public static function sendEmail($recipient, $email) {
    //     $response = Http::post(env('GSUITE_URL'), ['sk' => env('GSUITE_SECRET_KEY'), $recipient, "Message", $email]);
    //     if($response->successful()) {
    //         dd(env('GSUITE_SECRET_KEY'));
    //     }
    // }


}
