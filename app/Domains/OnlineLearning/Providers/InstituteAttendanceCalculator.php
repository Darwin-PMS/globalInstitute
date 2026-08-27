<?php

namespace App\Domains\OnlineLearning\Providers;

use App\Domains\OnlineLearning\Contracts\AttendanceCalculatorInterface;

class InstituteAttendanceCalculator implements AttendanceCalculatorInterface
{
    public function calculate(int $classDurationMinutes, int $attendedMinutes): string
    {
        if ($classDurationMinutes <= 0) return 'absent';

        $percentage = ($attendedMinutes / $classDurationMinutes) * 100;
        $thresholds = $this->getConfiguredThresholds();

        if ($percentage >= $thresholds['present_threshold']) return 'present';
        if ($percentage >= $thresholds['partial_threshold']) return 'partial';
        return 'absent';
    }

    public function isPresent(int $classDurationMinutes, int $attendedMinutes): bool
    {
        return $this->calculate($classDurationMinutes, $attendedMinutes) === 'present';
    }

    public function isLate(int $classDurationMinutes, int $joinedMinutes): bool
    {
        $thresholds = $this->getConfiguredThresholds();
        $lateThreshold = $classDurationMinutes * ($thresholds['late_threshold'] / 100);
        return $joinedMinutes > $lateThreshold;
    }

    public function getConfiguredThresholds(): array
    {
        return config('online-learning.attendance', [
            'present_threshold' => 75,
            'late_threshold' => 10,
            'partial_threshold' => 25,
            'absent_threshold' => 0,
        ]);
    }
}
