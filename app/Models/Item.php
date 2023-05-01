<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'item_name',
        'item_price',
        'short_description',
        'unit_name',
        'vendor_id'
    ];

    public function vendor() : BelongsTo {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}
