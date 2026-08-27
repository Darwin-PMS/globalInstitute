<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\OnlineClass;
use App\Models\Trainer;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Assignment::with(['course', 'module', 'teacher']);
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }
        $assignments = $query->latest()->paginate(20)->withQueryString();
        $courses = Course::orderBy('title')->get();
        return view('admin.assignments.index', compact('assignments', 'courses'));
    }

    public function create()
    {
        $courses = Course::orderBy('title')->get();
        $modules = CourseModule::orderBy('name')->get();
        $lessons = Lesson::orderBy('title')->get();
        $teachers = Trainer::orderBy('name')->get();
        return view('admin.assignments.create', compact('courses', 'modules', 'lessons', 'teachers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'module_id' => 'nullable|exists:course_modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'teacher_id' => 'required|exists:trainers,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'instructions' => 'nullable|string',
            'due_date' => 'nullable|date',
            'max_marks' => 'required|integer|min:1',
            'attachment' => 'nullable|file|max:5120',
            'status' => 'required|in:draft,published,closed',
        ]);

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('assignments', 'public');
        }

        Assignment::create($validated);
        return redirect()->route('admin.assignments.index')->with('success', 'Assignment created successfully.');
    }

    public function edit(Assignment $assignment)
    {
        $courses = Course::orderBy('title')->get();
        $modules = CourseModule::orderBy('name')->get();
        $lessons = Lesson::orderBy('title')->get();
        $teachers = Trainer::orderBy('name')->get();
        return view('admin.assignments.edit', compact('assignment', 'courses', 'modules', 'lessons', 'teachers'));
    }

    public function update(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'module_id' => 'nullable|exists:course_modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'teacher_id' => 'required|exists:trainers,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'instructions' => 'nullable|string',
            'due_date' => 'nullable|date',
            'max_marks' => 'required|integer|min:1',
            'attachment' => 'nullable|file|max:5120',
            'status' => 'required|in:draft,published,closed',
        ]);

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('assignments', 'public');
        }

        $assignment->update($validated);
        return redirect()->route('admin.assignments.index')->with('success', 'Assignment updated successfully.');
    }

    public function destroy(Assignment $assignment)
    {
        $assignment->delete();
        return redirect()->route('admin.assignments.index')->with('success', 'Assignment deleted successfully.');
    }
}
