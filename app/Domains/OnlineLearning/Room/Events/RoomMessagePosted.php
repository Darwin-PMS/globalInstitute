<?php

namespace App\Domains\OnlineLearning\Room\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A chat message or announcement was posted. See RoomStateChanged for the broadcast note. */
class RoomMessagePosted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $roomId,
        public int $messageId,
        public string $type,
    ) {}

    public function channelName(): string
    {
        return 'class-room.' . $this->roomId;
    }
}
