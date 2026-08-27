<?php

namespace App\Domains\OnlineLearning\Room\Enums;

enum RoomMessageType: string
{
    case Message = 'message';
    case Announcement = 'announcement';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Message => 'Message',
            self::Announcement => 'Announcement',
            self::System => 'System',
        };
    }
}
