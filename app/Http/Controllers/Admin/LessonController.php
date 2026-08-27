<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Course;
use App\Models\CourseModule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LessonController extends Controller
{
    public function index(Request $request)
    {
        $query = Lesson::with(['course', 'module']);
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->filled('module_id')) {
            $query->where('module_id', $request->module_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }
        $lessons = $query->orderBy('sequence')->latest()->paginate(20)->withQueryString();
        $courses = Course::orderBy('title')->get();
        return view('admin.lessons.index', compact('lessons', 'courses'));
    }

    public function create(Request $request)
    {
        $courses = Course::orderBy('title')->get();
        $modules = CourseModule::when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))->orderBy('title')->get();
        return view('admin.lessons.create', compact('courses', 'modules'));
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
            'is_published' => 'boolean',
            'status' => 'required|in:draft,published,archived',
        ]);
        $validated['slug'] = Str::slug($validated['title']);
        Lesson::create($validated);
        return redirect()->route('admin.lessons.index')->with('success', 'Lesson created successfully.');
    }

    public function edit(Lesson $lesson)
    {
        $courses = Course::orderBy('title')->get();
        $modules = CourseModule::where('course_id', $lesson->course_id)->orderBy('title')->get();
        return view('admin.lessons.edit', compact('lesson', 'courses', 'modules'));
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
            'is_published' => 'boolean',
            'status' => 'required|in:draft,published,archived',
        ]);
        $validated['slug'] = Str::slug($validated['title']);
        $lesson->update($validated);
        return redirect()->route('admin.lessons.index')->with('success', 'Lesson updated successfully.');
    }

    public function destroy(Lesson $lesson)
    {
        $lesson->delete();
        return redirect()->route('admin.lessons.index')->with('success', 'Lesson deleted successfully.');
    }
}
