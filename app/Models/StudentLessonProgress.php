<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentLessonProgress extends Model
{
    protected $table = 'student_lesson_progress';

    protected $fillable = [
        'student_id', 'enrollment_id', 'lesson_id', 'started_at',
        'completed_at', 'progress_percentage',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percentage' => 'integer',
    ];

    public function student() { return $this->belongsTo(Student::class); }
    public function enrollment() { return $this->belongsTo(Enrollment::class); }
    public function lesson() { return $this->belongsTo(Lesson::class); }
}
