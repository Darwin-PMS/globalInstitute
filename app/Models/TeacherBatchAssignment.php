<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherBatchAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'batch_id',
        'course_module_id',
        'start_date',
        'end_date',
        'assignment_type',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Trainer::class, 'teacher_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Batch::class);
    }

    public function courseModule(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CourseModule::class);
    }
}
