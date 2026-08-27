<?php

namespace App\Domains\OnlineLearning\Providers;

use App\Models\Trainer;
use App\Domains\OnlineLearning\Contracts\TeacherProviderInterface;
use Illuminate\Support\Collection;

class InstituteTeacherProvider implements TeacherProviderInterface
{
    public function findById(int $teacherId): ?object
    {
        return Trainer::find($teacherId);
    }

    public function findByUserId(int $userId): ?object
    {
        return Trainer::where('user_id', $userId)->first();
    }

    public function getTeacherIdFromUser(int $userId): ?int
    {
        return Trainer::where('user_id', $userId)->value('id');
    }

    public function getAssignedCourseIds(int $teacherId): Collection
    {
        return \App\Models\OnlineClass::where('teacher_id', $teacherId)
            ->distinct()
            ->pluck('course_id');
    }

    public function getTeacherName(int $teacherId): string
    {
        $teacher = Trainer::find($teacherId);
        return $teacher?->name ?? 'Unknown';
    }
}
