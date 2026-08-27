<?php
namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\OnlineClass;
use App\Models\Course;
use App\Models\Batch;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\Trainer;
use App\Domains\OnlineLearning\Services\ConflictDetectionService;
use Illuminate\Http\Request;

class OnlineClassApiController extends Controller
{
    public function __construct(protected ConflictDetectionService $conflictService) {}

    public function index(Request $request)
    {
        $query = OnlineClass::with(['course', 'batch', 'teacher', 'module']);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('course_id')) $query->where('course_id', $request->course_id);
        if ($request->filled('date')) $query->where('class_date', $request->date);
        if ($request->filled('search')) $query->where('title', 'like', "%{$request->search}%");
        $paginated = $query->latest('class_date')->latest('start_time')
            ->paginate(min((int) $request->get('per_page', 20), 100));

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
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
            $validated['teacher_id'], $validated['batch_id'],
            $validated['class_date'], $validated['start_time'], $validated['end_time']
        );
        if (!$validation['valid']) {
            return response()->json(['success' => false, 'message' => $validation['conflicts'][0]['message']], 422);
        }

        $validated['created_by'] = auth()->id();
        $validated['timezone'] = config('online-learning.timezone', 'Asia/Kolkata');
        $onlineClass = OnlineClass::create($validated);
        return response()->json([
            'success' => true,
            'message' => 'Class scheduled.',
            'data' => $onlineClass->load(['course', 'batch', 'teacher', 'module']),
        ]);
    }

    public function show(OnlineClass $onlineClass)
    {
        $onlineClass->load(['course', 'batch', 'teacher', 'module', 'lesson']);
        return response()->json(['success' => true, 'data' => $onlineClass]);
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
            $validated['teacher_id'], $validated['batch_id'],
            $validated['class_date'], $validated['start_time'], $validated['end_time'], $onlineClass->id
        );
        if (!$validation['valid']) {
            return response()->json(['success' => false, 'message' => $validation['conflicts'][0]['message']], 422);
        }

        $onlineClass->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Class updated.',
            'data' => $onlineClass->fresh(['course', 'batch', 'teacher', 'module']),
        ]);
    }

    public function destroy(OnlineClass $onlineClass)
    {
        $onlineClass->delete();
        return response()->json(['success' => true, 'message' => 'Class deleted.']);
    }

    public function formData()
    {
        return response()->json([
            'success' => true,
            'courses' => Course::orderBy('title')->get(['id', 'title']),
            'batches' => Batch::orderBy('batch_name')->get(['id', 'batch_name']),
            'modules' => CourseModule::orderBy('sequence')->get(['id', 'name', 'course_id']),
            'lessons' => Lesson::orderBy('title')->get(['id', 'title', 'course_id']),
            'teachers' => Trainer::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
