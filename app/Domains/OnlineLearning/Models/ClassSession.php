<?php

namespace App\Domains\OnlineLearning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSession extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'live_class_id', 'session_date', 'start_time', 'end_time',
        'timezone', 'meeting_provider', 'meeting_reference',
        'join_url', 'host_url', 'meeting_id', 'meeting_password',
        'status', 'recording_status', 'created_by',
    ];

    protected $casts = [
        'session_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function liveClass(): BelongsTo
    {
        return $this->belongsTo(LiveClass::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ClassMessage::class);
    }

    public function isJoinable(): bool
    {
        return in_array($this->status, ['scheduled', 'starting_soon', 'live']) && $this->join_url;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'scheduled' => 'Scheduled',
            'starting_soon' => 'Starting Soon',
            'live' => 'Live',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'postponed' => 'Postponed',
            default => ucfirst($this->status),
        };
    }
}
