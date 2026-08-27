<?php

namespace App\Domains\OnlineLearning\Providers;

use App\Models\Student;
use App\Models\User;
use App\Domains\OnlineLearning\Contracts\StudentProviderInterface;
use Illuminate\Support\Collection;

class InstituteStudentProvider implements StudentProviderInterface
{
    public function findById(int $studentId): ?object
    {
        return Student::find($studentId);
    }

    public function findByUserId(int $userId): ?object
    {
        return Student::where('user_id', $userId)->first();
    }

    public function getStudentIdFromUser(int $userId): ?int
    {
        return Student::where('user_id', $userId)->value('id');
    }

    public function getEnrolledCourseIds(int $studentId): Collection
    {
        return \App\Models\Enrollment::where('student_id', $studentId)
            ->where('status', 'active')
            ->pluck('course_id');
    }

    public function isEnrolledInCourse(int $studentId, int $courseId): bool
    {
        return \App\Models\Enrollment::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->exists();
    }

    public function getStudentName(int $studentId): string
    {
        $student = Student::with('user')->find($studentId);
        return $student?->user?->name ?? 'Unknown';
    }

    public function getStudentEmail(int $studentId): string
    {
        $student = Student::with('user')->find($studentId);
        return $student?->user?->email ?? '';
    }
}
