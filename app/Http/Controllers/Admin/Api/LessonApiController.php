<?php
namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\CourseModule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LessonApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Lesson::with(['course', 'module']);
        if ($request->filled('course_id')) $query->where('course_id', $request->course_id);
        if ($request->filled('module_id')) $query->where('module_id', $request->module_id);
        if ($request->filled('search')) $query->where('title', 'like', "%{$request->search}%");
        return response()->json($query->orderBy('sequence')->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'module_id' => 'required|exists:course_modules,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url|max:500',
            'duration' => 'nullable|integer|min:0',
            'sequence' => 'integer|min:0',
            'is_free' => 'boolean',
            'status' => 'required|in:draft,published,archived',
        ]);
        $validated['slug'] = Str::slug($validated['title']);
        $validated['is_published'] = $validated['status'] === 'published';
        $lesson = Lesson::create($validated);
        return response()->json(['success' => true, 'lesson' => $lesson, 'message' => 'Lesson created.']);
    }

    public function update(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'module_id' => 'required|exists:course_modules,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url|max:500',
            'duration' => 'nullable|integer|min:0',
            'sequence' => 'integer|min:0',
            'is_free' => 'boolean',
            'status' => 'required|in:draft,published,archived',
        ]);
        $validated['slug'] = Str::slug($validated['title']);
        $validated['is_published'] = $validated['status'] === 'published';
        $lesson->update($validated);
        return response()->json(['success' => true, 'message' => 'Lesson updated.']);
    }

    public function destroy(Lesson $lesson)
    {
        $lesson->delete();
        return response()->json(['success' => true, 'message' => 'Lesson deleted.']);
    }

    public function modules(Request $request)
    {
        $query = CourseModule::query();
        if ($request->filled('course_id')) $query->where('course_id', $request->course_id);
        return response()->json($query->orderBy('sequence')->get());
    }
}
