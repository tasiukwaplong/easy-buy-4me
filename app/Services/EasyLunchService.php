<?php

namespace App\Services;

use App\Models\EasyLunchSubscribers;
use App\Models\User;
use DateTime;

class EasyLunchService
{

    /**
     * This function checks if a user has an active 
     * Easylunch subscription and returns same, 
     * else it creates a new one and return it
     *
     * @param [type] $type can either be weekly or monthly
     * @param [type] $userId the Id of this user
     * @param [type] $easyLunchId the choosen easylunch package
     * @param [type] $amount amount to be paid
     * @return EasyLunchSubscribers 
     */
    public function subscribeUser($type, $userId, $easyLunchId, $amount) : EasyLunchSubscribers
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

    /**
     * This functions checks if a user has an active 
     * esay lunch subscrition package
     *
     * @param User $user
     * @return boolean
     */
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
