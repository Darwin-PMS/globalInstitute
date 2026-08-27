<?php

namespace App\Domains\OnlineLearning\Services;

use App\Domains\OnlineLearning\Models\LiveClass;
use App\Domains\OnlineLearning\Models\ClassSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RecurrenceService
{
    public function generateSessions(LiveClass $liveClass, array $config): Collection
    {
        $startDate = Carbon::parse($config['start_date']);
        $endDate = Carbon::parse($config['end_date']);
        $days = $config['days'];
        $startTime = $config['start_time'];
        $endTime = $config['end_time'];
        $timezone = $config['timezone'] ?? 'Asia/Kolkata';

        $sessions = collect();
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $dayName = strtolower($current->format('l'));

            if (in_array($dayName, $days)) {
                $session = ClassSession::create([
                    'live_class_id' => $liveClass->id,
                    'session_date' => $current->toDateString(),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'timezone' => $timezone,
                    'meeting_provider' => $config['meeting_provider'] ?? 'other',
                    'meeting_url' => $config['meeting_url'] ?? null,
                    'meeting_id' => $config['meeting_id'] ?? null,
                    'meeting_password' => $config['meeting_password'] ?? null,
                    'status' => 'scheduled',
                    'created_by' => $config['created_by'] ?? null,
                ]);

                $sessions->push($session);
            }

            $current->addDay();
        }

        return $sessions;
    }

    public function getRecurrencePreview(array $config): array
    {
        $startDate = Carbon::parse($config['start_date']);
        $endDate = Carbon::parse($config['end_date']);
        $days = $config['days'];
        $dates = [];

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dayName = strtolower($current->format('l'));
            if (in_array($dayName, $days)) {
                $dates[] = $current->toDateString();
            }
            $current->addDay();
        }

        return $dates;
    }
}
