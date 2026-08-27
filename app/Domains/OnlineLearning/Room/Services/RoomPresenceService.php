<?php

namespace App\Domains\OnlineLearning\Room\Services;

use App\Domains\OnlineLearning\Room\Enums\RoomEventType;
use App\Domains\OnlineLearning\Room\Enums\RoomRole;
use App\Domains\OnlineLearning\Room\Events\RoomPresenceChanged;
use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use App\Domains\OnlineLearning\Room\Models\ClassRoomParticipant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Presence inside a room: entering, heartbeats, leaving, and who is here now.
 *
 * Presence is deliberately separate from attendance. This tracks the raw
 * signal — attendance is derived from it when a host asks for it, so a dropped
 * connection never silently changes someone's record.
 */
class RoomPresenceService
{
    /** A participant not seen for this long is treated as gone. */
    public const STALE_AFTER_SECONDS = 90;

    public function __construct(protected RoomTimelineService $timeline) {}

    /**
     * Register a user as present. Re-entering the same room reuses their row and
     * accumulates time rather than starting over.
     */
    public function join(ClassRoom $room, User $user, RoomRole $role): ClassRoomParticipant
    {
        $participant = DB::transaction(function () use ($room, $user, $role) {
            $participant = ClassRoomParticipant::firstOrNew([
                'class_room_id' => $room->id,
                'user_id' => $user->id,
            ]);

            $isReturning = $participant->exists;

            // Close out any previous open stretch before starting a new one.
            if ($isReturning && $participant->left_at === null && $participant->last_joined_at) {
                $reference = $participant->last_seen_at ?: $participant->last_joined_at;
                $participant->total_seconds += ClassRoomParticipant::elapsed($participant->last_joined_at, $reference);
            }

            $participant->role = $role;
            $participant->display_name = $user->name;
            $participant->first_joined_at = $participant->first_joined_at ?: now();
            $participant->last_joined_at = now();
            $participant->last_seen_at = now();
            $participant->left_at = null;
            $participant->join_count = ($participant->join_count ?? 0) + 1;
            $participant->save();

            return $participant;
        });

        if ($role === RoomRole::Host) {
            $room->forceFill(['host_last_seen_at' => now()])->save();
        }

        $this->refreshCounters($room);

        // Only the first arrival is worth a timeline entry; reconnects are noise.
        if ($participant->join_count === 1) {
            $this->timeline->record($room, RoomEventType::ParticipantJoined, $user->id, $user->name . ' joined');
        }

        RoomPresenceChanged::dispatch($room->id, $user->id, 'joined');

        return $participant;
    }

    /**
     * Keep a participant alive. Called on an interval by the client; cheap by
     * design because it runs for everyone in the room.
     */
    public function heartbeat(ClassRoom $room, User $user): void
    {
        $updated = ClassRoomParticipant::where('class_room_id', $room->id)
            ->where('user_id', $user->id)
            ->update(['last_seen_at' => now(), 'updated_at' => now()]);

        if ($updated && $room->host_user_id === $user->id) {
            $room->forceFill(['host_last_seen_at' => now()])->save();
        }
    }

    public function leave(ClassRoom $room, User $user): void
    {
        $participant = ClassRoomParticipant::where('class_room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $participant || $participant->left_at !== null) {
            return;
        }

        if ($participant->last_joined_at) {
            // Credit up to the later of the last heartbeat and now, so a clean
            // leave is never worth less than a silent drop-off.
            $reference = $participant->last_seen_at && $participant->last_seen_at->gt(now())
                ? $participant->last_seen_at
                : now();
            $participant->total_seconds += ClassRoomParticipant::elapsed($participant->last_joined_at, $reference);
        }

        $participant->left_at = now();
        $participant->save();

        $this->refreshCounters($room);
        $this->timeline->record($room, RoomEventType::ParticipantLeft, $user->id, $user->name . ' left');

        RoomPresenceChanged::dispatch($room->id, $user->id, 'left');
    }

    /** Who is in the room right now. */
    public function online(ClassRoom $room): Collection
    {
        // Hosts first, then moderators, then everyone else. A CASE expression
        // rather than MySQL's FIELD() so this works on SQLite too.
        return $room->onlineParticipants(self::STALE_AFTER_SECONDS)
            ->with('user:id,name')
            ->orderByRaw("CASE role WHEN 'host' THEN 0 WHEN 'moderator' THEN 1 WHEN 'participant' THEN 2 ELSE 3 END")
            ->orderBy('first_joined_at')
            ->get()
            ->map(fn (ClassRoomParticipant $p) => $this->present($p));
    }

    /** Everyone who has ever been in the room, for the host's roster panel. */
    public function all(ClassRoom $room): Collection
    {
        return $room->participants()
            ->with('user:id,name')
            ->orderBy('first_joined_at')
            ->get()
            ->map(fn (ClassRoomParticipant $p) => $this->present($p));
    }

    protected function present(ClassRoomParticipant $participant): array
    {
        return [
            'user_id' => (int) $participant->user_id,
            'name' => $participant->user->name ?? $participant->display_name ?? 'Participant',
            'role' => $participant->role->value,
            'role_label' => $participant->role->label(),
            'is_online' => $participant->isOnline(self::STALE_AFTER_SECONDS),
            'first_joined_at' => $participant->first_joined_at?->toIso8601String(),
            'last_seen_at' => $participant->last_seen_at?->toIso8601String(),
            'join_count' => (int) $participant->join_count,
            'attended_seconds' => $participant->attendedSeconds(),
        ];
    }

    /**
     * Suggested attendance from measured presence.
     *
     * Returns a proposal only — a host confirms it. The thresholds come from the
     * module config so the same rules apply here and in the batch marking flow.
     */
    public function attendanceProposal(ClassRoom $room, int $classDurationSeconds): Collection
    {
        $rules = config('online-learning.attendance');
        $duration = max(1, $classDurationSeconds);

        return $room->participants()
            ->with('user:id,name')
            ->get()
            ->filter(fn (ClassRoomParticipant $p) => $p->role->countsTowardAttendance())
            ->map(function (ClassRoomParticipant $p) use ($duration, $rules) {
                $percent = min(100, (int) round(($p->attendedSeconds() / $duration) * 100));

                if ($percent >= ($rules['present_threshold'] ?? 75)) {
                    $status = 'present';
                } elseif ($percent >= ($rules['partial_threshold'] ?? 25)) {
                    $status = 'partial';
                } elseif ($percent > 0) {
                    $status = 'late';
                } else {
                    $status = 'absent';
                }

                return [
                    'user_id' => (int) $p->user_id,
                    'name' => $p->user->name ?? $p->display_name,
                    'attended_seconds' => $p->attendedSeconds(),
                    'attended_percent' => $percent,
                    'suggested_status' => $status,
                ];
            })
            ->values();
    }

    /** Keeps the denormalised counters on the room in step with presence. */
    protected function refreshCounters(ClassRoom $room): void
    {
        $online = $room->onlineParticipants(self::STALE_AFTER_SECONDS)->count();

        $room->forceFill([
            'participant_count' => $online,
            'peak_participant_count' => max((int) $room->peak_participant_count, $online),
        ])->save();
    }
}
