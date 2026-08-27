<?php

namespace App\Domains\OnlineLearning\Room\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Somebody entered or left a room. See RoomStateChanged for the broadcast note. */
class RoomPresenceChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $roomId,
        public int $userId,
        public string $action,
    ) {}

    public function channelName(): string
    {
        return 'class-room.' . $this->roomId;
    }
}
