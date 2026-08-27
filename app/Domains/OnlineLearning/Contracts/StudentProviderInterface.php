<?php

namespace App\Domains\OnlineLearning\Contracts;

use Illuminate\Support\Collection;

interface StudentProviderInterface
{
    public function findById(int $studentId): ?object;
    public function findByUserId(int $userId): ?object;
    public function getStudentIdFromUser(int $userId): ?int;
    public function getEnrolledCourseIds(int $studentId): Collection;
    public function isEnrolledInCourse(int $studentId, int $courseId): bool;
    public function getStudentName(int $studentId): string;
    public function getStudentEmail(int $studentId): string;
}
