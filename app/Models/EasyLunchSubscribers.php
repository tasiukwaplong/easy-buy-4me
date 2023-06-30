<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EasyLunchSubscribers extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'easy_lunch_id', 'package_type', 'amount', 'orders_remaining', 'last_used', 'last_order', 'current', 'paid'];

    public function easylunchsSubscribers() : BelongsToMany {
        return $this->belongsToMany(User::class, 'users_easylunch_subscriptions', 'easy_lunch_subscribers_id', 'user_id');
    }

    public function easylunch() {
        return $this->belongsTo(EasyLunch::class, 'easy_lunch_id');
    }
}
