<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseModule;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseModuleController extends Controller
{
    public function index(Request $request)
    {
        $query = CourseModule::with('course');

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $modules = $query->orderBy('course_id')->orderBy('sequence')->latest()->paginate(15);
        $courses = Course::where('status', 'published')->orderBy('title')->get();

        return view('admin.course-modules.index', compact('modules', 'courses'));
    }

    public function create()
    {
        $courses = Course::where('status', 'published')->orderBy('title')->get();

        return view('admin.course-modules.create', compact('courses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable|string|max:255',
            'sequence' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['sequence'] = $validated['sequence'] ?? 0;

        CourseModule::create($validated);

        return redirect()->route('admin.course-modules.index')->with('success', 'Course module created successfully.');
    }

    public function edit(CourseModule $courseModule)
    {
        $courses = Course::where('status', 'published')->orderBy('title')->get();

        return view('admin.course-modules.edit', compact('courseModule', 'courses'));
    }

    public function update(Request $request, CourseModule $courseModule)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable|string|max:255',
            'sequence' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['sequence'] = $validated['sequence'] ?? 0;

        $courseModule->update($validated);

        return redirect()->route('admin.course-modules.index')->with('success', 'Course module updated successfully.');
    }

    public function destroy(CourseModule $courseModule)
    {
        $courseModule->delete();

        return redirect()->route('admin.course-modules.index')->with('success', 'Course module deleted successfully.');
    }
}
