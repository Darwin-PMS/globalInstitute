<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    protected $fillable = [
        'course_id',
        'batch_name',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'days',
        'capacity',
        'available_seats',
        'trainer_id',
        'status',
        'batch_code',
        'room_id',
        'notes',
        'current_strength',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'string',
        'end_time' => 'string',
        'capacity' => 'integer',
        'available_seats' => 'integer',
        'current_strength' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Course::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Trainer::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Room::class);
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(\App\Models\Admission::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(\App\Models\BatchSchedule::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(\App\Models\Enrollment::class);
    }
}
