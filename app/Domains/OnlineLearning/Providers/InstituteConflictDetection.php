<?php

namespace App\Domains\OnlineLearning\Providers;

use App\Domains\OnlineLearning\Contracts\ConflictDetectionInterface;
use App\Models\OnlineClass;
use Illuminate\Database\Eloquent\Builder;

class InstituteConflictDetection implements ConflictDetectionInterface
{
    public function detectTeacherConflict(int $teacherId, string $date, string $startTime, string $endTime, ?int $excludeClassId = null): ?array
    {
        $conflict = $this->overlapping($date, $startTime, $endTime, $excludeClassId)
            ->where('teacher_id', $teacherId)
            ->first();

        if ($conflict) {
            return [
                'type' => 'teacher',
                'message' => "Teacher already has a class from {$conflict->timeOnly('start_time')} to {$conflict->timeOnly('end_time')}: {$conflict->title}",
                'conflicting_class_id' => $conflict->id,
            ];
        }

        return null;
    }

    public function detectBatchConflict(int $batchId, string $date, string $startTime, string $endTime, ?int $excludeClassId = null): ?array
    {
        $conflict = $this->overlapping($date, $startTime, $endTime, $excludeClassId)
            ->where('batch_id', $batchId)
            ->first();

        if ($conflict) {
            return [
                'type' => 'batch',
                'message' => "Batch already has a class from {$conflict->timeOnly('start_time')} to {$conflict->timeOnly('end_time')}: {$conflict->title}",
                'conflicting_class_id' => $conflict->id,
            ];
        }

        return null;
    }

    public function detectRoomConflict(int $roomId, string $date, string $startTime, string $endTime, ?int $excludeSessionId = null): ?array
    {
        return null;
    }

    public function hasAnyConflict(int $teacherId, ?int $batchId, string $date, string $startTime, string $endTime, ?int $excludeClassId = null): array
    {
        $conflicts = [];

        $teacherConflict = $this->detectTeacherConflict($teacherId, $date, $startTime, $endTime, $excludeClassId);
        if ($teacherConflict) {
            $conflicts[] = $teacherConflict;
        }

        if ($batchId) {
            $batchConflict = $this->detectBatchConflict($batchId, $date, $startTime, $endTime, $excludeClassId);
            if ($batchConflict) {
                $conflicts[] = $batchConflict;
            }
        }

        return $conflicts;
    }

    /**
     * Classes on the same day whose time range genuinely overlaps the candidate.
     *
     * Treats each class as the half-open interval [start, end): two classes
     * clash only when one starts before the other ends AND ends after the other
     * starts. The previous predicate used whereBetween on each endpoint, which
     * flagged back-to-back sessions (10:00-12:00 followed by 12:00-14:00) as
     * conflicting.
     */
    protected function overlapping(string $date, string $startTime, string $endTime, ?int $excludeClassId): Builder
    {
        $start = $this->normalizeTime($startTime);
        $end = $this->normalizeTime($endTime);

        // whereDate rather than a plain equality: class_date is a date cast, and
        // the stored representation differs between MySQL and SQLite.
        $query = OnlineClass::whereDate('class_date', $date)
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);

        if ($excludeClassId) {
            $query->where('id', '!=', $excludeClassId);
        }

        return $query;
    }

    /**
     * Times arrive from HTML time inputs as "H:i" and from the database as
     * "H:i:s". Pad to seconds so string comparison and TIME comparison agree.
     */
    protected function normalizeTime(string $time): string
    {
        $parts = explode(':', trim($time));

        return sprintf(
            '%02d:%02d:%02d',
            (int) ($parts[0] ?? 0),
            (int) ($parts[1] ?? 0),
            (int) ($parts[2] ?? 0)
        );
    }
}
