<?php
namespace App\Enums;

enum SubmissionStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Late = 'late';
    case Reviewed = 'reviewed';
    case Returned = 'returned';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending',
            self::Submitted => 'Submitted',
            self::Late => 'Late',
            self::Reviewed => 'Reviewed',
            self::Returned => 'Returned',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Pending => 'badge-warning',
            self::Submitted => 'badge-info',
            self::Late => 'badge-danger',
            self::Reviewed => 'badge-success',
            self::Returned => 'badge-primary',
        };
    }
}