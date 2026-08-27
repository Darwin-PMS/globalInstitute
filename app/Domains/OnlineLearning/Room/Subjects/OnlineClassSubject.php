<?php

namespace App\Domains\OnlineLearning\Room\Subjects;

use App\Domains\OnlineLearning\Room\Contracts\RoomSubjectInterface;
use App\Domains\OnlineLearning\Services\AttendanceService;
use App\Models\Assignment;
use App\Models\ClassAttendance;
use App\Models\Enrollment;
use App\Models\LearningMaterial;
use App\Models\OnlineClass;
use App\Models\Recording;
use App\Models\Student;
use App\Models\Trainer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Adapts this institute's OnlineClass to the room layer.
 *
 * This is the ONLY room file that references institute models. Everything the
 * room needs about the scheduled class is answered here, so a standalone
 * product replaces this one class and leaves the rest of the room untouched.
 */
class OnlineClassSubject implements RoomSubjectInterface
{
    public function __construct(
        protected OnlineClass $class,
        protected AttendanceService $attendanceService,
    ) {}

    /* ---------------------------------------------------------- identity */

    public function subjectType(): string
    {
        return OnlineClass::class;
    }

    public function subjectId(): int|string
    {
        return $this->class->id;
    }

    public function title(): string
    {
        return (string) $this->class->title;
    }

    public function description(): ?string
    {
        return $this->class->description;
    }

    public function contextLabel(): ?string
    {
        $parts = array_filter([
            $this->class->course->title ?? null,
            $this->class->module->name ?? null,
            $this->class->batch->batch_name ?? $this->class->batch->name ?? null,
        ]);

        return $parts ? implode(' · ', $parts) : null;
    }

    /* ---------------------------------------------------------- schedule */

    public function startsAt(): Carbon
    {
        return $this->class->startsAt();
    }

    public function endsAt(): Carbon
    {
        return $this->class->endsAt();
    }

    public function timezone(): string
    {
        return $this->class->timezone ?: config('online-learning.timezone', 'Asia/Kolkata');
    }

    public function scheduleStatus(): string
    {
        return (string) $this->class->status;
    }

    public function isCancelled(): bool
    {
        return $this->class->status === 'cancelled';
    }

    public function cancellationReason(): ?string
    {
        return $this->class->cancelled_reason;
    }

    /* ------------------------------------------------------------- people */

    public function hostUserId(): ?int
    {
        $trainer = Trainer::find($this->class->teacher_id);

        return $trainer?->user_id;
    }

    public function hostName(): ?string
    {
        return $this->class->teacher->name ?? null;
    }

    public function expectedRoster(): Collection
    {
        $students = Student::whereHas('enrollments', function ($q) {
            $q->where('course_id', $this->class->course_id)
                ->when($this->class->batch_id, fn ($q2) => $q2->where('batch_id', $this->class->batch_id))
                ->where('status', 'active');
        })->with('user')->get();

        $roster = $students
            ->filter(fn ($student) => $student->user_id !== null)
            ->map(fn ($student) => [
                'user_id' => (int) $student->user_id,
                'name' => $student->user->name ?? ('Student #' . $student->id),
                'reference_id' => (int) $student->id,
                'role' => 'participant',
            ])
            ->values();

        // The assigned teacher belongs on the roster as the host.
        $hostUserId = $this->hostUserId();
        if ($hostUserId) {
            $roster->prepend([
                'user_id' => $hostUserId,
                'name' => $this->hostName() ?? 'Instructor',
                'reference_id' => (int) $this->class->teacher_id,
                'role' => 'host',
            ]);
        }

        return $roster;
    }

    public function isEnrolled(int $userId): bool
    {
        if ($this->hostUserId() === $userId) {
            return true;
        }

        $student = Student::where('user_id', $userId)->first();
        if (! $student) {
            return false;
        }

        return Enrollment::where('student_id', $student->id)
            ->where('course_id', $this->class->course_id)
            ->where('status', 'active')
            ->exists();
    }

    /* ------------------------------------------------------------ meeting */

    public function meetingProvider(): ?string
    {
        return $this->class->meeting_provider;
    }

    public function meetingUrl(): ?string
    {
        return $this->class->meeting_url;
    }

    public function meetingId(): ?string
    {
        return $this->class->meeting_id;
    }

    public function meetingPassword(): ?string
    {
        return $this->class->meeting_password;
    }

    /* ------------------------------------------------------------ content */

    public function materials(bool $publishedOnly = true): Collection
    {
        return LearningMaterial::where('class_id', $this->class->id)
            ->when($publishedOnly, fn ($q) => $q->where('is_published', true))
            ->latest()
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'description' => $m->description,
                'file_type' => $m->file_type,
                'url' => $m->external_url ?: ($m->file_path ? '/storage/' . $m->file_path : null),
                'is_published' => (bool) $m->is_published,
                'download_count' => (int) $m->download_count,
            ]);
    }

    public function recordings(bool $publishedOnly = true): Collection
    {
        return Recording::where('online_class_id', $this->class->id)
            ->when($publishedOnly, fn ($q) => $q->where('is_published', true))
            ->latest()
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'description' => $r->description,
                'url' => $r->recording_url,
                'duration' => $r->duration,
                'view_count' => (int) $r->view_count,
                'is_published' => (bool) $r->is_published,
            ]);
    }

    public function assignments(bool $publishedOnly = true): Collection
    {
        return Assignment::where('class_id', $this->class->id)
            ->when($publishedOnly, fn ($q) => $q->where('status', 'published'))
            ->latest()
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'description' => $a->description,
                'due_date' => $a->due_date?->toIso8601String(),
                'max_marks' => $a->max_marks,
                'status' => $a->status,
            ]);
    }

    /* --------------------------------------------------------- attendance */

    public function attendanceRoster(): Collection
    {
        $existing = ClassAttendance::where('online_class_id', $this->class->id)
            ->get()
            ->keyBy('student_id');

        $students = Student::whereHas('enrollments', function ($q) {
            $q->where('course_id', $this->class->course_id)
                ->when($this->class->batch_id, fn ($q2) => $q2->where('batch_id', $this->class->batch_id))
                ->where('status', 'active');
        })->with('user')->get();

        return $students->map(function ($student) use ($existing) {
            $record = $existing->get($student->id);

            return [
                'user_id' => $student->user_id ? (int) $student->user_id : null,
                'student_id' => (int) $student->id,
                'name' => $student->user->name ?? ('Student #' . $student->id),
                'code' => $student->student_id,
                'status' => $record?->status ?? 'absent',
                'notes' => $record?->notes,
                'joined_at' => $record?->joined_at?->toIso8601String(),
            ];
        })->values();
    }

    public function attendanceStats(): array
    {
        return $this->attendanceService->getAttendanceStats($this->class->id);
    }

    public function recordAttendance(array $records, ?int $markedByUserId = null): void
    {
        // Translate room user ids into the student ids attendance is keyed by.
        $userIds = array_filter(array_column($records, 'user_id'));
        $studentByUser = Student::whereIn('user_id', $userIds)->pluck('id', 'user_id');

        $payload = [];
        foreach ($records as $record) {
            $studentId = $studentByUser->get($record['user_id'] ?? null);
            if (! $studentId) {
                continue;
            }

            $payload[] = [
                'student_id' => $studentId,
                'status' => $record['status'],
                'notes' => $record['notes'] ?? null,
            ];
        }

        if ($payload) {
            $this->attendanceService->markBulkAttendance($this->class->id, $payload, $markedByUserId);
        }
    }

    public function tracksAttendance(): bool
    {
        return (bool) $this->class->is_attendance_required;
    }

    public function model(): mixed
    {
        return $this->class;
    }
}
