<?php

namespace App\Domains\OnlineLearning\Room\Subjects;

use App\Domains\OnlineLearning\Room\Contracts\RoomSubjectInterface;
use App\Domains\OnlineLearning\Room\Contracts\RoomSubjectResolverInterface;
use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use App\Domains\OnlineLearning\Services\AttendanceService;
use App\Models\OnlineClass;

/**
 * Binds rooms to this institute's OnlineClass records.
 *
 * Replacing this binding (in OnlineLearningServiceProvider) is all it takes to
 * point the room layer at a different scheduled entity.
 */
class OnlineClassSubjectResolver implements RoomSubjectResolverInterface
{
    public function __construct(protected AttendanceService $attendanceService) {}

    public function resolveForRoom(ClassRoom $room): ?RoomSubjectInterface
    {
        if ($room->subject_type !== $this->subjectType()) {
            return null;
        }

        $class = OnlineClass::with(['course', 'batch', 'teacher', 'module'])
            ->find($room->subject_id);

        return $class ? $this->wrap($class) : null;
    }

    public function wrap(mixed $model): ?RoomSubjectInterface
    {
        if (! $model instanceof OnlineClass) {
            return null;
        }

        return new OnlineClassSubject($model, $this->attendanceService);
    }

    public function subjectType(): string
    {
        return OnlineClass::class;
    }
}
