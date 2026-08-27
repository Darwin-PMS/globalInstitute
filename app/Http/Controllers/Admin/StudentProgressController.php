<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\StudentLessonProgress;
use App\Models\Lesson;
use App\Models\CourseModule;
use Illuminate\Http\Request;

class StudentProgressController extends Controller
{
    public function index(Request $request)
    {
        $query = Enrollment::with(['student.user', 'course'])->where('status', 'active');
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student.user', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }
        $enrollments = $query->latest()->paginate(20)->withQueryString();
        $courses = \App\Models\Course::orderBy('title')->get();
        return view('admin.progress.index', compact('enrollments', 'courses'));
    }

    public function show(Enrollment $enrollment)
    {
        $enrollment->load(['student.user', 'course']);
        $courseId = $enrollment->course_id;
        $studentId = $enrollment->student_id;

        $modules = CourseModule::where('course_id', $courseId)->with(['lessons' => function ($q) use ($studentId) {
            $q->with(['progress' => function ($pq) use ($studentId) {
                $pq->where('student_id', $studentId);
            }]);
        }])->get();

        $totalLessons = Lesson::where('course_id', $courseId)->count();
        $completedLessons = StudentLessonProgress::where('student_id', $studentId)
            ->whereHas('lesson', fn($q) => $q->where('course_id', $courseId))
            ->whereNotNull('completed_at')
            ->count();

        $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        return view('admin.progress.show', compact('enrollment', 'modules', 'totalLessons', 'completedLessons', 'progress'));
    }
}
