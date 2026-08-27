<?php
namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case AcademicManager = 'academic_manager';
    case Teacher = 'teacher';
    case Student = 'student';
    case ContentManager = 'content_manager';
    case Support = 'support';

    public function label(): string
    {
        return match($this) {
            self::Admin => 'Admin',
            self::AcademicManager => 'Academic Manager',
            self::Teacher => 'Teacher',
            self::Student => 'Student',
            self::ContentManager => 'Content Manager',
            self::Support => 'Support',
        };
    }
}