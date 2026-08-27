<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $fillable = [
        'quiz_id', 'student_id', 'enrollment_id', 'score', 'total_marks',
        'is_passed', 'started_at', 'completed_at', 'time_taken', 'attempt_number',
    ];

    protected $casts = [
        'score' => 'integer',
        'total_marks' => 'integer',
        'is_passed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'time_taken' => 'integer',
        'attempt_number' => 'integer',
    ];

    public function quiz() { return $this->belongsTo(Quiz::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function enrollment() { return $this->belongsTo(Enrollment::class); }
    public function answers() { return $this->hasMany(QuizAnswer::class); }
}
