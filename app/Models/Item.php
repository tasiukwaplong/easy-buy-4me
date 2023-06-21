<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function orderedItem() : HasOne {
        return $this->hasOne(OrderedItem::class);
    }

    public function easylunches() : BelongsToMany {
        return $this->belongsToMany(EasyLunch::class, 'easy_lunch_items', 'item_id', 'easy_lunch_id');
    }
}
