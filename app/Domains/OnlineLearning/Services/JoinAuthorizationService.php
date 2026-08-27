<?php

namespace App\Domains\OnlineLearning\Services;

use App\Models\OnlineClass;
use App\Models\Enrollment;
use App\Domains\OnlineLearning\Contracts\EnrollmentProviderInterface;
use Carbon\Carbon;

class JoinAuthorizationService
{
    public function __construct(
        protected EnrollmentProviderInterface $enrollmentProvider
    ) {}

    public function canStudentJoin(int $studentId, OnlineClass $onlineClass): array
    {
        if ($onlineClass->status === 'cancelled') {
            return ['allowed' => false, 'reason' => 'This class has been cancelled.'];
        }

        if ($onlineClass->status === 'completed') {
            return ['allowed' => false, 'reason' => 'This class has already ended.'];
        }

        if (!$this->enrollmentProvider->isStudentEnrolled($studentId, $onlineClass->course_id)) {
            return ['allowed' => false, 'reason' => 'You are not enrolled in this course.'];
        }

        $config = config('online-learning.join_window');

        // start_time / end_time are Carbon casts; startsAt()/endsAt() combine
        // them with the class date safely rather than by string concatenation.
        $classStart = $onlineClass->startsAt();
        $classEnd = $onlineClass->endsAt();
        $now = Carbon::now();

        $joinWindowStart = $classStart->copy()->subMinutes($config['before_start_minutes']);
        $joinWindowEnd = $classEnd->copy()->addMinutes($config['after_end_minutes']);

        if ($now->lt($joinWindowStart)) {
            return ['allowed' => false, 'reason' => 'You can join ' . $config['before_start_minutes'] . ' minutes before class starts.'];
        }

        if ($now->gt($joinWindowEnd)) {
            return ['allowed' => false, 'reason' => 'The join window for this class has closed.'];
        }

        if (!$onlineClass->meeting_url) {
            return ['allowed' => false, 'reason' => 'Meeting link is not available yet.'];
        }

        return ['allowed' => true, 'meeting_url' => $onlineClass->meeting_url];
    }

    public function canTeacherAccess(int $teacherId, OnlineClass $onlineClass): bool
    {
        return $onlineClass->teacher_id === $teacherId;
    }
}
