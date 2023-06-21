<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id', 
        'quantity',
        'order_id' 
    ];

    public function order() : BelongsTo {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function item() : BelongsTo {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
