<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    protected $fillable = [
        'assignment_id', 'student_id', 'enrollment_id', 'submission_text',
        'file_path', 'submitted_at', 'status', 'marks', 'teacher_feedback',
        'evaluated_at', 'evaluated_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'marks' => 'integer',
        'evaluated_at' => 'datetime',
    ];

    public function assignment() { return $this->belongsTo(Assignment::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function enrollment() { return $this->belongsTo(Enrollment::class); }
    public function evaluator() { return $this->belongsTo(User::class, 'evaluated_by'); }
}
