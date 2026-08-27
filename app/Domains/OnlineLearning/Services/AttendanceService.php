<?php

namespace App\Domains\OnlineLearning\Services;

use App\Models\ClassAttendance;
use App\Models\OnlineClass;
use App\Models\Enrollment;
use App\Domains\OnlineLearning\Contracts\AttendanceCalculatorInterface;
use Illuminate\Support\Collection;

class AttendanceService
{
    public function __construct(
        protected AttendanceCalculatorInterface $calculator
    ) {}

    public function markAttendance(int $classId, int $studentId, ?int $enrollmentId, string $status, ?string $notes, ?int $markedBy): ClassAttendance
    {
        return ClassAttendance::updateOrCreate(
            [
                'online_class_id' => $classId,
                'student_id' => $studentId,
            ],
            [
                'enrollment_id' => $enrollmentId,
                'status' => $status,
                'notes' => $notes,
                'marked_by' => $markedBy,
            ]
        );
    }

    public function markBulkAttendance(int $classId, array $records, ?int $markedBy): void
    {
        // class_attendance.enrollment_id is NOT NULL, and the roster the UI posts
        // back carries only student ids. Resolve each student's enrollment for
        // this class once, up front, instead of writing null and failing.
        $class = OnlineClass::find($classId);
        $enrollmentIds = $class
            ? Enrollment::where('course_id', $class->course_id)
                ->when($class->batch_id, fn ($q) => $q->where('batch_id', $class->batch_id))
                ->whereIn('student_id', array_column($records, 'student_id'))
                ->pluck('id', 'student_id')
            : collect();

        foreach ($records as $record) {
            $this->markAttendance(
                $classId,
                $record['student_id'],
                $record['enrollment_id'] ?? $enrollmentIds->get($record['student_id']),
                $record['status'],
                $record['notes'] ?? null,
                $markedBy
            );
        }
    }

    public function calculateAutomaticAttendance(int $classId, int $studentId, int $joinedMinutes, int $classDurationMinutes): string
    {
        return $this->calculator->calculate($classDurationMinutes, $joinedMinutes);
    }

    public function getClassAttendance(int $classId): Collection
    {
        return ClassAttendance::where('online_class_id', $classId)
            ->with('student.user')
            ->get();
    }

    public function getAttendanceStats(int $classId): array
    {
        $attendance = $this->getClassAttendance($classId);

        return [
            'total' => $attendance->count(),
            'present' => $attendance->where('status', 'present')->count(),
            'absent' => $attendance->where('status', 'absent')->count(),
            'late' => $attendance->where('status', 'late')->count(),
            'partial' => $attendance->where('status', 'partial')->count(),
            'excused' => $attendance->where('status', 'excused')->count(),
        ];
    }

    public function getStudentAttendanceRate(int $studentId, int $courseId): float
    {
        $totalClasses = OnlineClass::where('course_id', $courseId)
            ->where('status', 'completed')
            ->count();

        if ($totalClasses === 0) return 0;

        $attendedClasses = ClassAttendance::where('student_id', $studentId)
            ->whereHas('onlineClass', fn($q) => $q->where('course_id', $courseId))
            ->whereIn('status', ['present', 'late'])
            ->count();

        return round(($attendedClasses / $totalClasses) * 100, 1);
    }
}
