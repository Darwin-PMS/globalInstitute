<?php

namespace App\Domains\OnlineLearning\Providers;

use App\Models\Course;
use App\Domains\OnlineLearning\Contracts\CourseProviderInterface;

class InstituteCourseProvider implements CourseProviderInterface
{
    public function findById(int $courseId): ?object
    {
        return Course::find($courseId);
    }

    public function findBySlug(string $slug): ?object
    {
        return Course::where('slug', $slug)->first();
    }

    public function getCourseTitle(int $courseId): string
    {
        return Course::where('id', $courseId)->value('title') ?? 'Unknown';
    }

    public function getExternalCourseId(int $courseId): ?string
    {
        return (string) $courseId;
    }

    public function getCourseProvider(): string
    {
        return 'institute';
    }
}
