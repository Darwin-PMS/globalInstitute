<?php
namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case Partial = 'partial';
    case Absent = 'absent';
    case Excused = 'excused';

    public function label(): string
    {
        return match($this) {
            self::Present => 'Present',
            self::Late => 'Late',
            self::Partial => 'Partial',
            self::Absent => 'Absent',
            self::Excused => 'Excused',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Present => 'badge-success',
            self::Late => 'badge-warning',
            self::Partial => 'badge-info',
            self::Absent => 'badge-danger',
            self::Excused => 'badge-primary',
        };
    }
}