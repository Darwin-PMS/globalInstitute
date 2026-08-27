<?php

namespace App\Domains\OnlineLearning\Room\Contracts;

use App\Domains\OnlineLearning\Room\Models\ClassRoom;

/**
 * Turns a stored (subject_type, subject_id) pair back into a subject adapter,
 * and finds the subject for a room.
 *
 * Swapping the backing entity means binding a different resolver — the room
 * layer resolves subjects only through this.
 */
interface RoomSubjectResolverInterface
{
    /** Resolve the subject a room is bound to, or null if it has gone away. */
    public function resolveForRoom(ClassRoom $room): ?RoomSubjectInterface;

    /** Wrap an arbitrary model as a room subject, if this resolver handles it. */
    public function wrap(mixed $model): ?RoomSubjectInterface;

    /** The subject type string this resolver produces (the morph key). */
    public function subjectType(): string;
}
