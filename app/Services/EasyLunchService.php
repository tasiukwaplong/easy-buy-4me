<?php

namespace App\Services;

use App\Models\EasyLunch;
use App\Models\EasyLunchSubscribers;
use App\Models\Order;
use App\Models\User;
use App\Models\whatsapp\messages\partials\interactive\Row;
use App\Models\whatsapp\messages\partials\interactive\Section;
use App\Models\whatsapp\Utils;
use Illuminate\Database\Eloquent\Collection;

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
    public function subscribeUser($type, $userId, $easyLunchId, $amount): EasyLunchSubscribers
    {
        $ordersRemaining = ($type == Utils::EASY_LUNCH_TYPE_WEEKLY) ? 5 : 20;

        $easyLunchSubscriber = EasyLunchSubscribers::where(['user_id' => $userId, 'easy_lunch_id' => $easyLunchId])->where('orders_remaining', 0)->first();

        if ($easyLunchSubscriber) {

            $easyLunchSubscriber->update([
                'amount' => $amount,
                'orders_remaining' => $ordersRemaining
            ]);
        } 
        
        else {

            $easyLunchSubscriber = EasyLunchSubscribers::create([
                'user_id' => $userId,
                'easy_lunch_id' => $easyLunchId,
                'package_type' => $type,
                'amount' => $amount,
                'orders_remaining' => $ordersRemaining
            ]);
        }


        return $easyLunchSubscriber;
    }

    /**
     * This functions checks if a user has an active 
     * esay lunch subscrition package
     *
     * @param User $user
     * @return boolean
     */
    public function isActive(User $user)
    {
        //Check if this user hase easylunch subscription
        $easyLunchSubscriber = EasyLunchSubscribers::where('user_id', $user->id)->first();

        if ($easyLunchSubscriber) {

            $now = date("Y-m-d");
            $lastUsed = date("Y-m-d", strtotime($easyLunchSubscriber->last_used));

            return  $easyLunchSubscriber and
                $easyLunchSubscriber->orders_remaining > 0 and
                $easyLunchSubscriber->paid and
                $now > $lastUsed;
        }

        return false;
    }


    /**
     * Function to get easylunch subscriptions for user
     *
     * @param User $user
     * @return Collection
     */
    public function getSubscriptions(User $user) : Collection
    {
        //Check if this user hase easylunch subscription
        $easyLunchSubscriptions = EasyLunchSubscribers::where('user_id', $user->id)->get();

        return $easyLunchSubscriptions->filter(function ($sub) {
            return $sub->orders_remaining > 0 and $sub->paid;
        });
    }


    public static function getEasyLunchItems(EasyLunch $easyLunch)
    {
        return implode(", ", $easyLunch->items->map(function ($item) {
            return $item->item_name;
        })->all());
    }

    public static function easylunchHome()
    {
        $bodyContent = "*EasyLaunch* is a subscription package designed to provide customers with a meal 🍲 choice option within the 5 working days, ensuring that they receive a daily meal of their choice.\nThere are two package options available for customers\n\n*Weekly Package*\n*Monthly Package*\n\n";
        $bodyContent .= "Each of this package comes with a speciic meal content and customers are expected to suscribe to the package with their choice of preferred meal for the day from the list available to them on the menu.\nYou can also subscribe for more than one package.\n\nTap the *EASY LUNCH* below to subscribe";
        
        return array('sections' => self::getAllSections(null), 'body' => $bodyContent);
    }

    public static function getEasylunchPackages(User $user) {
        $bodyContent = "Tap *PACKAGES* to view all available package";
        return array('sections' => self::getAllSections($user), 'body' => $bodyContent);
    }

    private static function getAllSections($user) {

        $easyLunches = '';

        if($user) {

            $userPackages = EasyLunchSubscribers::where('user_id', $user->id)->where('orders_remaining', '>', 0)->get()->map(function($sub) {return $sub->easy_lunch_id; })->all();

            $easyLunches = EasyLunch::whereNotIn('id', $userPackages)->get();

        }
        else $easyLunches = EasyLunch::all();

       
        
        $weeklyPackages = [];
        $monthlyPackages = [];

        foreach ($easyLunches as $easyLunch) {

            $description = self::getEasyLunchItems($easyLunch);

            if ($easyLunch->cost_per_week > 0) {

                array_push($weeklyPackages, new Row(
                    "[subscribe-easylunch-weekly:$easyLunch->id:$easyLunch->cost_per_week]",
                    ucwords($easyLunch->name . "(N$easyLunch->cost_per_week)"),
                    "$description"
                ));
            }

            if ($easyLunch->cost_per_month > 0)

                array_push($monthlyPackages, new Row(
                    "[subscribe-easylunch-monthly:$easyLunch->id:$easyLunch->cost_per_month]",
                    ucwords($easyLunch->name . "(N$easyLunch->cost_per_month)"),
                    "$description"
                ));
        }

        //Build section
        $weeklySection = new Section("Weekly Packages", $weeklyPackages);
        $monthlySection = new Section("Monthly Packages", $monthlyPackages);

        $allSections = array();

        if (count($weeklySection->rows) > 0)
            array_push($allSections, $weeklySection);

        if (count($monthlySection->rows) > 0)
            array_push($allSections, $monthlySection);

        return $allSections;
    }

    public static function getEasylunches($subscriptions) {

        $sections = [];

        $weeklyPackages = [];
        $monthlyPackages = [];
        $actions = [];

        foreach ($subscriptions as $subscription) {

            $easylunchId = $subscription->easylunch->id;
            $name = $subscription->easylunch->name;
            $description = $subscription->easylunch->description;

            if (strcasecmp($subscription->package_type, "weekly") === 0) {
                array_push($weeklyPackages, new Row("[easylunch-weekly:$easylunchId]", $name, $description));
            }

            if (strcasecmp($subscription->package_type, "monthly") === 0) {
                array_push($monthlyPackages, new Row("[easylunch-monthly:$easylunchId]", $name, $description));
            }
        }

        //Add an action button
        array_push($actions, new Row(Utils::BUTTONS_ADD_EASY_LUNCH_SUB, "New Subscription", "Subscribe to a new Easy Lunch Package"));

         //Build section
         $weeklySection = new Section("Weekly Packages", $weeklyPackages);
         $monthlySection = new Section("Monthly Packages", $monthlyPackages);
         $actionSection = new Section('Actions', $actions);

         if (count($weeklySection->rows) > 0)
            array_push($sections, $weeklySection);

        if (count($monthlySection->rows) > 0)
            array_push($sections, $monthlySection);
        
        array_push($sections, $actionSection);

        return $sections;
    }

    public function isUsed(User $user, EasyLunch $easyLunch) : bool {
        $today = date('Y-m-d');
        return $easyLunch->subscription->last_used === $today;
    }

    public static function isEasyLunchSub(Order $order) {
        return substr($order->description, 0, 18) === "Easy lunch package";
    }

    public static function useEasyLunchSub(User $user, Order $order, $paymentMethod) {

        $easylunchsub = EasyLunchSubscribers::where('user_id', $user->id)->first();
        $easylunchsub->orders_remaining -= 1;
        $easylunchsub->last_used = date("Y-m-d");
        $easylunchsub->last_order = $order->order_id;
        $easylunchsub->save();

        $order->status = Utils::ORDER_STATUS_PROCESSING;
        $transactionService = new TransactionService();
        $transactionService->updateTransaction($order->transaction, ['status' => Utils::TRANSACTION_STATUS_SUCCESS, 'method' => $paymentMethod]);            

        return $order;
    }
}
