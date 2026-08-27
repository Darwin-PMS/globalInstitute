<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'day',
        'start_time',
        'end_time',
        'room_id',
        'teacher_id',
        'status',
    ];

    protected $casts = [
        'start_time' => 'time',
        'end_time' => 'time',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Batch::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Room::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Trainer::class, 'teacher_id');
    }
}
