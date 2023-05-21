<?php

namespace App\Services;

use App\Models\EasyLunch;
use App\Models\EasyLunchSubscribers;
use App\Models\User;
use DateTime;

class EasyLunchService
{

    public function subscribeUser($type, $userId, $easyLunchId, $amount)
    {

        $easyLunchSubscriber = EasyLunchSubscribers::where('user_id', $userId)->first() ??
            EasyLunchSubscribers::create([
                'user_id' => $userId,
                'easy_lunch_id' => $easyLunchId,
                'package_type' => $type,
                'amount' => $amount
            ]);

        return $easyLunchSubscriber;
    }

    public function isActive(User $user) {
        $easyLunchSubscriber = EasyLunchSubscribers::where('user_id', $user->id)->first();

        if($easyLunchSubscriber) {

            $expiryTime = new DateTime($easyLunchSubscriber->expiry_date);
            $now = new DateTime(now());

            return $expiryTime > $now;

        }
        
        return false;
    }
}
