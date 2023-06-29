<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Errand extends Model
{
    use HasFactory;

    protected $fillable = [
        'destination_phone',
        'dispatcher',
        'delivery_address',
        'delivery_fee',
        'status',
        'order_id'
    ];

    public function order() : BelongsTo {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
