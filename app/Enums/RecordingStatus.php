<?php
namespace App\Enums;

enum RecordingStatus: string
{
    case NotAvailable = 'not_available';
    case Processing = 'processing';
    case Available = 'available';
    case Failed = 'failed';
    case Archived = 'archived';

    public function label(): string
    {
        return match($this) {
            self::NotAvailable => 'Not Available',
            self::Processing => 'Processing',
            self::Available => 'Available',
            self::Failed => 'Failed',
            self::Archived => 'Archived',
        };
    }
}