<?php

namespace App\Domains\OnlineLearning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveClass extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_id', 'module_id', 'lesson_id', 'title', 'description',
        'teacher_id', 'class_type', 'access_policy', 'status',
        'course_provider', 'external_course_id', 'tenant_id',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Course::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CourseModule::class, 'module_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Lesson::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Trainer::class, 'teacher_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public function messages(): HasMany
    {
        return $this->hasManyThrough(ClassMessage::class, ClassSession::class);
    }
}
