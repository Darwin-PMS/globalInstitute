<?php
namespace App\Enums;

enum ClassType: string
{
    case Online = 'online';
    case Hybrid = 'hybrid';
    case Recorded = 'recorded';

    public function label(): string
    {
        return match($this) {
            self::Online => 'Online',
            self::Hybrid => 'Hybrid',
            self::Recorded => 'Recorded',
        };
    }
}