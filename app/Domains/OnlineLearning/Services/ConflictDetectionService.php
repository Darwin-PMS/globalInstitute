<?php

namespace App\Domains\OnlineLearning\Services;

use App\Models\OnlineClass;
use App\Domains\OnlineLearning\Contracts\ConflictDetectionInterface;
use Illuminate\Support\Carbon;

class ConflictDetectionService
{
    public function __construct(
        protected ConflictDetectionInterface $detector
    ) {}

    public function detectTeacherConflict(int $teacherId, string $date, string $startTime, string $endTime, ?int $excludeClassId = null): ?array
    {
        return $this->detector->detectTeacherConflict($teacherId, $date, $startTime, $endTime, $excludeClassId);
    }

    public function detectBatchConflict(int $batchId, string $date, string $startTime, string $endTime, ?int $excludeClassId = null): ?array
    {
        return $this->detector->detectBatchConflict($batchId, $date, $startTime, $endTime, $excludeClassId);
    }

    public function hasConflict(int $teacherId, ?int $batchId, string $date, string $startTime, string $endTime, ?int $excludeClassId = null): array
    {
        return $this->detector->hasAnyConflict($teacherId, $batchId, $date, $startTime, $endTime, $excludeClassId);
    }

    public function validateClassCreation(int $teacherId, ?int $batchId, string $date, string $startTime, string $endTime, ?int $excludeClassId = null): array
    {
        $conflicts = $this->hasConflict($teacherId, $batchId, $date, $startTime, $endTime, $excludeClassId);

        return [
            'valid' => empty($conflicts),
            'conflicts' => $conflicts,
        ];
    }
}
