<?php

namespace App\Domains\OnlineLearning\Providers;

use App\Models\Enrollment;
use App\Domains\OnlineLearning\Contracts\EnrollmentProviderInterface;
use Illuminate\Support\Collection;

class InstituteEnrollmentProvider implements EnrollmentProviderInterface
{
    public function getActiveEnrollments(int $courseId): Collection
    {
        return Enrollment::where('course_id', $courseId)
            ->where('status', 'active')
            ->with('student.user')
            ->get();
    }

    public function getStudentEnrollments(int $studentId): Collection
    {
        return Enrollment::where('student_id', $studentId)
            ->with('course')
            ->latest()
            ->get();
    }

    public function isStudentEnrolled(int $studentId, int $courseId): bool
    {
        return Enrollment::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->exists();
    }

    public function getEnrolledStudentIds(int $courseId): Collection
    {
        return Enrollment::where('course_id', $courseId)
            ->where('status', 'active')
            ->pluck('student_id');
    }

    public function getEnrollment(int $studentId, int $courseId): ?object
    {
        return Enrollment::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->first();
    }

    public function getEnrolledBatchIds(int $courseId): Collection
    {
        return Enrollment::where('course_id', $courseId)
            ->where('status', 'active')
            ->distinct()
            ->pluck('batch_id')
            ->filter();
    }
}
