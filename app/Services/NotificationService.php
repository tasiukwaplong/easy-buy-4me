<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class NotificationService
{

    public static function sendEmail($recipient, Mailable $mailable)
    {
        Mail::to($recipient)->send($mailable);
    }
}
