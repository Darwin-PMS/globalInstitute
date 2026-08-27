<?php
namespace App\Enums;

enum LessonType: string
{
    case Text = 'text';
    case Video = 'video';
    case Pdf = 'pdf';
    case Document = 'document';
    case LiveClass = 'live_class';
    case Assignment = 'assignment';
    case Quiz = 'quiz';
    case ExternalResource = 'external_resource';

    public function label(): string
    {
        return match($this) {
            self::Text => 'Text',
            self::Video => 'Video',
            self::Pdf => 'PDF',
            self::Document => 'Document',
            self::LiveClass => 'Live Class',
            self::Assignment => 'Assignment',
            self::Quiz => 'Quiz',
            self::ExternalResource => 'External Resource',
        };
    }
}