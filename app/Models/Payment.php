<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'student_id',
        'enrollment_id',
        'fee_installment_id',
        'amount',
        'payment_date',
        'payment_method',
        'transaction_id',
        'gateway',
        'reference_number',
        'upi_id',
        'status',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Enrollment::class);
    }

    public function feeInstallment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\FeeInstallment::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(\App\Models\Receipt::class);
    }
}
