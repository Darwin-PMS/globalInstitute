<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_number',
        'student_id',
        'course_id',
        'batch_id',
        'admission_id',
        'enrollment_date',
        'start_date',
        'expected_end_date',
        'fee_structure_id',
        'total_fee',
        'discount_amount',
        'discount_type',
        'discount_reason',
        'final_fee',
        'total_paid',
        'balance',
        'fee_status',
        'status',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'start_date' => 'date',
        'expected_end_date' => 'date',
        'total_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_fee' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Course::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Batch::class);
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admission::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(\App\Models\FeeStructure::class);
    }

    public function feePlans(): HasMany
    {
        return $this->hasMany(\App\Models\FeePlan::class);
    }

    public function installments(): HasManyThrough
    {
        return $this->hasManyThrough(\App\Models\FeeInstallment::class, \App\Models\FeePlan::class);
    }
}
