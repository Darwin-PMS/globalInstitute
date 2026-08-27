<?php
namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\StudentLessonProgress;
use Illuminate\Http\Request;

class ProgressApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Enrollment::with(['student.user', 'course'])->where('status', 'active');
        if ($request->filled('course_id')) $query->where('course_id', $request->course_id);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('student.user', fn($q) => $q->where('name', 'like', "%{$s}%"));
        }
        return response()->json($query->latest()->paginate($request->get('per_page', 20)));
    }

    public function show(Enrollment $enrollment)
    {
        $enrollment->load(['student.user', 'course']);
        $courseId = $enrollment->course_id;
        $studentId = $enrollment->student_id;

        $modules = CourseModule::where('course_id', $courseId)->with(['lessons' => function ($q) use ($studentId, $enrollment) {
            $q->with(['progress' => function ($pq) use ($studentId, $enrollment) {
                $pq->where('student_id', $studentId)->where('enrollment_id', $enrollment->id);
            }]);
        }])->get();

        $totalLessons = Lesson::where('course_id', $courseId)->count();
        $completedLessons = StudentLessonProgress::where('student_id', $studentId)
            ->where('enrollment_id', $enrollment->id)
            ->whereNotNull('completed_at')
            ->count();

        return response()->json([
            'enrollment' => $enrollment,
            'modules' => $modules,
            'totalLessons' => $totalLessons,
            'completedLessons' => $completedLessons,
            'progress' => $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0,
        ]);
    }
}
