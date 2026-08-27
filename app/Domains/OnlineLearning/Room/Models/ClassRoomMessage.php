<?php

namespace App\Domains\OnlineLearning\Room\Models;

use App\Domains\OnlineLearning\Room\Enums\RoomMessageType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Chat and announcements belonging to one room. */
class ClassRoomMessage extends Model
{
    protected $fillable = [
        'class_room_id', 'user_id', 'type', 'body', 'is_pinned', 'deleted_by_host_at',
    ];

    protected $casts = [
        'type' => RoomMessageType::class,
        'is_pinned' => 'boolean',
        'deleted_by_host_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Messages a host removed stay in the table but leave the transcript. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNull('deleted_by_host_at');
    }

    /** Incremental fetch: everything newer than the id the client already has. */
    public function scopeAfter(Builder $query, ?int $afterId): Builder
    {
        return $afterId ? $query->where('id', '>', $afterId) : $query;
    }
}
