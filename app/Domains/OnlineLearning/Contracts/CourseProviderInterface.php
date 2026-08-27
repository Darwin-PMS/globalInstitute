<?php

namespace App\Domains\OnlineLearning\Contracts;

interface CourseProviderInterface
{
    public function findById(int $courseId): ?object;
    public function findBySlug(string $slug): ?object;
    public function getCourseTitle(int $courseId): string;
    public function getExternalCourseId(int $courseId): ?string;
    public function getCourseProvider(): string;
}
