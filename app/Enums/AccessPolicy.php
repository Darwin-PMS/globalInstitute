<?php
namespace App\Enums;

enum AccessPolicy: string
{
    case EnrolledOnly = 'enrolled';
    case Public = 'public';
    case FreePreview = 'free_preview';

    public function label(): string
    {
        return match($this) {
            self::EnrolledOnly => 'Enrolled Students Only',
            self::Public => 'Public',
            self::FreePreview => 'Free Preview',
        };
    }
}