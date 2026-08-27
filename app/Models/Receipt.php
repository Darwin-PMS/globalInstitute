<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'payment_id',
        'student_id',
        'enrollment_id',
        'amount_paid',
        'previous_balance',
        'remaining_balance',
        'payment_method',
        'transaction_id',
        'payment_date',
        'received_by',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'previous_balance' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Payment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Enrollment::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by');
    }
}
