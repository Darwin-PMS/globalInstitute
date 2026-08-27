<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounsellingSession extends Model
{
    protected $fillable = [
        'enquiry_id',
        'counsellor_id',
        'student_goal',
        'recommended_course',
        'alternative_course',
        'career_objective',
        'current_qualification',
        'skill_level',
        'recommended_batch',
        'counselling_notes',
        'counsellor_recommendation',
        'session_date',
        'status',
    ];

    protected $casts = [
        'session_date' => 'datetime',
    ];

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function counsellor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counsellor_id');
    }
}