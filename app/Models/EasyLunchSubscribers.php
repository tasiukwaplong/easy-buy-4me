<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EasyLunchSubscribers extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'easy_lunch_id', 'package_type', 'amount', 'orders_remaining', 'last_used', 'last_order', 'paid'];
}
