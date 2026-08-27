<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnlineClass;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Trainer;
use App\Models\Enrollment;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Recording;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentLessonProgress;
use App\Models\LearningMaterial;
use App\Domains\OnlineLearning\Services\ConflictDetectionService;
use App\Domains\OnlineLearning\Services\AttendanceService;
use App\Domains\OnlineLearning\Services\JoinAuthorizationService;
use App\Domains\OnlineLearning\Services\ProgressService;
use App\Domains\OnlineLearning\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OnlineLearningApiController extends Controller
{
    public function __construct(
        protected ConflictDetectionService $conflictService,
        protected AttendanceService $attendanceService,
        protected JoinAuthorizationService $joinService,
        protected ProgressService $progressService,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Get Student LMS Dashboard Payload
     */
    public function studentDashboard(Request $request)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student profile not found for this account.'
            ], 404);
        }

        $enrollments = Enrollment::where('student_id', $student->id)
            ->where('status', 'active')
            ->with(['course', 'batch'])
            ->get();

        $courseIds = $enrollments->pluck('course_id');

        // Next class countdown target
        $nextClass = OnlineClass::whereIn('course_id', $courseIds)
            ->where('class_date', '>=', today())
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->where('class_date', '>', today())
                  ->orWhere('end_time', '>=', now()->format('H:i:s'));
            })
            ->with(['course', 'teacher', 'batch'])
            ->orderBy('class_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->first();

        $todayClasses = OnlineClass::whereIn('course_id', $courseIds)
            ->where('class_date', today())
            ->where('status', '!=', 'cancelled')
            ->with(['course', 'teacher', 'batch'])
            ->orderBy('start_time', 'asc')
            ->get();

        $upcomingClasses = OnlineClass::whereIn('course_id', $courseIds)
            ->where('class_date', '>=', today())
            ->where('status', '!=', 'cancelled')
            ->with(['course', 'teacher', 'batch'])
            ->orderBy('class_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit(6)
            ->get();

        $pendingAssignments = Assignment::whereIn('course_id', $courseIds)
            ->where('status', 'published')
            ->where('due_date', '>=', now())
            ->with(['course'])
            ->withCount(['submissions' => function ($q) use ($student) {
                $q->where('student_id', $student->id);
            }])
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        $recentRecordings = Recording::whereHas('onlineClass', function ($q) use ($courseIds) {
            $q->whereIn('course_id', $courseIds);
        })
            ->where('is_published', true)
            ->with('onlineClass.course')
            ->latest()
            ->limit(5)
            ->get();

        $totalLessons = Lesson::whereIn('course_id', $courseIds)->count();
        $completedLessons = StudentLessonProgress::where('student_id', $student->id)
            ->whereNotNull('completed_at')
            ->count();
        $progressPercent = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'student_code' => $student->student_id ?? null,
                    'avatar' => $student->avatar ?? null,
                ],
                'next_class' => $nextClass,
                'today_classes' => $todayClasses,
                'upcoming_classes' => $upcomingClasses,
                'enrollments' => $enrollments,
                'pending_assignments' => $pendingAssignments,
                'recent_recordings' => $recentRecordings,
                'stats' => [
                    'total_courses' => $enrollments->count(),
                    'total_lessons' => $totalLessons,
                    'completed_lessons' => $completedLessons,
                    'progress_percent' => $progressPercent,
                    'pending_assignments_count' => $pendingAssignments->count(),
                ]
            ]
        ]);
    }

    /**
     * Get Student Classes List
     */
    public function studentClasses(Request $request)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->firstOrFail();
        $courseIds = Enrollment::where('student_id', $student->id)->where('status', 'active')->pluck('course_id');

        $query = OnlineClass::whereIn('course_id', $courseIds)
            ->where('status', '!=', 'cancelled')
            ->with(['course', 'teacher', 'batch', 'module']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $classes = $query->latest('class_date')->latest('start_time')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $classes->items(),
            'meta' => [
                'current_page' => $classes->currentPage(),
                'last_page' => $classes->lastPage(),
                'total' => $classes->total(),
                'per_page' => $classes->perPage(),
            ]
        ]);
    }

    /**
     * Student Course Detail Endpoint
     */
    public function studentCourseDetail($courseId)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->firstOrFail();

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $courseId)
            ->with('course')
            ->firstOrFail();

        $modules = CourseModule::where('course_id', $courseId)
            ->with(['lessons' => function ($q) use ($student, $enrollment) {
                $q->with(['progress' => function ($pq) use ($student, $enrollment) {
                    $pq->where('student_id', $student->id)
                       ->where('enrollment_id', $enrollment->id);
                }]);
            }])
            ->get();

        $courseProgress = $this->progressService->calculateCourseProgress(
            $student->id,
            $enrollment->id,
            $courseId
        );

        $classes = OnlineClass::where('course_id', $courseId)
            ->where('status', '!=', 'cancelled')
            ->with('teacher')
            ->latest('class_date')
            ->limit(10)
            ->get();

        $assignments = Assignment::where('course_id', $courseId)
            ->where('status', 'published')
            ->with(['submissions' => function ($q) use ($student) {
                $q->where('student_id', $student->id);
            }])
            ->latest()
            ->get();

        $materials = LearningMaterial::where('course_id', $courseId)
            ->where('is_published', true)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'enrollment' => $enrollment,
                'course' => $enrollment->course,
                'modules' => $modules,
                'classes' => $classes,
                'assignments' => $assignments,
                'materials' => $materials,
                'progress' => $courseProgress,
            ]
        ]);
    }

    /**
     * Join Online Class (Student Authorization & Timestamp Audit)
     */
    public function joinClass(Request $request, OnlineClass $onlineClass)
    {
        $user = Auth::user();

        // Admin or Teacher join
        if ($user->is_admin) {
            return response()->json([
                'success' => true,
                'meeting_url' => $onlineClass->meeting_url,
                'message' => 'Joining as Admin.'
            ]);
        }

        $teacher = Trainer::where('user_id', $user->id)->first();
        if ($teacher && $onlineClass->teacher_id === $teacher->id) {
            // Update class status to live if scheduled
            if ($onlineClass->status === 'scheduled' || $onlineClass->status === 'starting_soon') {
                $onlineClass->update(['status' => 'live']);
            }
            return response()->json([
                'success' => true,
                'meeting_url' => $onlineClass->meeting_url,
                'message' => 'Joining class as Host/Teacher.'
            ]);
        }

        // Student join check
        $student = Student::where('user_id', $user->id)->first();
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized profile.'
            ], 403);
        }

        $authResult = $this->joinService->canStudentJoin($student->id, $onlineClass);

        if (!$authResult['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $authResult['reason']
            ], 403);
        }

        // Log join timestamp in attendance
        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $onlineClass->course_id)
            ->first();

        $this->attendanceService->markAttendance(
            $onlineClass->id,
            $student->id,
            $enrollment?->id,
            'present',
            'Joined live session via SPA at ' . now()->toTimeString(),
            $user->id
        );

        return response()->json([
            'success' => true,
            'meeting_url' => $authResult['meeting_url'],
            'message' => 'Authorization successful. Redirecting to class session...'
        ]);
    }

    /**
     * Teacher LMS Dashboard Payload
     */
    public function teacherDashboard(Request $request)
    {
        $user = Auth::user();
        $teacher = Trainer::where('user_id', $user->id)->first();

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher profile not found for this account.'
            ], 404);
        }

        $todayClasses = OnlineClass::where('teacher_id', $teacher->id)
            ->where('class_date', today())
            ->where('status', '!=', 'cancelled')
            ->with(['course', 'batch'])
            ->orderBy('start_time', 'asc')
            ->get();

        $upcomingClasses = OnlineClass::where('teacher_id', $teacher->id)
            ->where('class_date', '>=', today())
            ->where('status', '!=', 'cancelled')
            ->with(['course', 'batch'])
            ->orderBy('class_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit(10)
            ->get();

        $pendingSubmissions = AssignmentSubmission::whereHas('assignment', function ($q) use ($teacher) {
            $q->where('teacher_id', $teacher->id);
        })
            ->where('status', 'submitted')
            ->with(['assignment.course', 'student.user'])
            ->latest()
            ->limit(10)
            ->get();

        $totalClasses = OnlineClass::where('teacher_id', $teacher->id)->count();
        $completedClasses = OnlineClass::where('teacher_id', $teacher->id)->where('status', 'completed')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'teacher' => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'email' => $teacher->email ?? $user->email,
                ],
                'today_classes' => $todayClasses,
                'upcoming_classes' => $upcomingClasses,
                'pending_submissions' => $pendingSubmissions,
                'stats' => [
                    'total_classes' => $totalClasses,
                    'completed_classes' => $completedClasses,
                    'pending_submissions_count' => $pendingSubmissions->count(),
                ]
            ]
        ]);
    }

    /**
     * Teacher Classes List Endpoint
     */
    public function teacherClasses(Request $request)
    {
        $user = Auth::user();
        $teacher = Trainer::where('user_id', $user->id)->firstOrFail();

        $query = OnlineClass::where('teacher_id', $teacher->id)
            ->with(['course', 'batch', 'module']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date')) {
            $query->where('class_date', $request->date);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $classes = $query->latest('class_date')->latest('start_time')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $classes->items(),
            'meta' => [
                'current_page' => $classes->currentPage(),
                'last_page' => $classes->lastPage(),
                'total' => $classes->total(),
                'per_page' => $classes->perPage(),
            ]
        ]);
    }

    /**
     * Get Class Attendance & Enrolled Students for Marking
     */
    public function getClassAttendance(OnlineClass $onlineClass)
    {
        $enrolledStudents = Student::whereHas('enrollments', function ($q) use ($onlineClass) {
            $q->where('course_id', $onlineClass->course_id)
              ->where('batch_id', $onlineClass->batch_id)
              ->where('status', 'active');
        })->with('user')->get();

        $existingAttendance = $this->attendanceService->getClassAttendance($onlineClass->id)->keyBy('student_id');

        $studentsWithAttendance = $enrolledStudents->map(function ($student) use ($existingAttendance) {
            $record = $existingAttendance->get($student->id);
            return [
                'student_id' => $student->id,
                'name' => $student->user->name ?? $student->first_name . ' ' . $student->last_name,
                'student_code' => $student->student_id,
                'status' => $record ? $record->status : 'absent',
                'notes' => $record ? $record->notes : '',
                'joined_at' => $record ? $record->joined_at : null,
            ];
        });

        $stats = $this->attendanceService->getAttendanceStats($onlineClass->id);

        return response()->json([
            'success' => true,
            'data' => [
                'class' => $onlineClass->load(['course', 'batch', 'teacher']),
                'students' => $studentsWithAttendance,
                'stats' => $stats,
            ]
        ]);
    }

    /**
     * Post Bulk Attendance
     */
    public function markAttendance(Request $request, OnlineClass $onlineClass)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:present,absent,late,partial',
            'attendance.*.notes' => 'nullable|string|max:255',
        ]);

        $this->attendanceService->markBulkAttendance($onlineClass->id, $validated['attendance'], $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Attendance marked successfully for ' . count($validated['attendance']) . ' students.'
        ]);
    }

    /**
     * Reschedule Class Endpoint with Conflict Validation
     */
    public function rescheduleClass(Request $request, OnlineClass $onlineClass)
    {
        $validated = $request->validate([
            'class_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'reason' => 'nullable|string|max:500',
        ]);

        $validation = $this->conflictService->validateClassCreation(
            $onlineClass->teacher_id,
            $onlineClass->batch_id,
            $validated['class_date'],
            $validated['start_time'],
            $validated['end_time'],
            $onlineClass->id
        );

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validation['conflicts'][0]['message'],
                'conflicts' => $validation['conflicts'],
            ], 422);
        }

        $oldDate = $onlineClass->class_date->format('d M Y') . ' ' . $onlineClass->timeOnly('start_time');

        $onlineClass->update([
            'class_date' => $validated['class_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'rescheduled_from' => $onlineClass->class_date,
            'status' => 'postponed',
        ]);

        $this->notificationService->classRescheduled(
            $this->notifiableUserIds($onlineClass),
            $onlineClass->title,
            $validated['class_date'],
            $validated['start_time']
        );

        return response()->json([
            'success' => true,
            'message' => 'Class rescheduled successfully from ' . $oldDate . ' to ' . $validated['class_date'] . ' ' . $validated['start_time'],
            'class' => $onlineClass->fresh(['course', 'batch', 'teacher'])
        ]);
    }

    /**
     * Cancel Class Endpoint
     */
    public function cancelClass(Request $request, OnlineClass $onlineClass)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $onlineClass->update([
            'status' => 'cancelled',
            'cancelled_reason' => $validated['reason'],
        ]);

        $this->notificationService->classCancelled(
            $this->notifiableUserIds($onlineClass),
            $onlineClass->title,
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'Class has been cancelled.',
            'class' => $onlineClass->fresh()
        ]);
    }

    /**
     * User ids for the students actively enrolled in a class.
     *
     * lms_notifications.user_id is a foreign key to `users`, so the enrolled
     * students' student ids have to be translated first - passing them straight
     * through addressed notifications to whichever user happened to share the id.
     *
     * @return array<int, int>
     */
    protected function notifiableUserIds(OnlineClass $onlineClass): array
    {
        $studentIds = Enrollment::where('course_id', $onlineClass->course_id)
            ->when($onlineClass->batch_id, fn ($q) => $q->where('batch_id', $onlineClass->batch_id))
            ->where('status', 'active')
            ->pluck('student_id');

        return Student::whereIn('id', $studentIds)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->all();
    }
}
