<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EasyLunch extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'cost_per_week', 'cost_per_month', 'is_active'];

    public function items() : BelongsToMany {
        return $this->belongsToMany(Item::class, 'easy_lunch_items', 'easy_lunch_id', 'item_id');
    }

    public function subscription() : HasOne {
        return $this->hasOne(EasyLunchSubscribers::class);
    }
}
