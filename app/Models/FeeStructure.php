<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'name',
        'effective_from',
        'effective_to',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Course::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(\App\Models\FeeStructureItem::class);
    }
}
