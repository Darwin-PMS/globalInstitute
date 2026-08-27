<?php

namespace App\Domains\OnlineLearning\Room\Services;

use App\Domains\OnlineLearning\Room\Enums\RoomEventType;
use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use App\Domains\OnlineLearning\Room\Models\ClassRoomEvent;
use Illuminate\Support\Collection;

/**
 * The per-room activity timeline.
 *
 * Every meaningful thing that happens in a room is recorded here, which gives
 * the Activity panel its content and doubles as an audit trail.
 */
class RoomTimelineService
{
    public function record(
        ClassRoom $room,
        RoomEventType $type,
        ?int $userId = null,
        ?string $description = null,
        array $meta = []
    ): ClassRoomEvent {
        return ClassRoomEvent::create([
            'class_room_id' => $room->id,
            'user_id' => $userId,
            'type' => $type->value,
            'description' => $description ?: $type->label(),
            'meta' => $meta ?: null,
        ]);
    }

    /**
     * Timeline entries, newest last so the panel can append.
     *
     * `afterId` makes this an incremental fetch: the client sends the last id it
     * holds and receives only what is new.
     */
    public function feed(ClassRoom $room, ?int $afterId = null, int $limit = 100): Collection
    {
        return ClassRoomEvent::where('class_room_id', $room->id)
            ->after($afterId)
            ->with('user:id,name')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (ClassRoomEvent $event) => [
                'id' => $event->id,
                'type' => $event->type->value,
                'label' => $event->type->label(),
                'icon' => $event->type->icon(),
                'description' => $event->description,
                'actor' => $event->user?->name,
                'meta' => $event->meta,
                'at' => $event->created_at->toIso8601String(),
            ]);
    }
}
