<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EasyLunch extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'cost_per_week', 'cost_per_month', 'is_active'];
}
