<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{

    protected $fillable = [
        'course_id', 'module_id', 'lesson_id', 'teacher_id', 'title',
        'description', 'instructions', 'time_limit', 'max_attempts',
        'pass_marks', 'total_marks', 'is_published', 'status',
    ];

    protected $casts = [
        'time_limit' => 'integer',
        'max_attempts' => 'integer',
        'pass_marks' => 'integer',
        'total_marks' => 'integer',
        'is_published' => 'boolean',
    ];

    public function course() { return $this->belongsTo(Course::class); }
    public function module() { return $this->belongsTo(CourseModule::class, 'module_id'); }
    public function lesson() { return $this->belongsTo(Lesson::class); }
    public function teacher() { return $this->belongsTo(Trainer::class, 'teacher_id'); }
    public function questions() { return $this->hasMany(QuizQuestion::class)->orderBy('sequence'); }
    public function attempts() { return $this->hasMany(QuizAttempt::class); }
}
