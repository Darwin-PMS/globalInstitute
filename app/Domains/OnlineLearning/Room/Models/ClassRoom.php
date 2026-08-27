<?php

namespace App\Domains\OnlineLearning\Room\Models;

use App\Domains\OnlineLearning\Room\Enums\RoomRole;
use App\Domains\OnlineLearning\Room\Enums\RoomState;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A single Live Class Room: one independent virtual classroom with its own
 * session state, participants, chat, timeline and settings.
 */
class ClassRoom extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject_type', 'subject_id', 'room_key', 'state',
        'opened_at', 'started_at', 'ended_at', 'cancelled_at', 'cancellation_reason',
        'host_user_id', 'host_last_seen_at', 'settings',
        'participant_count', 'peak_participant_count', 'message_count', 'tenant_id',
    ];

    protected $casts = [
        'state' => RoomState::class,
        'opened_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'host_last_seen_at' => 'datetime',
        'settings' => 'array',
        'participant_count' => 'integer',
        'peak_participant_count' => 'integer',
        'message_count' => 'integer',
        'tenant_id' => 'integer',
    ];

    /**
     * Defaults applied when a room is provisioned. Each room stores its own copy
     * so changing the default later never rewrites a class already in progress.
     */
    public const DEFAULT_SETTINGS = [
        'chat_enabled' => true,
        'announcements_enabled' => true,
        'materials_visible' => true,
        'recordings_visible' => true,
        'assignments_visible' => true,
        'attendance_tracking' => true,
        'allow_late_join' => true,
        'lobby_opens_minutes_before' => 15,
    ];

    /**
     * Rooms are addressed by their opaque key, never by id, so a room URL cannot
     * be walked to find other classes.
     */
    public function getRouteKeyName(): string
    {
        return 'room_key';
    }

    /* ----------------------------------------------------------- relations */

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ClassRoomParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ClassRoomMessage::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ClassRoomEvent::class);
    }

    /* -------------------------------------------------------------- scopes */

    public function scopeForSubject(Builder $query, string $type, int|string $id): Builder
    {
        return $query->where('subject_type', $type)->where('subject_id', $id);
    }

    public function scopeEnterable(Builder $query): Builder
    {
        return $query->whereIn('state', [RoomState::Open->value, RoomState::Live->value]);
    }

    /* ------------------------------------------------------------ settings */

    public function setting(string $key, mixed $default = null): mixed
    {
        $settings = array_merge(self::DEFAULT_SETTINGS, $this->settings ?? []);

        return $settings[$key] ?? $default;
    }

    public function allSettings(): array
    {
        return array_merge(self::DEFAULT_SETTINGS, $this->settings ?? []);
    }

    /* --------------------------------------------------------------- state */

    public function isEnterable(): bool
    {
        return $this->state->isEnterable();
    }

    public function isLive(): bool
    {
        return $this->state === RoomState::Live;
    }

    public function isFinished(): bool
    {
        return $this->state->isFinished();
    }

    /**
     * A host counts as present if they were seen recently. Used to show
     * "waiting for your instructor" rather than an empty stage.
     */
    public function hostIsPresent(int $staleAfterSeconds = 90): bool
    {
        return $this->host_last_seen_at !== null
            && $this->host_last_seen_at->gt(now()->subSeconds($staleAfterSeconds));
    }

    /** Participants seen within the heartbeat window are treated as online. */
    public function onlineParticipants(int $staleAfterSeconds = 90): HasMany
    {
        return $this->participants()
            ->whereNull('left_at')
            ->where('last_seen_at', '>=', now()->subSeconds($staleAfterSeconds));
    }

    public function participantFor(int $userId): ?ClassRoomParticipant
    {
        return $this->participants()->where('user_id', $userId)->first();
    }

    public function roleFor(int $userId): ?RoomRole
    {
        $participant = $this->participantFor($userId);

        return $participant ? $participant->role : null;
    }
}
