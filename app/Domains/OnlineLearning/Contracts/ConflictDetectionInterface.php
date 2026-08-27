<?php

namespace App\Domains\OnlineLearning\Contracts;

interface ConflictDetectionInterface
{
    public function detectTeacherConflict(int $teacherId, string $date, string $startTime, string $endTime, ?int $excludeSessionId = null): ?array;
    public function detectBatchConflict(int $batchId, string $date, string $startTime, string $endTime, ?int $excludeSessionId = null): ?array;
    public function detectRoomConflict(int $roomId, string $date, string $startTime, string $endTime, ?int $excludeSessionId = null): ?array;
    public function hasAnyConflict(int $teacherId, ?int $batchId, string $date, string $startTime, string $endTime, ?int $excludeSessionId = null): array;
}
