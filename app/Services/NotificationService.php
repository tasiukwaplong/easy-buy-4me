<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Newsletter\Facades\Newsletter;

class NotificationService
{

    public static function sendEmail($recipient, $email) {

        // dd(env('EMAIL_SK'));
        $response = Http::post(env('GSUITE_URL'), ['sk' => env('EMAIL_SK'), 'email' => $recipient, "body" => $email]);
        if($response->successful()) {
            dd($response->json());
        }
    }



}
