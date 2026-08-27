<?php

namespace App\Domains\OnlineLearning\Services;

use App\Models\StudentLessonProgress;
use App\Models\Lesson;
use App\Models\CourseModule;
use App\Models\Enrollment;

class ProgressService
{
    public function calculateLessonProgress(int $studentId, int $enrollmentId, int $lessonId): int
    {
        $progress = StudentLessonProgress::where('student_id', $studentId)
            ->where('enrollment_id', $enrollmentId)
            ->where('lesson_id', $lessonId)
            ->first();

        return $progress?->progress_percentage ?? 0;
    }

    public function calculateModuleProgress(int $studentId, int $enrollmentId, int $moduleId): int
    {
        $totalLessons = Lesson::where('module_id', $moduleId)->count();
        if ($totalLessons === 0) return 0;

        $completedLessons = StudentLessonProgress::where('student_id', $studentId)
            ->where('enrollment_id', $enrollmentId)
            ->whereHas('lesson', fn($q) => $q->where('module_id', $moduleId))
            ->whereNotNull('completed_at')
            ->count();

        return round(($completedLessons / $totalLessons) * 100);
    }

    public function calculateCourseProgress(int $studentId, int $enrollmentId, int $courseId): int
    {
        $totalLessons = Lesson::where('course_id', $courseId)->count();
        if ($totalLessons === 0) return 0;

        $completedLessons = StudentLessonProgress::where('student_id', $studentId)
            ->where('enrollment_id', $enrollmentId)
            ->whereHas('lesson', fn($q) => $q->where('course_id', $courseId))
            ->whereNotNull('completed_at')
            ->count();

        return round(($completedLessons / $totalLessons) * 100);
    }

    public function markLessonComplete(int $studentId, int $enrollmentId, int $lessonId): void
    {
        StudentLessonProgress::updateOrCreate(
            [
                'student_id' => $studentId,
                'enrollment_id' => $enrollmentId,
                'lesson_id' => $lessonId,
            ],
            [
                'completed_at' => now(),
                'progress_percentage' => 100,
            ]
        );
    }

    public function markLessonStarted(int $studentId, int $enrollmentId, int $lessonId): void
    {
        StudentLessonProgress::firstOrCreate(
            [
                'student_id' => $studentId,
                'enrollment_id' => $enrollmentId,
                'lesson_id' => $lessonId,
            ],
            [
                'started_at' => now(),
                'progress_percentage' => 0,
            ]
        );
    }
}
