<?php

namespace App\utils;

class Helpers {

    public const ERRAND_STATUS = [
        'ongoing' => 0,
        'canceled' => -1,
        'delivered' => 1
    ];

    public const TRANSACTION_STATUS = [
        'PAID',
        'PENDING',
        'CANCELED'
    ];

}