<?php

namespace App\Domains\OnlineLearning\Room\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A room moved through its lifecycle.
 *
 * REAL-TIME READY: these events carry ids only — no Eloquent models — so they
 * are safe to broadcast as-is. Implementing ShouldBroadcast and adding a
 * `broadcastOn()` returning a per-room private channel is the whole change
 * needed to push room updates over WebSockets. Until then the SPA polls the
 * same data through the delta endpoints, so no caller has to change.
 */
class RoomStateChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $roomId,
        public string $from,
        public string $to,
    ) {}

    /** The channel name a broadcast transport would use. */
    public function channelName(): string
    {
        return 'class-room.' . $this->roomId;
    }
}
