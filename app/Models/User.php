<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'first_name',
        'last_name',
        'role',
        'email',
        'temp_email',
        'referral_code',
        'referred_by',
    ];

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function monnifyAccounts(): HasMany
    {
        return $this->hasMany(MonnifyAccount::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function easylunchsSubscribers() : BelongsToMany {
        return $this->belongsToMany(EasyLunchSubscribers::class, 'users_easylunch_subscriptions', 'user_id', 'easy_lunch_subscribers_id');
    }
}
