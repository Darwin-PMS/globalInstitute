<?php
namespace App\Enums;

enum ClassStatus: string
{
    case Scheduled = 'scheduled';
    case StartingSoon = 'starting_soon';
    case Live = 'live';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Postponed = 'postponed';

    public function label(): string
    {
        return match($this) {
            self::Scheduled => 'Scheduled',
            self::StartingSoon => 'Starting Soon',
            self::Live => 'Live',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Postponed => 'Postponed',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Scheduled, self::StartingSoon => 'badge-info',
            self::Live => 'badge-success',
            self::Completed => 'badge-primary',
            self::Cancelled => 'badge-danger',
            self::Postponed => 'badge-warning',
        };
    }
}