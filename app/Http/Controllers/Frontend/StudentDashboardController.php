<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\OnlineClass;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Recording;
use App\Models\StudentLessonProgress;
use App\Models\LearningMaterial;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Domains\OnlineLearning\Services\ProgressService;
use App\Domains\OnlineLearning\Services\JoinAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentDashboardController extends Controller
{
    public function __construct(
        protected ProgressService $progressService,
        protected JoinAuthorizationService $joinService,
    ) {}

    public function index()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return redirect()->route('frontend.home')->with('error', 'Student profile not found.');
        }

        $enrollments = Enrollment::where('student_id', $student->id)
            ->where('status', 'active')
            ->with('course')
            ->get();

        $courseIds = $enrollments->pluck('course_id');

        $todayClasses = OnlineClass::whereIn('course_id', $courseIds)
            ->where('class_date', today())
            ->where('status', '!=', 'cancelled')
            ->with(['course', 'teacher'])
            ->get();

        $upcomingClasses = OnlineClass::whereIn('course_id', $courseIds)
            ->where('class_date', '>=', today())
            ->where('class_date', '<=', now()->addDays(7))
            ->where('status', '!=', 'cancelled')
            ->with(['course', 'teacher'])
            ->orderBy('class_date')
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        $pendingAssignments = Assignment::whereIn('course_id', $courseIds)
            ->where('status', 'published')
            ->where('due_date', '>=', now())
            ->with('course')
            ->orderBy('due_date')
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

        $availableQuizzes = Quiz::whereIn('course_id', $courseIds)
            ->where('is_published', true)
            ->with('course')
            ->limit(5)
            ->get();

        return view('frontend.student.dashboard', compact(
            'student', 'enrollments', 'todayClasses', 'upcomingClasses',
            'pendingAssignments', 'recentRecordings', 'totalLessons',
            'completedLessons', 'progressPercent', 'availableQuizzes'
        ));
    }

    public function myCourses()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        $enrollments = Enrollment::where('student_id', $student->id)
            ->with('course')
            ->latest()
            ->get();
        return view('frontend.student.my-courses', compact('enrollments'));
    }

    public function courseDetail($courseId)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $courseId)
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

        return view('frontend.student.course-detail', compact(
            'enrollment', 'modules', 'classes', 'assignments', 'materials', 'courseProgress'
        ));
    }

    public function myClasses()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        $enrollments = Enrollment::where('student_id', $student->id)->where('status', 'active')->pluck('course_id');

        $classes = OnlineClass::whereIn('course_id', $enrollments)
            ->where('status', '!=', 'cancelled')
            ->with(['course', 'teacher'])
            ->latest('class_date')
            ->paginate(20);

        return view('frontend.student.my-classes', compact('classes'));
    }

    public function myAssignments()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        $enrollments = Enrollment::where('student_id', $student->id)->where('status', 'active')->pluck('course_id');

        $assignments = Assignment::whereIn('course_id', $enrollments)
            ->where('status', 'published')
            ->with(['course', 'submissions' => function ($q) use ($student) {
                $q->where('student_id', $student->id);
            }])
            ->latest()
            ->paginate(20);

        return view('frontend.student.my-assignments', compact('assignments'));
    }

    public function myQuizzes()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        $enrollments = Enrollment::where('student_id', $student->id)->where('status', 'active')->pluck('course_id');

        $quizzes = Quiz::whereIn('course_id', $enrollments)
            ->where('is_published', true)
            ->with(['course', 'attempts' => function ($q) use ($student) {
                $q->where('student_id', $student->id);
            }])
            ->latest()
            ->paginate(20);

        return view('frontend.student.my-quizzes', compact('quizzes'));
    }

    public function myRecordings()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        $enrollments = Enrollment::where('student_id', $student->id)->where('status', 'active')->pluck('course_id');

        $recordings = Recording::whereHas('onlineClass', function ($q) use ($enrollments) {
            $q->whereIn('course_id', $enrollments);
        })
            ->where('is_published', true)
            ->with('onlineClass.course')
            ->latest()
            ->paginate(20);

        return view('frontend.student.my-recordings', compact('recordings'));
    }

    public function submitAssignment(Request $request, Assignment $assignment)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $assignment->course_id)
            ->first();

        if (!$enrollment) {
            return back()->with('error', 'You are not enrolled in this course.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
            'attachment' => 'required|file|max:10240',
        ]);

        $filePath = $request->file('attachment')->store('assignments/submissions', 'public');

        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'submission_text' => $validated['notes'] ?? null,
            'file_path' => $filePath,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        return back()->with('success', 'Assignment submitted successfully.');
    }

    public function takeQuiz(Quiz $quiz)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();

        $attempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->count();

        if ($attempts >= $quiz->max_attempts) {
            return back()->with('error', 'You have reached the maximum number of attempts.');
        }

        $quiz->load('questions.options');
        return view('frontend.student.take-quiz', compact('quiz', 'attempts'));
    }
}
