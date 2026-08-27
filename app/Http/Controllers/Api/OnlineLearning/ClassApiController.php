<?php

namespace App\Http\Controllers\Api\OnlineLearning;

use App\Domains\OnlineLearning\Services\AttendanceService;
use App\Domains\OnlineLearning\Services\JoinAuthorizationService;
use App\Http\Controllers\Api\OnlineLearning\Concerns\ApiResponses;
use App\Http\Controllers\Api\OnlineLearning\Concerns\ResolvesLearningActor;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ClassAttendance;
use App\Models\LearningMaterial;
use App\Models\OnlineClass;
use App\Models\Recording;
use Illuminate\Http\Request;

/**
 * Role-aware single-class detail + calendar range.
 *
 * Backs three screens that show the same entity at different depths:
 *  - admin/teacher class drawer
 *  - student live-class screen
 *  - calendar month/week/day/agenda
 */
class ClassApiController extends Controller
{
    use ApiResponses;
    use ResolvesLearningActor;

    public function __construct(
        protected JoinAuthorizationService $joinService,
        protected AttendanceService $attendanceService,
    ) {}

    /**
     * GET /api/online-learning/classes/{onlineClass}
     */
    public function show(OnlineClass $onlineClass)
    {
        if (! $this->canViewClass($onlineClass)) {
            return $this->forbidden('You do not have access to this class.');
        }

        $onlineClass->load(['course', 'batch', 'teacher', 'module', 'lesson']);
        $role = $this->actingRole();

        $recordings = Recording::where('online_class_id', $onlineClass->id)
            ->when($role === 'student', fn ($q) => $q->where('is_published', true))
            ->latest()
            ->get();

        $materials = LearningMaterial::where('class_id', $onlineClass->id)
            ->when($role === 'student', fn ($q) => $q->where('is_published', true))
            ->latest()
            ->get();

        $assignments = Assignment::where('class_id', $onlineClass->id)
            ->when($role === 'student', fn ($q) => $q->where('status', 'published'))
            ->latest()
            ->get();

        // Join eligibility is decided here, server-side, and the meeting URL is
        // withheld from students until POST /join actually authorises them.
        $joinState = $this->resolveJoinState($onlineClass, $role);

        $payload = [
            'class' => $onlineClass,
            'recordings' => $recordings,
            'materials' => $materials,
            'assignments' => $assignments,
            'can_join' => $joinState['can_join'],
            'join_message' => $joinState['message'],
            'role' => $role,
        ];

        // Staff-only sections
        if ($role !== 'student') {
            $payload['attendance_stats'] = $this->attendanceService->getAttendanceStats($onlineClass->id);
            $payload['meeting_url'] = $onlineClass->meeting_url;
            $payload['meeting_id'] = $onlineClass->meeting_id;
            $payload['meeting_password'] = $onlineClass->meeting_password;
        } else {
            // A student sees only their own attendance record for this class.
            $student = $this->actingStudent();
            $payload['my_attendance'] = $student
                ? ClassAttendance::where('online_class_id', $onlineClass->id)
                    ->where('student_id', $student->id)
                    ->first()
                : null;
        }

        return $this->ok($payload);
    }

    /**
     * GET /api/online-learning/calendar?from=YYYY-MM-DD&to=YYYY-MM-DD
     *
     * Scoped by role: admins see everything, teachers their own classes,
     * students the classes of courses they are enrolled in.
     */
    public function calendar(Request $request)
    {
        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'status' => 'nullable|string',
            'course_id' => 'nullable|integer',
            'teacher_id' => 'nullable|integer',
        ]);

        $query = OnlineClass::with(['course', 'batch', 'teacher'])
            ->whereBetween('class_date', [$validated['from'], $validated['to']]);

        $role = $this->actingRole();

        if ($role === 'teacher') {
            $teacher = $this->actingTeacher();
            $query->where('teacher_id', $teacher->id);
        } elseif ($role === 'student') {
            $student = $this->actingStudent();
            if (! $student) {
                return $this->notFound('Student profile not found for this account.');
            }
            $query->whereIn('course_id', $this->enrolledCourseIds($student));
        }

        // Filters are advisory; the role scope above is not negotiable.
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['course_id'])) {
            $query->where('course_id', $validated['course_id']);
        }
        if ($role === 'admin' && ! empty($validated['teacher_id'])) {
            $query->where('teacher_id', $validated['teacher_id']);
        }

        $classes = $query->orderBy('class_date')->orderBy('start_time')->get();

        return $this->ok($classes, '', [
            'from' => $validated['from'],
            'to' => $validated['to'],
            'count' => $classes->count(),
        ]);
    }

    /**
     * Decide whether the acting user may join right now, and why not if they cannot.
     */
    protected function resolveJoinState(OnlineClass $onlineClass, string $role): array
    {
        if ($role === 'student') {
            $student = $this->actingStudent();

            if (! $student) {
                return ['can_join' => false, 'message' => 'Student profile not found.'];
            }

            $result = $this->joinService->canStudentJoin($student->id, $onlineClass);

            return [
                'can_join' => $result['allowed'],
                'message' => $result['allowed'] ? 'You can join this class now.' : $result['reason'],
            ];
        }

        // Staff can open the room whenever it is not a closed class.
        $open = ! in_array($onlineClass->status, ['cancelled'], true) && (bool) $onlineClass->meeting_url;

        return [
            'can_join' => $open,
            'message' => $open
                ? 'You can open the meeting room.'
                : ($onlineClass->status === 'cancelled' ? 'This class was cancelled.' : 'No meeting link has been set.'),
        ];
    }
}
