<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enquiry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'whatsapp_number',
        'email',
        'age',
        'gender',
        'qualification',
        'current_occupation',
        'city',
        'course_id',
        'selected_course',
        'selected_batch',
        'selected_course_fee',
        'discount_amount',
        'final_fee',
        'expected_admission_date_field',
        'subject',
        'preferred_time',
        'preferred_batch',
        'preferred_duration',
        'budget_range',
        'expected_admission_date',
        'career_goal',
        'previous_experience',
        'message',
        'source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'landing_page',
        'campaign',
        'status',
        'lead_status',
        'lead_score',
        'qualification_status',
        'notes',
        'counselling_notes',
        'recommended_course',
        'alternative_course',
        'counsellor_recommendation',
        'assigned_to',
        'assigned_counsellor_id',
        'last_contacted_at',
        'next_follow_up_at',
        'documents_received',
        'payment_received',
        'admission_form_completed',
    ];

    protected function casts(): array
    {
        return [
            'expected_admission_date_field' => 'date',
            'last_contacted_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'documents_received' => 'boolean',
            'payment_received' => 'boolean',
            'admission_form_completed' => 'boolean',
            'selected_course_fee' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'final_fee' => 'decimal:2',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
