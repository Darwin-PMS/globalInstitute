<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'type',
        'amount',
        'reason',
        'approved_by',
        'adjustment_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'adjustment_date' => 'date',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Enrollment::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
