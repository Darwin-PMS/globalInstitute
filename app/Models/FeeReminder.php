<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'fee_installment_id',
        'reminder_type',
        'message',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Enrollment::class);
    }

    public function feeInstallment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\FeeInstallment::class);
    }
}
