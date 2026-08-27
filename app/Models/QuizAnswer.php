<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAnswer extends Model
{
    protected $fillable = [
        'attempt_id', 'question_id', 'option_id', 'answer_text',
        'is_passed', 'marks_obtained',
    ];

    protected $casts = [
        'is_passed' => 'boolean',
        'marks_obtained' => 'integer',
    ];

    public function attempt() { return $this->belongsTo(QuizAttempt::class); }
    public function question() { return $this->belongsTo(QuizQuestion::class); }
    public function option() { return $this->belongsTo(QuizOption::class); }
}
