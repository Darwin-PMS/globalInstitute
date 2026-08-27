<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'day',
        'start_time',
        'end_time',
        'available',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'time',
        'end_time' => 'time',
        'available' => 'boolean',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Trainer::class, 'teacher_id');
    }
}
