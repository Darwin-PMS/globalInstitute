<?php

namespace App\Http\Controllers\Api\OnlineLearning\Room;

use App\Domains\OnlineLearning\Room\Enums\RoomMessageType;
use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chat and announcements for a room.
 *
 * The read endpoint is a delta: the client sends the newest id it holds and gets
 * back only what followed. That is what makes swapping polling for a socket a
 * transport change rather than a rewrite.
 */
class RoomMessageApiController extends RoomController
{
    /**
     * GET /api/online-learning/rooms/{room}/messages?after=<id>
     *
     * Without `after` this returns the most recent page, so opening the panel
     * never pulls an entire transcript.
     */
    public function index(Request $request, ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        if (! $room->setting('chat_enabled', true) && ! $ctx['role']->canControlRoom()) {
            return $this->forbidden('Chat is disabled for this class.');
        }

        $limit = (int) config('online-learning.room.feed_page_size', 100);

        if ($request->filled('after')) {
            $messages = $this->messages->feed($room, (int) $request->get('after'), $limit);
            $isDelta = true;
        } else {
            $messages = $this->messages->latest($room, 50);
            $isDelta = false;
        }

        return $this->ok(
            [
                'messages' => $messages->values(),
                'pinned' => $isDelta ? [] : $this->messages->pinned($room)->values(),
            ],
            '',
            [
                'last_id' => $messages->last()['id'] ?? ($request->get('after') ? (int) $request->get('after') : null),
                'is_delta' => $isDelta,
            ]
        );
    }

    /** POST /api/online-learning/rooms/{room}/messages */
    public function store(Request $request, ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
            'type' => 'nullable|in:message,announcement',
        ]);

        $type = RoomMessageType::from($validated['type'] ?? 'message');

        // The service owns this rule so chat and announcements cannot diverge.
        $check = $this->messages->canPost($room, $ctx['role'], $type);
        if (! $check['allowed']) {
            return $this->forbidden($check['reason']);
        }

        $message = $this->messages->post($room, $ctx['user'], $validated['body'], $type);

        return $this->ok(
            ['message' => [
                'id' => $message->id,
                'type' => $message->type->value,
                'body' => $message->body,
                'is_pinned' => false,
                'user_id' => $ctx['user']->id,
                'author' => $ctx['user']->name,
                'at' => $message->created_at->toIso8601String(),
            ]],
            $type === RoomMessageType::Announcement ? 'Announcement posted.' : ''
        );
    }

    /** POST /api/online-learning/rooms/{room}/messages/{message}/pin — host only. */
    public function pin(Request $request, ClassRoom $room, int $message): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        if ($denied = $this->requireControl($ctx['role'], 'Only the host can pin messages.')) {
            return $denied;
        }

        $pinned = $request->boolean('pinned', true);
        $updated = $this->messages->setPinned($room, $message, $pinned);

        if (! $updated) {
            return $this->notFound('That message is not part of this room.');
        }

        return $this->ok(
            ['pinned' => $this->messages->pinned($room)->values()],
            $pinned ? 'Message pinned.' : 'Message unpinned.'
        );
    }

    /** DELETE /api/online-learning/rooms/{room}/messages/{message} — host only. */
    public function destroy(ClassRoom $room, int $message): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        if ($denied = $this->requireControl($ctx['role'], 'Only the host can remove messages.')) {
            return $denied;
        }

        $removed = $this->messages->removeAsHost($room, $message);

        if (! $removed) {
            return $this->notFound('That message is not part of this room.');
        }

        return $this->ok(['id' => $message], 'Message removed.');
    }
}
