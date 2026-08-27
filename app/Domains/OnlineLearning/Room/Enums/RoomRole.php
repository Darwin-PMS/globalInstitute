<?php

namespace App\Domains\OnlineLearning\Room\Enums;

/**
 * A participant's role *inside a room*, resolved server-side per room.
 *
 * Deliberately separate from the application's global roles: the same person
 * can host one room and observe another.
 */
enum RoomRole: string
{
    case Host = 'host';
    case Moderator = 'moderator';
    case Participant = 'participant';
    case Observer = 'observer';

    public function label(): string
    {
        return match ($this) {
            self::Host => 'Host',
            self::Moderator => 'Moderator',
            self::Participant => 'Participant',
            self::Observer => 'Observer',
        };
    }

    /** Hosts and moderators drive the room. */
    public function canControlRoom(): bool
    {
        return in_array($this, [self::Host, self::Moderator], true);
    }

    public function canPostAnnouncement(): bool
    {
        return $this->canControlRoom();
    }

    public function canMarkAttendance(): bool
    {
        return $this->canControlRoom();
    }

    /** Observers (e.g. an auditing admin) watch without being counted. */
    public function countsTowardAttendance(): bool
    {
        return $this === self::Participant;
    }
}
