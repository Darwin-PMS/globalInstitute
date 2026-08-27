<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Trainer;
use App\Models\OnlineClass;
use App\Models\ClassAttendance;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Recording;
use App\Models\Course;
use App\Models\Student;
use App\Models\Enrollment;
use App\Domains\OnlineLearning\Services\AttendanceService;
use App\Domains\OnlineLearning\Services\JoinAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherDashboardController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService,
        protected JoinAuthorizationService $joinService,
    ) {}

    public function index()
    {
        $user = Auth::user();
        $teacher = Trainer::where('user_id', $user->id)->first();

        if (!$teacher) {
            return redirect()->route('frontend.home')->with('error', 'Teacher profile not found.');
        }

        $todayClasses = OnlineClass::where('teacher_id', $teacher->id)
            ->where('class_date', today())
            ->where('status', '!=', 'cancelled')
            ->with(['course', 'batch'])
            ->get();

        $upcomingClasses = OnlineClass::where('teacher_id', $teacher->id)
            ->where('class_date', '>=', today())
            ->where('status', '!=', 'cancelled')
            ->with(['course', 'batch'])
            ->orderBy('class_date')
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        $pendingSubmissions = AssignmentSubmission::whereHas('assignment', function ($q) use ($teacher) {
            $q->where('teacher_id', $teacher->id);
        })
            ->where('status', 'submitted')
            ->with(['assignment', 'student.user'])
            ->latest()
            ->limit(10)
            ->get();

        $pendingQuizAttempts = QuizAttempt::whereHas('quiz', function ($q) use ($teacher) {
            $q->where('teacher_id', $teacher->id);
        })
            ->whereNull('score')
            ->with(['quiz', 'student.user'])
            ->latest()
            ->limit(10)
            ->get();

        $totalClasses = OnlineClass::where('teacher_id', $teacher->id)->count();
        $completedClasses = OnlineClass::where('teacher_id', $teacher->id)->where('status', 'completed')->count();
        $totalStudents = Enrollment::whereIn('course_id', function ($q) use ($teacher) {
            $q->select('course_id')->from('online_classes')->where('teacher_id', $teacher->id);
        })->where('status', 'active')->count();

        return view('frontend.teacher.dashboard', compact(
            'teacher', 'todayClasses', 'upcomingClasses', 'pendingSubmissions',
            'pendingQuizAttempts', 'totalClasses', 'completedClasses', 'totalStudents'
        ));
    }

    public function myClasses()
    {
        $user = Auth::user();
        $teacher = Trainer::where('user_id', $user->id)->first();

        $classes = OnlineClass::where('teacher_id', $teacher->id)
            ->with(['course', 'batch'])
            ->latest('class_date')
            ->paginate(20);

        return view('frontend.teacher.my-classes', compact('classes'));
    }

    public function markAttendance(OnlineClass $onlineClass, Request $request)
    {
        $user = Auth::user();
        $teacher = Trainer::where('user_id', $user->id)->first();

        if (!$this->joinService->canTeacherAccess($teacher->id, $onlineClass)) {
            return back()->with('error', 'Unauthorized.');
        }

        $validated = $request->validate([
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:present,absent,late,excused',
            'attendance.*.notes' => 'nullable|string|max:255',
        ]);

        $records = array_map(fn($r) => [
            'student_id' => $r['student_id'],
            'status' => $r['status'],
            'notes' => $r['notes'] ?? null,
        ], $validated['attendance']);

        $this->attendanceService->markBulkAttendance($onlineClass->id, $records, $teacher->id);

        return back()->with('success', 'Attendance marked successfully.');
    }

    public function submissions()
    {
        $user = Auth::user();
        $teacher = Trainer::where('user_id', $user->id)->first();

        $submissions = AssignmentSubmission::whereHas('assignment', function ($q) use ($teacher) {
            $q->where('teacher_id', $teacher->id);
        })
            ->with(['assignment', 'student.user'])
            ->latest()
            ->paginate(20);

        return view('frontend.teacher.submissions', compact('submissions'));
    }

    public function evaluateSubmission(AssignmentSubmission $submission, Request $request)
    {
        $validated = $request->validate([
            'marks' => 'required|integer|min:0',
            'teacher_feedback' => 'nullable|string|max:1000',
        ]);

        $validated['status'] = 'reviewed';
        $validated['evaluated_at'] = now();

        $submission->update($validated);

        return back()->with('success', 'Submission evaluated successfully.');
    }

    public function quizResults()
    {
        $user = Auth::user();
        $teacher = Trainer::where('user_id', $user->id)->first();

        $attempts = QuizAttempt::whereHas('quiz', function ($q) use ($teacher) {
            $q->where('teacher_id', $teacher->id);
        })
            ->with(['quiz', 'student.user'])
            ->latest()
            ->paginate(20);

        return view('frontend.teacher.quiz-results', compact('attempts'));
    }
}
