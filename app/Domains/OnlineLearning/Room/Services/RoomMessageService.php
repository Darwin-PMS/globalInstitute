<?php

namespace App\Domains\OnlineLearning\Room\Services;

use App\Domains\OnlineLearning\Room\Enums\RoomEventType;
use App\Domains\OnlineLearning\Room\Enums\RoomMessageType;
use App\Domains\OnlineLearning\Room\Enums\RoomRole;
use App\Domains\OnlineLearning\Room\Events\RoomMessagePosted;
use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use App\Domains\OnlineLearning\Room\Models\ClassRoomMessage;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Chat and announcements for one room.
 *
 * Reads are incremental by id, which is what makes this real-time-ready: the
 * client asks for "everything after id N" whether that request came from a poll
 * or from a socket telling it something arrived.
 */
class RoomMessageService
{
    public function __construct(protected RoomTimelineService $timeline) {}

    public function post(
        ClassRoom $room,
        User $user,
        string $body,
        RoomMessageType $type = RoomMessageType::Message
    ): ClassRoomMessage {
        $message = ClassRoomMessage::create([
            'class_room_id' => $room->id,
            'user_id' => $user->id,
            'type' => $type->value,
            'body' => trim($body),
        ]);

        $room->increment('message_count');

        // Announcements are part of the class record, so they reach the timeline.
        if ($type === RoomMessageType::Announcement) {
            $this->timeline->record(
                $room,
                RoomEventType::AnnouncementPosted,
                $user->id,
                'Announcement posted',
                ['message_id' => $message->id]
            );
        }

        RoomMessagePosted::dispatch($room->id, $message->id, $type->value);

        return $message;
    }

    /**
     * Messages after a given id, oldest first so the client appends.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function feed(ClassRoom $room, ?int $afterId = null, int $limit = 100): Collection
    {
        return ClassRoomMessage::where('class_room_id', $room->id)
            ->visible()
            ->after($afterId)
            ->with('user:id,name')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (ClassRoomMessage $m) => $this->present($m));
    }

    /**
     * The most recent messages, for a first paint that does not pull the whole
     * transcript. Returned oldest-first so it renders like the feed.
     */
    public function latest(ClassRoom $room, int $limit = 50): Collection
    {
        return ClassRoomMessage::where('class_room_id', $room->id)
            ->visible()
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->sortBy('id')
            ->values()
            ->map(fn (ClassRoomMessage $m) => $this->present($m));
    }

    /** Pinned announcements stay visible above the transcript. */
    public function pinned(ClassRoom $room): Collection
    {
        return ClassRoomMessage::where('class_room_id', $room->id)
            ->visible()
            ->where('is_pinned', true)
            ->with('user:id,name')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ClassRoomMessage $m) => $this->present($m));
    }

    public function setPinned(ClassRoom $room, int $messageId, bool $pinned): ?ClassRoomMessage
    {
        $message = ClassRoomMessage::where('class_room_id', $room->id)->find($messageId);

        if (! $message) {
            return null;
        }

        $message->is_pinned = $pinned;
        $message->save();

        return $message;
    }

    /**
     * Hosts hide a message rather than destroy it, so the record survives for
     * moderation review.
     */
    public function removeAsHost(ClassRoom $room, int $messageId): ?ClassRoomMessage
    {
        $message = ClassRoomMessage::where('class_room_id', $room->id)->find($messageId);

        if (! $message || $message->deleted_by_host_at !== null) {
            return null;
        }

        $message->deleted_by_host_at = now();
        $message->save();

        return $message;
    }

    /**
     * Whether this user may post the given type right now. The endpoint calls
     * this; the UI merely mirrors it.
     */
    public function canPost(ClassRoom $room, RoomRole $role, RoomMessageType $type): array
    {
        if ($type === RoomMessageType::System) {
            return ['allowed' => false, 'reason' => 'System messages cannot be posted manually.'];
        }

        if ($type === RoomMessageType::Announcement) {
            if (! $role->canPostAnnouncement()) {
                return ['allowed' => false, 'reason' => 'Only the host can post announcements.'];
            }
            if (! $room->setting('announcements_enabled', true)) {
                return ['allowed' => false, 'reason' => 'Announcements are disabled for this class.'];
            }

            return ['allowed' => true, 'reason' => null];
        }

        if (! $room->setting('chat_enabled', true)) {
            return ['allowed' => false, 'reason' => 'Chat is disabled for this class.'];
        }

        // Once a class is over its transcript is closed, but the host can still
        // leave a closing announcement.
        if ($room->isFinished() && ! $role->canControlRoom()) {
            return ['allowed' => false, 'reason' => 'This class has ended.'];
        }

        return ['allowed' => true, 'reason' => null];
    }

    protected function present(ClassRoomMessage $message): array
    {
        return [
            'id' => $message->id,
            'type' => $message->type->value,
            'body' => $message->body,
            'is_pinned' => (bool) $message->is_pinned,
            'user_id' => $message->user_id ? (int) $message->user_id : null,
            'author' => $message->user->name ?? 'System',
            'at' => $message->created_at->toIso8601String(),
        ];
    }
}
