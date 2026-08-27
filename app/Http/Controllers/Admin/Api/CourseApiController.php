<?php
namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with('category');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        $courses = $query->orderBy($request->get('sort', 'created_at'), $request->get('order', 'desc'))
            ->paginate($request->get('per_page', 20));
        return response()->json($courses);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:course_categories,id',
            'title' => 'required|string|max:255',
            'short_description' => 'required|string|max:500',
            'description' => 'required|string',
            'overview' => 'nullable|string',
            'eligibility' => 'nullable|string',
            'duration' => 'nullable|string|max:255',
            'fees' => 'nullable|numeric|min:0',
            'skills' => 'nullable|array',
            'image' => 'nullable|image|max:2048',
            'featured' => 'boolean',
            'status' => 'required|in:published,draft,archived',
        ]);
        $validated['slug'] = Str::slug($validated['title']);
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('courses', 'public');
        }
        $course = Course::create($validated);
        return response()->json(['success' => true, 'course' => $course, 'message' => 'Course created.']);
    }

    public function show(Course $course)
    {
        $course->load('category', 'modules', 'lessons', 'onlineClasses');
        return response()->json($course);
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:course_categories,id',
            'title' => 'required|string|max:255',
            'short_description' => 'required|string|max:500',
            'description' => 'required|string',
            'overview' => 'nullable|string',
            'eligibility' => 'nullable|string',
            'duration' => 'nullable|string|max:255',
            'fees' => 'nullable|numeric|min:0',
            'skills' => 'nullable|array',
            'image' => 'nullable|image|max:2048',
            'featured' => 'boolean',
            'status' => 'required|in:published,draft,archived',
        ]);
        $validated['slug'] = Str::slug($validated['title']);
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('courses', 'public');
        }
        $course->update($validated);
        return response()->json(['success' => true, 'message' => 'Course updated.']);
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return response()->json(['success' => true, 'message' => 'Course deleted.']);
    }
}