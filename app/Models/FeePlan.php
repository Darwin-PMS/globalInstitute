<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'plan_name',
        'total_amount',
        'total_installments',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_installments' => 'integer',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Enrollment::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(\App\Models\FeeInstallment::class);
    }
}
