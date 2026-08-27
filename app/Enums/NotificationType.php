<?php
namespace App\Enums;

enum NotificationType: string
{
    case ClassScheduled = 'class_scheduled';
    case ClassUpdated = 'class_updated';
    case ClassCancelled = 'class_cancelled';
    case ClassStartingSoon = 'class_starting_soon';
    case ClassReminder = 'class_reminder';
    case RecordingAvailable = 'recording_available';
    case AssignmentCreated = 'assignment_created';
    case AssignmentDue = 'assignment_due';
    case AssignmentEvaluated = 'assignment_evaluated';
    case QuizAvailable = 'quiz_available';
    case QuizCompleted = 'quiz_completed';
    case General = 'general';

    public function label(): string
    {
        return match($this) {
            self::ClassScheduled => 'Class Scheduled',
            self::ClassUpdated => 'Class Updated',
            self::ClassCancelled => 'Class Cancelled',
            self::ClassStartingSoon => 'Class Starting Soon',
            self::ClassReminder => 'Class Reminder',
            self::RecordingAvailable => 'Recording Available',
            self::AssignmentCreated => 'Assignment Created',
            self::AssignmentDue => 'Assignment Due',
            self::AssignmentEvaluated => 'Assignment Evaluated',
            self::QuizAvailable => 'Quiz Available',
            self::QuizCompleted => 'Quiz Completed',
            self::General => 'General',
        };
    }
}