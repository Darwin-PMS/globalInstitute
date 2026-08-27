<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Admission extends Model
{
    protected $fillable = [
        'admission_number',
        'enquiry_id',
        'student_id',
        'course_id',
        'batch_id',
        'admission_date',
        'fee',
        'discount_amount',
        'final_fee',
        'amount_paid',
        'balance',
        'payment_status',
        'admission_status',
        'counsellor_id',
        'notes',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_fee' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Course::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Batch::class, 'batch_id');
    }

    public function counsellor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counsellor_id');
    }

    public function enrollment(): HasOne
    {
        return $this->hasOne(\App\Models\Enrollment::class);
    }
}