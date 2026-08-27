<?php

namespace App\Domains\OnlineLearning\Contracts;

interface AttendanceCalculatorInterface
{
    public function calculate(int $classDurationMinutes, int $attendedMinutes): string;
    public function isPresent(int $classDurationMinutes, int $attendedMinutes): bool;
    public function isLate(int $classDurationMinutes, int $joinedMinutes): bool;
    public function getConfiguredThresholds(): array;
}
