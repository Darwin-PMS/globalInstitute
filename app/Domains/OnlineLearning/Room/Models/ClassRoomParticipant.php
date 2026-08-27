<?php

namespace App\Domains\OnlineLearning\Room\Models;

use App\Domains\OnlineLearning\Room\Enums\RoomRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Presence inside a room — who is here, since when, for how long.
 *
 * Distinct from class_attendance: presence is the raw signal, attendance is the
 * graded record the room derives from it.
 */
class ClassRoomParticipant extends Model
{
    protected $fillable = [
        'class_room_id', 'user_id', 'role', 'display_name',
        'first_joined_at', 'last_joined_at', 'left_at', 'last_seen_at',
        'join_count', 'total_seconds',
    ];

    protected $casts = [
        'role' => RoomRole::class,
        'first_joined_at' => 'datetime',
        'last_joined_at' => 'datetime',
        'left_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'join_count' => 'integer',
        'total_seconds' => 'integer',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOnline(int $staleAfterSeconds = 90): bool
    {
        return $this->left_at === null
            && $this->last_seen_at !== null
            && $this->last_seen_at->gt(now()->subSeconds($staleAfterSeconds));
    }

    /**
     * Time in the room including the session currently open, so the figure does
     * not jump only when someone leaves.
     */
    public function attendedSeconds(): int
    {
        $seconds = $this->total_seconds;

        if ($this->left_at === null && $this->last_joined_at && $this->last_seen_at) {
            $seconds += self::elapsed($this->last_joined_at, $this->last_seen_at);
        }

        return $seconds;
    }

    /**
     * Seconds between two moments, never negative.
     *
     * Uses timestamps rather than diffInSeconds() on purpose: Carbon 3 returns a
     * SIGNED difference, so the argument order silently decided whether presence
     * counted or came out as zero.
     */
    public static function elapsed(?\DateTimeInterface $from, ?\DateTimeInterface $to): int
    {
        if (! $from || ! $to) {
            return 0;
        }

        return max(0, $to->getTimestamp() - $from->getTimestamp());
    }
}
