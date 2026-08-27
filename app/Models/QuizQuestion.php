<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = [
        'quiz_id', 'question_text', 'question_type', 'marks', 'sequence', 'is_required',
    ];

    protected $casts = [
        'marks' => 'integer',
        'sequence' => 'integer',
        'is_required' => 'boolean',
    ];

    public function quiz() { return $this->belongsTo(Quiz::class); }
    public function options() { return $this->hasMany(QuizOption::class)->orderBy('sequence'); }
    public function answers() { return $this->hasMany(QuizAnswer::class); }
}
