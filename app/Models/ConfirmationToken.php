<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfirmationToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'veri_token',
        'expires_in'
    ];
}
