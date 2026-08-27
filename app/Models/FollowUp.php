<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    protected $fillable = [
        'enquiry_id',
        'assigned_to',
        'follow_up_date',
        'follow_up_time',
        'follow_up_type',
        'reminder',
        'notes',
        'outcome',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
        'follow_up_time' => 'time',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}