<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with('category');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'oldest': $query->oldest(); break;
                case 'title_asc': $query->orderBy('title', 'asc'); break;
                case 'title_desc': $query->orderBy('title', 'desc'); break;
                case 'fees_low': $query->orderBy('fees', 'asc'); break;
                case 'fees_high': $query->orderBy('fees', 'desc'); break;
                default: $query->latest(); break;
            }
        } else {
            $query->latest();
        }

        $courses = $query->paginate(15)->withQueryString();
        $categories = CourseCategory::where('status', 'published')->orderBy('name')->get();

        $totalCourses = Course::count();
        $publishedCourses = Course::where('status', 'published')->count();
        $draftCourses = Course::where('status', 'draft')->count();
        $categoryCount = CourseCategory::count();

        return view('admin.courses.index', compact(
            'courses', 'categories',
            'totalCourses', 'publishedCourses', 'draftCourses', 'categoryCount'
        ));
    }

    public function create()
    {
        $categories = CourseCategory::where('status', 'published')->orderBy('name')->get();
        return view('admin.courses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:course_categories,id',
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'duration' => 'nullable|string|max:255',
            'fees' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:published,draft',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['title']);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('courses', 'public');
        }

        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        Course::create($validated);

        return redirect()->route('admin.courses.index')->with('success', 'Course created successfully.');
    }

    public function edit(Course $course)
    {
        $categories = CourseCategory::where('status', 'published')->orderBy('name')->get();
        return view('admin.courses.edit', compact('course', 'categories'));
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:course_categories,id',
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'duration' => 'nullable|string|max:255',
            'fees' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:published,draft',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['title']);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('courses', 'public');
        }

        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $course->update($validated);

        return redirect()->route('admin.courses.index')->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return redirect()->route('admin.courses.index')->with('success', 'Course deleted successfully.');
    }
}
