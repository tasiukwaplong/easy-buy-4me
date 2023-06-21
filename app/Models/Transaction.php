<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_reference',
        'amount',
        'date',
        'method',
        'description',
        'status',
        'user_id',
        'order_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order() : BelongsTo {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderInvoice() : HasOne {
        return $this->hasOne(OrderInvoice::class);
    }
}
