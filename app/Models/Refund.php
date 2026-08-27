<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'amount',
        'reason',
        'refund_date',
        'method',
        'reference_number',
        'approved_by',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refund_date' => 'date',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Payment::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
