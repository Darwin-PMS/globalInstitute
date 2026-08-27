<?php

namespace App\Domains\OnlineLearning\Room\Enums;

/**
 * Lifecycle of a single Live Class Room.
 *
 * This is the room's OWN state, tracked independently of the subject's status
 * (e.g. online_classes.status). A room can be open for its lobby while the
 * underlying class is still "scheduled", and it stays "ended" for its own
 * post-class experience regardless of what else changes upstream.
 */
enum RoomState: string
{
    case Scheduled = 'scheduled';
    case Open = 'open';
    case Live = 'live';
    case Ended = 'ended';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Open => 'Lobby Open',
            self::Live => 'Live',
            self::Ended => 'Ended',
            self::Cancelled => 'Cancelled',
            self::Archived => 'Archived',
        };
    }

    /** Whether participants may be inside the room at all. */
    public function isEnterable(): bool
    {
        return in_array($this, [self::Open, self::Live], true);
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Ended, self::Cancelled, self::Archived], true);
    }

    /**
     * Allowed forward transitions. Cancelling is permitted from any state that
     * has not already finished.
     */
    public function canTransitionTo(self $next): bool
    {
        if ($next === self::Cancelled) {
            return ! $this->isFinished();
        }

        return match ($this) {
            self::Scheduled => in_array($next, [self::Open, self::Live, self::Ended], true),
            self::Open => in_array($next, [self::Live, self::Ended], true),
            self::Live => $next === self::Ended,
            self::Ended => $next === self::Archived,
            self::Cancelled, self::Archived => false,
        };
    }
}
