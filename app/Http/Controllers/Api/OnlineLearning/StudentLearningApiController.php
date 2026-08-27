<?php

namespace App\Http\Controllers\Api\OnlineLearning;

use App\Domains\OnlineLearning\Services\AttendanceService;
use App\Domains\OnlineLearning\Services\ProgressService;
use App\Http\Controllers\Api\OnlineLearning\Concerns\ApiResponses;
use App\Http\Controllers\Api\OnlineLearning\Concerns\ResolvesLearningActor;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Enrollment;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\Recording;
use App\Models\StudentLessonProgress;
use Illuminate\Http\Request;

/**
 * Student-only Online Learning screens:
 * recordings library, assignments (list + submit), materials, progress.
 */
class StudentLearningApiController extends Controller
{
    use ApiResponses;
    use ResolvesLearningActor;

    public function __construct(
        protected ProgressService $progressService,
        protected AttendanceService $attendanceService,
    ) {}

    /**
     * GET /api/online-learning/student/recordings
     */
    public function recordings(Request $request)
    {
        $student = $this->actingStudent();
        if (! $student) {
            return $this->notFound('Student profile not found for this account.');
        }

        $courseIds = $this->enrolledCourseIds($student);

        $query = Recording::whereHas('onlineClass', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->where('is_published', true)
            ->with('onlineClass.course', 'onlineClass.teacher');

        if ($request->filled('course_id')) {
            // Reject a course the student is not enrolled in rather than silently widening.
            if (! $courseIds->contains((int) $request->course_id)) {
                return $this->forbidden('You are not enrolled in that course.');
            }
            $query->whereHas('onlineClass', fn ($q) => $q->where('course_id', $request->course_id));
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        return $this->paginated(
            $query->latest()->paginate($this->perPage($request->get('per_page')))
        );
    }

    /**
     * GET /api/online-learning/student/assignments
     */
    public function assignments(Request $request)
    {
        $student = $this->actingStudent();
        if (! $student) {
            return $this->notFound('Student profile not found for this account.');
        }

        $courseIds = $this->enrolledCourseIds($student);

        $query = Assignment::whereIn('course_id', $courseIds)
            ->where('status', 'published')
            ->with(['course', 'teacher'])
            ->with(['submissions' => fn ($q) => $q->where('student_id', $student->id)]);

        // filter=pending|submitted|graded|overdue
        $filter = $request->get('filter');
        if ($filter === 'pending') {
            $query->whereDoesntHave('submissions', fn ($q) => $q->where('student_id', $student->id));
        } elseif ($filter === 'submitted') {
            $query->whereHas('submissions', fn ($q) => $q->where('student_id', $student->id)
                ->whereIn('status', ['submitted', 'late']));
        } elseif ($filter === 'graded') {
            $query->whereHas('submissions', fn ($q) => $q->where('student_id', $student->id)
                ->whereNotNull('marks'));
        } elseif ($filter === 'overdue') {
            $query->where('due_date', '<', now())
                ->whereDoesntHave('submissions', fn ($q) => $q->where('student_id', $student->id));
        }

        if ($request->filled('course_id')) {
            if (! $courseIds->contains((int) $request->course_id)) {
                return $this->forbidden('You are not enrolled in that course.');
            }
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $paginated = $query->orderByRaw('due_date IS NULL, due_date ASC')
            ->paginate($this->perPage($request->get('per_page')));

        // Counters for the filter chips - computed over the whole set, not the page.
        $base = Assignment::whereIn('course_id', $courseIds)->where('status', 'published');

        $response = $this->paginated($paginated);
        $data = $response->getData(true);
        $data['meta']['counts'] = [
            'all' => (clone $base)->count(),
            'pending' => (clone $base)->whereDoesntHave('submissions', fn ($q) => $q->where('student_id', $student->id))->count(),
            'submitted' => (clone $base)->whereHas('submissions', fn ($q) => $q->where('student_id', $student->id)->whereIn('status', ['submitted', 'late']))->count(),
            'graded' => (clone $base)->whereHas('submissions', fn ($q) => $q->where('student_id', $student->id)->whereNotNull('marks'))->count(),
            'overdue' => (clone $base)->where('due_date', '<', now())
                ->whereDoesntHave('submissions', fn ($q) => $q->where('student_id', $student->id))->count(),
        ];

        return response()->json($data);
    }

    /**
     * POST /api/online-learning/student/assignments/{assignment}/submit
     */
    public function submitAssignment(Request $request, Assignment $assignment)
    {
        $student = $this->actingStudent();
        if (! $student) {
            return $this->notFound('Student profile not found for this account.');
        }

        if ($assignment->status !== 'published') {
            return $this->fail('This assignment is not open for submission.', 422);
        }

        // enrollment_id is NOT NULL on assignment_submissions - resolve it before writing.
        $enrollment = $this->enrollmentFor($student, $assignment->course_id);
        if (! $enrollment) {
            return $this->forbidden('You are not actively enrolled in this course.');
        }

        $validated = $request->validate([
            'submission_text' => 'required_without:file|nullable|string|max:20000',
            'file' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,jpg,jpeg,png',
        ]);

        $existing = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        $filePath = $existing?->file_path;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('assignment-submissions', 'public');
        }

        // Late if past the due date; the server decides this, not the client.
        $isLate = $assignment->due_date && now()->greaterThan($assignment->due_date);

        $submission = AssignmentSubmission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id],
            [
                'enrollment_id' => $enrollment->id,
                'submission_text' => $validated['submission_text'] ?? null,
                'file_path' => $filePath,
                'submitted_at' => now(),
                'status' => $isLate ? 'late' : 'submitted',
            ]
        );

        return $this->ok(
            $submission->fresh(),
            $isLate
                ? 'Assignment submitted after the due date and marked late.'
                : 'Assignment submitted successfully.'
        );
    }

    /**
     * GET /api/online-learning/student/materials
     */
    public function materials(Request $request)
    {
        $student = $this->actingStudent();
        if (! $student) {
            return $this->notFound('Student profile not found for this account.');
        }

        $courseIds = $this->enrolledCourseIds($student);

        $query = LearningMaterial::whereIn('course_id', $courseIds)
            ->where('is_published', true)
            ->with('course');

        if ($request->filled('course_id')) {
            if (! $courseIds->contains((int) $request->course_id)) {
                return $this->forbidden('You are not enrolled in that course.');
            }
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('file_type')) {
            $query->where('file_type', $request->file_type);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        return $this->paginated(
            $query->latest()->paginate($this->perPage($request->get('per_page')))
        );
    }

    /**
     * GET /api/online-learning/student/progress
     *
     * Per-course completion + attendance rate, plus an overall roll-up.
     */
    public function progress()
    {
        $student = $this->actingStudent();
        if (! $student) {
            return $this->notFound('Student profile not found for this account.');
        }

        $enrollments = Enrollment::where('student_id', $student->id)
            ->where('status', 'active')
            ->with(['course', 'batch'])
            ->get();

        $courses = $enrollments->map(function ($enrollment) use ($student) {
            $totalLessons = Lesson::where('course_id', $enrollment->course_id)->count();

            $completedLessons = StudentLessonProgress::where('student_id', $student->id)
                ->where('enrollment_id', $enrollment->id)
                ->whereNotNull('completed_at')
                ->count();

            return [
                'enrollment_id' => $enrollment->id,
                'course_id' => $enrollment->course_id,
                'course_title' => $enrollment->course->title ?? 'Course',
                'batch' => $enrollment->batch->batch_name ?? null,
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'progress_percent' => $this->progressService->calculateCourseProgress(
                    $student->id,
                    $enrollment->id,
                    $enrollment->course_id
                ),
                'attendance_rate' => round(
                    $this->attendanceService->getStudentAttendanceRate($student->id, $enrollment->course_id),
                    1
                ),
            ];
        });

        $totalLessons = (int) $courses->sum('total_lessons');
        $completedLessons = (int) $courses->sum('completed_lessons');

        return $this->ok([
            'courses' => $courses->values(),
            'overall' => [
                'courses_count' => $courses->count(),
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'progress_percent' => $totalLessons > 0
                    ? (int) round(($completedLessons / $totalLessons) * 100)
                    : 0,
                'average_attendance' => $courses->count() > 0
                    ? round($courses->avg('attendance_rate'), 1)
                    : 0.0,
            ],
        ]);
    }
}
