<?php

namespace App\Domains\OnlineLearning\Room\Services;

use App\Domains\OnlineLearning\Room\Contracts\RoomSubjectInterface;
use App\Domains\OnlineLearning\Room\Contracts\RoomSubjectResolverInterface;
use App\Domains\OnlineLearning\Room\Enums\RoomEventType;
use App\Domains\OnlineLearning\Room\Enums\RoomState;
use App\Domains\OnlineLearning\Room\Events\RoomStateChanged;
use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Provisioning and lifecycle for Live Class Rooms.
 *
 * Each scheduled class gets exactly one room, created on first use, carrying its
 * own state and settings. State changes are the single place the room lifecycle
 * moves, and each one is recorded on the timeline and broadcast as an event so a
 * real-time transport can be added later without touching callers.
 */
class ClassRoomService
{
    public function __construct(
        protected RoomSubjectResolverInterface $resolver,
        protected RoomTimelineService $timeline,
    ) {}

    /* ------------------------------------------------------- provisioning */

    /**
     * The room for a subject, created if it does not exist yet.
     *
     * Uses the subject pair as the identity, so two concurrent first-visits
     * cannot produce two rooms for the same class.
     */
    public function roomFor(RoomSubjectInterface $subject): ClassRoom
    {
        $room = ClassRoom::forSubject($subject->subjectType(), $subject->subjectId())->first();

        if ($room) {
            return $room;
        }

        return DB::transaction(function () use ($subject) {
            $room = ClassRoom::create([
                'subject_type' => $subject->subjectType(),
                'subject_id' => $subject->subjectId(),
                'room_key' => (string) Str::uuid(),
                'state' => $subject->isCancelled() ? RoomState::Cancelled : RoomState::Scheduled,
                'cancelled_at' => $subject->isCancelled() ? now() : null,
                'cancellation_reason' => $subject->cancellationReason(),
                'host_user_id' => $subject->hostUserId(),
                'settings' => $this->defaultSettingsFor($subject),
                'tenant_id' => config('online-learning.multi_tenant') ? $subject->model()->tenant_id ?? null : null,
            ]);

            return $room;
        });
    }

    /** A room's settings start from config, then the subject's own rules. */
    protected function defaultSettingsFor(RoomSubjectInterface $subject): array
    {
        $settings = array_merge(
            ClassRoom::DEFAULT_SETTINGS,
            config('online-learning.room.default_settings', [])
        );

        $settings['attendance_tracking'] = $subject->tracksAttendance();
        $settings['lobby_opens_minutes_before'] = (int) config(
            'online-learning.join_window.before_start_minutes',
            $settings['lobby_opens_minutes_before']
        );

        return $settings;
    }

    public function resolveSubject(ClassRoom $room): ?RoomSubjectInterface
    {
        return $this->resolver->resolveForRoom($room);
    }

    /* -------------------------------------------------------- transitions */

    /**
     * Move the room's own clock forward based on the schedule.
     *
     * Called on every room read so a room opens its lobby and closes itself
     * without needing a scheduler. A host starting the class is what moves it to
     * Live — time alone never does, because an unattended class is not live.
     */
    public function syncState(ClassRoom $room, RoomSubjectInterface $subject): ClassRoom
    {
        if ($room->isFinished()) {
            return $room;
        }

        if ($subject->isCancelled()) {
            return $this->cancel($room, $subject->cancellationReason());
        }

        $now = now();
        $lobbyOpensAt = $subject->startsAt()->copy()
            ->subMinutes((int) $room->setting('lobby_opens_minutes_before', 15));

        // Past the end of the window: close the room out.
        if ($now->gte($subject->endsAt()->copy()->addMinutes(
            (int) config('online-learning.room.auto_close_grace_minutes', 30)
        ))) {
            return $this->end($room, null, true);
        }

        if ($room->state === RoomState::Scheduled && $now->gte($lobbyOpensAt)) {
            return $this->open($room);
        }

        return $room;
    }

    public function open(ClassRoom $room, ?int $byUserId = null): ClassRoom
    {
        return $this->transition($room, RoomState::Open, $byUserId, RoomEventType::RoomOpened, [
            'opened_at' => now(),
        ]);
    }

    /** The host starting the class — this is what makes a room genuinely live. */
    public function start(ClassRoom $room, ?int $byUserId = null): ClassRoom
    {
        return $this->transition($room, RoomState::Live, $byUserId, RoomEventType::RoomStarted, [
            'started_at' => now(),
            'opened_at' => $room->opened_at ?: now(),
        ]);
    }

    public function end(ClassRoom $room, ?int $byUserId = null, bool $automatic = false): ClassRoom
    {
        return $this->transition(
            $room,
            RoomState::Ended,
            $byUserId,
            RoomEventType::RoomEnded,
            ['ended_at' => now()],
            $automatic ? 'Class closed automatically after its scheduled end.' : null
        );
    }

    public function cancel(ClassRoom $room, ?string $reason = null, ?int $byUserId = null): ClassRoom
    {
        return $this->transition(
            $room,
            RoomState::Cancelled,
            $byUserId,
            RoomEventType::RoomCancelled,
            ['cancelled_at' => now(), 'cancellation_reason' => $reason],
            $reason
        );
    }

    /**
     * The single gate every state change passes through. Illegal transitions are
     * ignored rather than throwing, so a double-clicked "Start" is harmless.
     */
    protected function transition(
        ClassRoom $room,
        RoomState $next,
        ?int $byUserId,
        RoomEventType $eventType,
        array $attributes = [],
        ?string $description = null
    ): ClassRoom {
        if ($room->state === $next || ! $room->state->canTransitionTo($next)) {
            return $room;
        }

        $previous = $room->state;

        $room->fill(array_merge($attributes, ['state' => $next]));
        $room->save();

        $this->timeline->record($room, $eventType, $byUserId, $description, [
            'from' => $previous->value,
            'to' => $next->value,
        ]);

        // Real-time-ready: a broadcast transport can subscribe to this without
        // any caller here needing to change.
        RoomStateChanged::dispatch($room->id, $previous->value, $next->value);

        return $room->refresh();
    }

    /* ----------------------------------------------------------- settings */

    /**
     * @param  array<string, mixed>  $changes
     */
    public function updateSettings(ClassRoom $room, array $changes, ?int $byUserId = null): ClassRoom
    {
        $allowed = array_keys(ClassRoom::DEFAULT_SETTINGS);
        $filtered = array_intersect_key($changes, array_flip($allowed));

        if (! $filtered) {
            return $room;
        }

        $room->settings = array_merge($room->allSettings(), $filtered);
        $room->save();

        $this->timeline->record($room, RoomEventType::SettingsChanged, $byUserId, null, [
            'changed' => array_keys($filtered),
        ]);

        return $room->refresh();
    }

    /* -------------------------------------------------------------- state */

    /** The header payload: what the room is and where it is in its lifecycle. */
    public function stateSnapshot(ClassRoom $room, RoomSubjectInterface $subject): array
    {
        return [
            'room_key' => $room->room_key,
            'state' => $room->state->value,
            'state_label' => $room->state->label(),
            'is_enterable' => $room->isEnterable(),
            'is_live' => $room->isLive(),
            'is_finished' => $room->isFinished(),
            'opened_at' => $room->opened_at?->toIso8601String(),
            'started_at' => $room->started_at?->toIso8601String(),
            'ended_at' => $room->ended_at?->toIso8601String(),
            'cancellation_reason' => $room->cancellation_reason,
            'host_present' => $room->hostIsPresent(),
            'host_name' => $subject->hostName(),
            'participant_count' => (int) $room->participant_count,
            'peak_participant_count' => (int) $room->peak_participant_count,
            'message_count' => (int) $room->message_count,
            'settings' => $room->allSettings(),
            'schedule' => [
                'starts_at' => $subject->startsAt()->toIso8601String(),
                'ends_at' => $subject->endsAt()->toIso8601String(),
                'timezone' => $subject->timezone(),
                'status' => $subject->scheduleStatus(),
                'lobby_opens_at' => $subject->startsAt()->copy()
                    ->subMinutes((int) $room->setting('lobby_opens_minutes_before', 15))
                    ->toIso8601String(),
            ],
        ];
    }
}
