<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{

    protected $fillable = [
        'course_id', 'module_id', 'lesson_id', 'class_id', 'teacher_id',
        'title', 'description', 'instructions', 'due_date', 'max_marks',
        'attachment_path', 'status',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'max_marks' => 'integer',
    ];

    public function course() { return $this->belongsTo(Course::class); }
    public function module() { return $this->belongsTo(CourseModule::class, 'module_id'); }
    public function lesson() { return $this->belongsTo(Lesson::class); }
    public function onlineClass() { return $this->belongsTo(OnlineClass::class, 'class_id'); }
    public function teacher() { return $this->belongsTo(Trainer::class, 'teacher_id'); }
    public function submissions() { return $this->hasMany(AssignmentSubmission::class); }
}
