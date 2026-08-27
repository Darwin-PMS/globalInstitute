<?php
namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use Illuminate\Http\Request;

class AssignmentApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Assignment::with(['course', 'module', 'teacher']);
        if ($request->filled('course_id')) $query->where('course_id', $request->course_id);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) $query->where('title', 'like', "%{$request->search}%");
        return response()->json($query->latest()->paginate($request->get('per_page', 20)));
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
        $assignment = Assignment::create($validated);
        return response()->json(['success' => true, 'assignment' => $assignment, 'message' => 'Assignment created.']);
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
        return response()->json(['success' => true, 'message' => 'Assignment updated.']);
    }

    public function destroy(Assignment $assignment)
    {
        $assignment->delete();
        return response()->json(['success' => true, 'message' => 'Assignment deleted.']);
    }
}
