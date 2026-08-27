<?php

namespace App\Domains\OnlineLearning\Services;

use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use App\Domains\OnlineLearning\Room\Models\ClassRoomParticipant;
use App\Domains\OnlineLearning\Room\Enums\RoomRole;
use App\Domains\OnlineLearning\Room\Enums\RoomState;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LiveClassAttendanceService
{
    protected LiveKitService $liveKitService;

    public function __construct(LiveKitService $liveKitService)
    {
        $this->liveKitService = $liveKitService;
    }

    /**
     * Calculate attendance from room presence data.
     */
    public function calculateAttendance(ClassRoom $room): array
    {
        $participants = $room->participants()
            ->where('role', RoomRole::Participant)
            ->with('user')
            ->get();

        $attendance = [];
        $config = config('online-learning.attendance');

        foreach ($participants as $participant) {
            $duration = $participant->attendedSeconds();
            $classDuration = $this->getClassDuration($room);
            $percentage = $classDuration > 0 ? round(($duration / $classDuration) * 100, 2) : 0;

            $status = $this->classifyAttendance($percentage, $config, $participant);

            $attendance[] = [
                'user_id' => $participant->user_id,
                'user_name' => $participant->display_name,
                'duration_seconds' => $duration,
                'class_duration_seconds' => $classDuration,
                'attendance_percentage' => $percentage,
                'attendance_status' => $status,
                'join_count' => $participant->join_count,
                'first_joined_at' => $participant->joined_at?->toISOString(),
                'last_seen_at' => $participant->last_seen_at?->toISOString(),
                'left_at' => $participant->left_at?->toISOString(),
            ];
        }

        return $attendance;
    }

    /**
     * Get class duration in seconds from started_at to ended_at.
     */
    protected function getClassDuration(ClassRoom $room): int
    {
        if ($room->started_at && $room->ended_at) {
            return $room->started_at->diffInSeconds($room->ended_at);
        }

        // Use scheduled duration from the subject
        $subject = $room->subject;
        if ($subject && method_exists($subject, 'getStartTimeAttribute') && method_exists($subject, 'getEndTimeAttribute')) {
            $start = $subject->getStartTimeAttribute();
            $end = $subject->getEndTimeAttribute();
            if ($start && $end) {
                return strtotime($start) > 0 && strtotime($end) > 0
                    ? strtotime($end) - strtotime($start)
                    : 3600; // fallback 1 hour
            }
        }

        return 3600; // Default 1 hour
    }

    /**
     * Classify attendance percentage into a status.
     */
    protected function classifyAttendance(float $percentage, array $config, ClassRoomParticipant $participant): string
    {
        // Check if participant joined late
        $isLate = false;
        if ($participant->joined_at) {
            $room = $participant->room;
            $subject = $room?->subject;
            if ($subject && method_exists($subject, 'getStartTimeAttribute')) {
                $classStart = $subject->getStartTimeAttribute();
                if ($classStart && strtotime($classStart) > 0) {
                    $delayMinutes = (strtotime($participant->joined_at->toDateTimeString()) - strtotime($classStart)) / 60;
                    $classDurationMinutes = $this->getClassDuration($room) / 60;
                    if ($classDurationMinutes > 0 && ($delayMinutes / $classDurationMinutes) * 100 > ($config['late_threshold'] ?? 10)) {
                        $isLate = true;
                    }
                }
            }
        }

        $status = match (true) {
            $percentage >= ($config['present_threshold'] ?? 75) => $isLate ? 'late' : 'present',
            $percentage >= ($config['partial_threshold'] ?? 25) => 'partial',
            default => 'absent',
        };

        return $status;
    }

    /**
     * Get attendance stats for a class.
     */
    public function getAttendanceStats(ClassRoom $room): array
    {
        $attendance = $this->calculateAttendance($room);

        $total = count($attendance);
        $present = count(array_filter($attendance, fn($a) => $a['attendance_status'] === 'present'));
        $late = count(array_filter($attendance, fn($a) => $a['attendance_status'] === 'late'));
        $partial = count(array_filter($attendance, fn($a) => $a['attendance_status'] === 'partial'));
        $absent = count(array_filter($attendance, fn($a) => $a['attendance_status'] === 'absent'));
        $avgPercentage = $total > 0 ? round(array_sum(array_column($attendance, 'attendance_percentage')) / $total, 2) : 0;

        return [
            'total_enrolled' => $total,
            'present' => $present,
            'late' => $late,
            'partial' => $partial,
            'absent' => $absent,
            'average_attendance_percentage' => $avgPercentage,
            'attendance' => $attendance,
        ];
    }

    /**
     * Get student attendance across multiple classes.
     */
    public function getStudentAttendanceStats(User $student, ?int $courseId = null): array
    {
        $query = ClassRoomParticipant::where('user_id', $student->id)
            ->whereHas('room', function ($q) use ($courseId) {
                $q->where('state', RoomState::Ended);
                if ($courseId) {
                    $q->where('subject_type', 'App\\Models\\OnlineClass')
                      ->whereHas('subject', fn($sq) => $sq->where('course_id', $courseId));
                }
            })
            ->with('room.subject');

        $participants = $query->get();

        $totalClasses = $participants->count();
        $attended = $participants->filter(fn($p) => $p->attendedSeconds() > 0)->count();
        $totalDuration = $participants->sum(fn($p) => $p->attendedSeconds());
        $avgPercentage = $totalClasses > 0 ? round(($attended / $totalClasses) * 100, 2) : 0;

        return [
            'student_id' => $student->id,
            'total_classes' => $totalClasses,
            'attended' => $attended,
            'missed' => $totalClasses - $attended,
            'attendance_percentage' => $avgPercentage,
            'total_duration_seconds' => $totalDuration,
            'average_duration_seconds' => $totalClasses > 0 ? round($totalDuration / $totalClasses) : 0,
        ];
    }
}
