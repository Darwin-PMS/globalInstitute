<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'gateway_transaction_id',
        'gateway_order_id',
        'gateway_response',
        'status',
        'verified_at',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'verified_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Payment::class);
    }
}
