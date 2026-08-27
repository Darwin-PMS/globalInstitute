<?php

namespace App\Domains\OnlineLearning\Room\Models;

use App\Domains\OnlineLearning\Room\Enums\RoomEventType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One entry in a room's activity timeline. */
class ClassRoomEvent extends Model
{
    protected $fillable = [
        'class_room_id', 'user_id', 'type', 'description', 'meta',
    ];

    protected $casts = [
        'type' => RoomEventType::class,
        'meta' => 'array',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAfter(Builder $query, ?int $afterId): Builder
    {
        return $afterId ? $query->where('id', '>', $afterId) : $query;
    }
}
