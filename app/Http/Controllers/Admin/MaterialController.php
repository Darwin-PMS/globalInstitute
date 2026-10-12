<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = LearningMaterial::with(['course', 'module', 'lesson']);
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }
        $materials = $query->latest()->paginate(20)->withQueryString();
        $courses = Course::orderBy('title')->get();
        return view('admin.materials.index', compact('materials', 'courses'));
    }

    public function create(Request $request)
    {
        $courses = Course::orderBy('title')->get();
        $modules = CourseModule::when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))->orderBy('name')->get();
        $lessons = Lesson::when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))->orderBy('title')->get();
        return view('admin.materials.create', compact('courses', 'modules', 'lessons'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'module_id' => 'nullable|exists:course_modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'file' => 'nullable|file|max:10240',
            'file_type' => 'required|in:pdf,ppt,doc,xls,zip,image,link,other',
            'external_url' => 'nullable|url|max:500',
            'visibility' => 'required|in:enrolled,public',
            'is_published' => 'boolean',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $validated['file_path'] = $file->store('materials', 'public');
            $validated['file_size'] = $file->getSize();
        }

        LearningMaterial::create($validated);
        return redirect()->route('admin.materials.index')->with('success', 'Material uploaded successfully.');
    }

    public function edit(LearningMaterial $material)
    {
        $courses = Course::orderBy('title')->get();
        $modules = CourseModule::where('course_id', $material->course_id)->orderBy('name')->get();
        $lessons = Lesson::where('course_id', $material->course_id)->orderBy('title')->get();
        return view('admin.materials.edit', compact('material', 'courses', 'modules', 'lessons'));
    }

    public function update(Request $request, LearningMaterial $material)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'module_id' => 'nullable|exists:course_modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'file' => 'nullable|file|max:10240',
            'file_type' => 'required|in:pdf,ppt,doc,xls,zip,image,link,other',
            'external_url' => 'nullable|url|max:500',
            'visibility' => 'required|in:enrolled,public',
            'is_published' => 'boolean',
        ]);

        if ($request->hasFile('file')) {
            if ($material->file_path) {
                Storage::disk('public')->delete($material->file_path);
            }
            $file = $request->file('file');
            $validated['file_path'] = $file->store('materials', 'public');
            $validated['file_size'] = $file->getSize();
        }

        $material->update($validated);
        return redirect()->route('admin.materials.index')->with('success', 'Material updated successfully.');
    }

    public function destroy(LearningMaterial $material)
    {
        if ($material->file_path) {
            Storage::disk('public')->delete($material->file_path);
        }
        $material->delete();
        return redirect()->route('admin.materials.index')->with('success', 'Material deleted successfully.');
    }
}
