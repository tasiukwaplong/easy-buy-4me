<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name', 
        'type', 
        'status', 
        'transaction_id', 
        'url',
        'invoice_no',
    ];

    function transaction () : BelongsTo {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
