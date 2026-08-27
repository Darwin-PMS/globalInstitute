<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OnlineClass;
use App\Models\Course;
use App\Models\Batch;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\Trainer;
use App\Domains\OnlineLearning\Services\ConflictDetectionService;
use App\Domains\OnlineLearning\Services\NotificationService;
use Illuminate\Http\Request;

class OnlineClassController extends Controller
{
    public function __construct(
        protected ConflictDetectionService $conflictService,
        protected NotificationService $notificationService,
    ) {}

    public function index(Request $request)
    {
        $query = OnlineClass::with(['course', 'batch', 'teacher', 'module']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->filled('date')) {
            $query->where('class_date', $request->date);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }
        $classes = $query->latest('class_date')->latest('start_time')->paginate(20)->withQueryString();
        $courses = Course::orderBy('title')->get();
        $totalClasses = OnlineClass::count();
        $scheduledClasses = OnlineClass::where('status', 'scheduled')->count();
        $completedClasses = OnlineClass::where('status', 'completed')->count();
        $cancelledClasses = OnlineClass::where('status', 'cancelled')->count();
        return view('admin.online-classes.index', compact('classes', 'courses', 'totalClasses', 'scheduledClasses', 'completedClasses', 'cancelledClasses'));
    }

    public function create()
    {
        $courses = Course::orderBy('title')->get();
        $batches = Batch::orderBy('batch_name')->get();
        $modules = CourseModule::orderBy('name')->get();
        $lessons = Lesson::orderBy('title')->get();
        $teachers = Trainer::orderBy('name')->get();
        return view('admin.online-classes.create', compact('courses', 'batches', 'modules', 'lessons', 'teachers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'batch_id' => 'required|exists:batches,id',
            'module_id' => 'nullable|exists:course_modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'teacher_id' => 'required|exists:trainers,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'class_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'duration' => 'nullable|integer|min:0',
            'meeting_provider' => 'required|in:google_meet,zoom,teams,other',
            'meeting_url' => 'nullable|url|max:500',
            'meeting_id' => 'nullable|string|max:100',
            'meeting_password' => 'nullable|string|max:100',
            'is_attendance_required' => 'boolean',
            'status' => 'required|in:scheduled,starting_soon,live,completed,cancelled,postponed',
        ]);

        $validation = $this->conflictService->validateClassCreation(
            $validated['teacher_id'],
            $validated['batch_id'],
            $validated['class_date'],
            $validated['start_time'],
            $validated['end_time']
        );

        if (!$validation['valid']) {
            $firstConflict = $validation['conflicts'][0];
            return back()->withErrors(['teacher_id' => $firstConflict['message']])->withInput();
        }

        $validated['created_by'] = auth()->id();
        $validated['timezone'] = config('online-learning.timezone', 'Asia/Kolkata');
        $onlineClass = OnlineClass::create($validated);

        $this->notificationService->classScheduled(
            \App\Models\Enrollment::where('course_id', $validated['course_id'])
                ->where('batch_id', $validated['batch_id'])
                ->where('status', 'active')
                ->pluck('student_id')
                ->toArray(),
            $validated['title'],
            $validated['class_date'],
            $validated['start_time']
        );

        return redirect()->route('admin.online-classes.index')->with('success', 'Online class scheduled successfully.');
    }

    public function edit(OnlineClass $onlineClass)
    {
        $courses = Course::orderBy('title')->get();
        $batches = Batch::orderBy('batch_name')->get();
        $modules = CourseModule::orderBy('name')->get();
        $lessons = Lesson::orderBy('title')->get();
        $teachers = Trainer::orderBy('name')->get();
        return view('admin.online-classes.edit', compact('onlineClass', 'courses', 'batches', 'modules', 'lessons', 'teachers'));
    }

    public function update(Request $request, OnlineClass $onlineClass)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'batch_id' => 'required|exists:batches,id',
            'module_id' => 'nullable|exists:course_modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'teacher_id' => 'required|exists:trainers,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'class_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'duration' => 'nullable|integer|min:0',
            'meeting_provider' => 'required|in:google_meet,zoom,teams,other',
            'meeting_url' => 'nullable|url|max:500',
            'meeting_id' => 'nullable|string|max:100',
            'meeting_password' => 'nullable|string|max:100',
            'is_attendance_required' => 'boolean',
            'status' => 'required|in:scheduled,starting_soon,live,completed,cancelled,postponed',
            'cancelled_reason' => 'nullable|string|max:500',
        ]);

        $validation = $this->conflictService->validateClassCreation(
            $validated['teacher_id'],
            $validated['batch_id'],
            $validated['class_date'],
            $validated['start_time'],
            $validated['end_time'],
            $onlineClass->id
        );

        if (!$validation['valid']) {
            $firstConflict = $validation['conflicts'][0];
            return back()->withErrors(['teacher_id' => $firstConflict['message']])->withInput();
        }

        $onlineClass->update($validated);
        return redirect()->route('admin.online-classes.index')->with('success', 'Online class updated successfully.');
    }

    public function destroy(OnlineClass $onlineClass)
    {
        $studentIds = \App\Models\Enrollment::where('course_id', $onlineClass->course_id)
            ->where('batch_id', $onlineClass->batch_id)
            ->where('status', 'active')
            ->pluck('student_id')
            ->toArray();

        $this->notificationService->classCancelled(
            $studentIds,
            $onlineClass->title,
            $onlineClass->cancelled_reason ?? ''
        );

        $onlineClass->delete();
        return redirect()->route('admin.online-classes.index')->with('success', 'Online class deleted successfully.');
    }
}
