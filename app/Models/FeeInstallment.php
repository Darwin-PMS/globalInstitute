<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_plan_id',
        'installment_number',
        'amount',
        'due_date',
        'paid_amount',
        'balance',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'installment_number' => 'integer',
    ];

    public function feePlan(): BelongsTo
    {
        return $this->belongsTo(\App\Models\FeePlan::class);
    }
}
