<?php

namespace App\Http\Controllers\Api\OnlineLearning\Room;

use App\Domains\OnlineLearning\Room\Enums\RoomEventType;
use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * One endpoint per room panel.
 *
 * Each is independently addressable so the client fetches a panel only when the
 * user opens it, and can refresh that panel alone afterwards. Nothing here
 * returns data the caller's role is not entitled to.
 */
class RoomPanelApiController extends RoomController
{
    /** GET /api/online-learning/rooms/{room}/materials */
    public function materials(ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        $canControl = $ctx['role']->canControlRoom();

        if (! $room->setting('materials_visible', true) && ! $canControl) {
            return $this->forbidden('Materials are hidden for this class.');
        }

        // Hosts also see unpublished items; participants never do.
        return $this->ok(['materials' => $ctx['subject']->materials(! $canControl)->values()]);
    }

    /** GET /api/online-learning/rooms/{room}/recordings */
    public function recordings(ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        $canControl = $ctx['role']->canControlRoom();

        if (! $room->setting('recordings_visible', true) && ! $canControl) {
            return $this->forbidden('Recordings are hidden for this class.');
        }

        return $this->ok(['recordings' => $ctx['subject']->recordings(! $canControl)->values()]);
    }

    /** GET /api/online-learning/rooms/{room}/assignments */
    public function assignments(ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        $canControl = $ctx['role']->canControlRoom();

        if (! $room->setting('assignments_visible', true) && ! $canControl) {
            return $this->forbidden('Assignments are hidden for this class.');
        }

        return $this->ok(['assignments' => $ctx['subject']->assignments(! $canControl)->values()]);
    }

    /** GET /api/online-learning/rooms/{room}/roster — host only. */
    public function roster(ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        if ($denied = $this->requireControl($ctx['role'], 'Only the host can see the participant list.')) {
            return $denied;
        }

        return $this->ok([
            'expected' => $ctx['subject']->expectedRoster()->values(),
            'participants' => $this->presence->all($room)->values(),
            'online' => $this->presence->online($room)->values(),
        ]);
    }

    /**
     * GET /api/online-learning/rooms/{room}/presence
     *
     * Polled while the room is open, so it stays deliberately small.
     */
    public function presence(ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        // Participants see a count and the host's presence, not a name list —
        // a class roster is not something every attendee should be able to read.
        if (! $ctx['role']->canControlRoom()) {
            return $this->ok([
                'online_count' => $room->onlineParticipants()->count(),
                'host_present' => $room->hostIsPresent(),
            ]);
        }

        return $this->ok([
            'online_count' => $room->onlineParticipants()->count(),
            'host_present' => $room->hostIsPresent(),
            'online' => $this->presence->online($room)->values(),
        ]);
    }

    /**
     * GET /api/online-learning/rooms/{room}/attendance — host only.
     *
     * Returns the saved roster alongside a proposal derived from measured
     * presence, so the host confirms rather than types.
     */
    public function attendance(ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        if ($denied = $this->requireControl($ctx['role'], 'Only the host can view attendance.')) {
            return $denied;
        }

        $subject = $ctx['subject'];
        // Timestamp arithmetic, not diffInSeconds(): Carbon 3 returns a SIGNED
        // difference, so the reversed order collapsed the duration to 1 second
        // and made every proposal read 100%.
        $durationSeconds = max(1, $subject->endsAt()->getTimestamp() - $subject->startsAt()->getTimestamp());

        return $this->ok([
            'tracking_enabled' => (bool) $room->setting('attendance_tracking', true),
            'roster' => $subject->attendanceRoster()->values(),
            'stats' => $subject->attendanceStats(),
            'proposal' => $this->presence->attendanceProposal($room, $durationSeconds)->values(),
            'class_duration_seconds' => $durationSeconds,
        ]);
    }

    /** POST /api/online-learning/rooms/{room}/attendance — host only. */
    public function saveAttendance(Request $request, ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        if ($denied = $this->requireControl($ctx['role'], 'Only the host can mark attendance.')) {
            return $denied;
        }

        if (! $room->setting('attendance_tracking', true)) {
            return $this->fail('Attendance tracking is disabled for this class.', 422);
        }

        $validated = $request->validate([
            'records' => 'required|array|min:1',
            'records.*.user_id' => 'required|integer',
            'records.*.status' => 'required|in:present,late,absent,partial',
            'records.*.notes' => 'nullable|string|max:255',
        ]);

        $ctx['subject']->recordAttendance($validated['records'], $ctx['user']->id);

        $this->timeline->record(
            $room,
            RoomEventType::AttendanceMarked,
            $ctx['user']->id,
            'Attendance marked for ' . count($validated['records']) . ' participants'
        );

        return $this->ok(
            ['stats' => $ctx['subject']->attendanceStats()],
            'Attendance saved for ' . count($validated['records']) . ' participants.'
        );
    }

    /**
     * GET /api/online-learning/rooms/{room}/activity
     *
     * Incremental: pass `after` to receive only newer entries.
     */
    public function activity(Request $request, ClassRoom $room): JsonResponse
    {
        $ctx = $this->context($room);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        $after = $request->filled('after') ? (int) $request->get('after') : null;
        $limit = (int) config('online-learning.room.feed_page_size', 100);

        $events = $this->timeline->feed($room, $after, $limit);

        return $this->ok(
            ['events' => $events->values()],
            '',
            ['last_id' => $events->last()['id'] ?? $after]
        );
    }
}
