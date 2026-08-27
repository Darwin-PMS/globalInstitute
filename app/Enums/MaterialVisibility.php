<?php
namespace App\Enums;

enum MaterialVisibility: string
{
    case Public = 'public';
    case EnrolledStudents = 'enrolled';
    case TeacherOnly = 'teacher_only';
    case AdminOnly = 'admin_only';

    public function label(): string
    {
        return match($this) {
            self::Public => 'Public',
            self::EnrolledStudents => 'Enrolled Students',
            self::TeacherOnly => 'Teachers Only',
            self::AdminOnly => 'Admin Only',
        };
    }
}