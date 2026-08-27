<?php

namespace App\Domains\OnlineLearning\Contracts;

use Illuminate\Support\Collection;

interface EnrollmentProviderInterface
{
    public function getActiveEnrollments(int $courseId): Collection;
    public function getStudentEnrollments(int $studentId): Collection;
    public function isStudentEnrolled(int $studentId, int $courseId): bool;
    public function getEnrolledStudentIds(int $courseId): Collection;
    public function getEnrollment(int $studentId, int $courseId): ?object;
    public function getEnrolledBatchIds(int $courseId): Collection;
}
