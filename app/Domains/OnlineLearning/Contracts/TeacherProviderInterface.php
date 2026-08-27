<?php

namespace App\Domains\OnlineLearning\Contracts;

use Illuminate\Support\Collection;

interface TeacherProviderInterface
{
    public function findById(int $teacherId): ?object;
    public function findByUserId(int $userId): ?object;
    public function getTeacherIdFromUser(int $userId): ?int;
    public function getAssignedCourseIds(int $teacherId): Collection;
    public function getTeacherName(int $teacherId): string;
}
