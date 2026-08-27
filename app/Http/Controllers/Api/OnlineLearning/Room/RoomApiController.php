<?php

namespace App\Http\Controllers\Api\OnlineLearning\Room;

use App\Domains\OnlineLearning\Room\Enums\RoomRole;
use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The Live Class Room: bootstrap, state, lifecycle and settings.
 *
 * Reads are deliberately split so the client can lazy-load. `bootstrap` returns
 * only what the room shell needs to paint; each panel then fetches its own slice
 * on demand, and `state` is the cheap delta the client polls.
 */
class RoomApiController extends RoomController
{

    /**
     * GET /api/online-learning/rooms/{room}
     *
     * The shell payload: identity, schedule, room state and this user's
     * capabilities. Panel contents are NOT included — they load separately.
     */
    public function bootstrap(ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        $subject = $ctx['subject'];
        $entry = $this->access->canEnter($room, $ctx['user'], $subject);

        return $this->ok([
            'room' => $this->rooms->stateSnapshot($room, $subject),
            'class' => [
                'id' => $subject->subjectId(),
                'title' => $subject->title(),
                'description' => $subject->description(),
                'context' => $subject->contextLabel(),
                'host_name' => $subject->hostName(),
                'meeting_provider' => $subject->meetingProvider(),
                'tracks_attendance' => $subject->tracksAttendance(),
            ],
            'me' => array_merge(
                $this->access->capabilities($room, $ctx['role']),
                [
                    'user_id' => $ctx['user']->id,
                    'name' => $ctx['user']->name,
                    'can_enter' => $entry['allowed'],
                    'entry_message' => $entry['reason'],
                ]
            ),
            // Which panels this user may open, so the shell renders the right tabs.
            'panels' => $this->availablePanels($room, $ctx['role']),
            'poll_intervals' => config('online-learning.room.poll_intervals'),
        ]);
    }

    /**
     * GET /api/online-learning/rooms/{room}/state
     *
     * The polling endpoint: small, and safe to call often.
     */
    public function state(ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        return $this->ok([
            'room' => $this->rooms->stateSnapshot($room, $ctx['subject']),
            'online_count' => $room->onlineParticipants()->count(),
        ]);
    }

    /**
     * POST /api/online-learning/rooms/{room}/enter
     *
     * Authorises entry, records presence, and only then releases the meeting
     * link. The client never receives the URL before this call succeeds.
     */
    public function enter(ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        $subject = $ctx['subject'];
        $entry = $this->access->canEnter($room, $ctx['user'], $subject);

        if (! $entry['allowed']) {
            return $this->forbidden($entry['reason'] ?? 'You cannot enter this room right now.');
        }

        $this->presence->join($room, $ctx['user'], $ctx['role']);

        return $this->ok([
            'meeting_url' => $subject->meetingUrl(),
            'meeting_id' => $ctx['role']->canControlRoom() ? $subject->meetingId() : null,
            'meeting_password' => $ctx['role']->canControlRoom() ? $subject->meetingPassword() : null,
            'room' => $this->rooms->stateSnapshot($room->refresh(), $subject),
        ], 'Welcome to the room.');
    }

    /** POST /api/online-learning/rooms/{room}/heartbeat */
    public function heartbeat(ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        $this->presence->heartbeat($room, $ctx['user']);

        return $this->ok(['online_count' => $room->onlineParticipants()->count()]);
    }

    /** POST /api/online-learning/rooms/{room}/leave */
    public function leave(ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        $this->presence->leave($room, $ctx['user']);

        return $this->ok(null, 'You have left the room.');
    }

    /* ------------------------------------------------------ host controls */

    /** POST /api/online-learning/rooms/{room}/start */
    public function start(ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        if (! $ctx['role']->canControlRoom()) {
            return $this->forbidden('Only the host can start this class.');
        }

        $room = $this->rooms->start($room, $ctx['user']->id);

        return $this->ok(
            ['room' => $this->rooms->stateSnapshot($room, $ctx['subject'])],
            'The class is now live.'
        );
    }

    /** POST /api/online-learning/rooms/{room}/end */
    public function end(ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        if (! $ctx['role']->canControlRoom()) {
            return $this->forbidden('Only the host can end this class.');
        }

        $room = $this->rooms->end($room, $ctx['user']->id);

        return $this->ok(
            ['room' => $this->rooms->stateSnapshot($room, $ctx['subject'])],
            'The class has ended.'
        );
    }

    /** PUT /api/online-learning/rooms/{room}/settings */
    public function updateSettings(Request $request, ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        if (! $ctx['role']->canControlRoom()) {
            return $this->forbidden('Only the host can change room settings.');
        }

        $validated = $request->validate([
            'chat_enabled' => 'sometimes|boolean',
            'announcements_enabled' => 'sometimes|boolean',
            'materials_visible' => 'sometimes|boolean',
            'recordings_visible' => 'sometimes|boolean',
            'assignments_visible' => 'sometimes|boolean',
            'attendance_tracking' => 'sometimes|boolean',
            'allow_late_join' => 'sometimes|boolean',
            'lobby_opens_minutes_before' => 'sometimes|integer|min:0|max:180',
        ]);

        $room = $this->rooms->updateSettings($room, $validated, $ctx['user']->id);

        return $this->ok(
            [
                'room' => $this->rooms->stateSnapshot($room, $ctx['subject']),
                'me' => $this->access->capabilities($room, $ctx['role']),
            ],
            'Room settings updated.'
        );
    }

    /**
     * Panels this role may open. Driven by room settings so a host who disables
     * chat removes the tab for everyone, not just its contents.
     */
    protected function availablePanels(ClassRoom $room, RoomRole $role): array
    {
        $caps = $this->access->capabilities($room, $role);

        $panels = [
            ['key' => 'stage', 'label' => 'Stage', 'icon' => 'cast', 'lazy' => false],
        ];

        if ($caps['can_post_message'] || $room->setting('announcements_enabled', true)) {
            $panels[] = ['key' => 'chat', 'label' => 'Chat', 'icon' => 'forum', 'lazy' => true];
        }
        if ($caps['can_view_materials']) {
            $panels[] = ['key' => 'materials', 'label' => 'Materials', 'icon' => 'folder_open', 'lazy' => true];
        }
        if ($caps['can_view_assignments']) {
            $panels[] = ['key' => 'assignments', 'label' => 'Assignments', 'icon' => 'assignment', 'lazy' => true];
        }
        if ($caps['can_view_recordings']) {
            $panels[] = ['key' => 'recordings', 'label' => 'Recordings', 'icon' => 'smart_display', 'lazy' => true];
        }
        if ($caps['can_view_roster']) {
            $panels[] = ['key' => 'roster', 'label' => 'Participants', 'icon' => 'groups', 'lazy' => true];
        }
        if ($caps['can_mark_attendance']) {
            $panels[] = ['key' => 'attendance', 'label' => 'Attendance', 'icon' => 'how_to_reg', 'lazy' => true];
        }

        $panels[] = ['key' => 'activity', 'label' => 'Activity', 'icon' => 'history', 'lazy' => true];

        return $panels;
    }
}
