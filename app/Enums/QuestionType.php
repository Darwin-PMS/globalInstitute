<?php
namespace App\Enums;

enum QuestionType: string
{
    case Mcq = 'mcq';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';

    public function label(): string
    {
        return match($this) {
            self::Mcq => 'Multiple Choice',
            self::TrueFalse => 'True/False',
            self::ShortAnswer => 'Short Answer',
        };
    }
}