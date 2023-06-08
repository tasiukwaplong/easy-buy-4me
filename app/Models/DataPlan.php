<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataPlan extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'network_name', 'network_code', 'cost', 'dataplan', 'description', 'price'];
}
