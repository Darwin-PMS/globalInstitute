<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructureItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_structure_id',
        'fee_type',
        'name',
        'amount',
        'mandatory',
        'sequence',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'mandatory' => 'boolean',
        'sequence' => 'integer',
    ];

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(\App\Models\FeeStructure::class);
    }
}
