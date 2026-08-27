<?php
namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use Illuminate\Http\Request;

class MaterialApiController extends Controller
{
    public function index(Request $request)
    {
        $query = LearningMaterial::with(['course', 'module', 'lesson']);
        if ($request->filled('course_id')) $query->where('course_id', $request->course_id);
        if ($request->filled('search')) $query->where('title', 'like', "%{$request->search}%");
        return response()->json($query->latest()->paginate($request->get('per_page', 20)));
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
            'file_type' => 'nullable|in:pdf,ppt,doc,xls,zip,image,link,other',
            'external_url' => 'nullable|url|max:500',
            'visibility' => 'nullable|in:enrolled,public',
            'is_published' => 'boolean',
        ]);
        $validated['visibility'] = $validated['visibility'] ?? 'enrolled';
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $validated['file_path'] = $file->store('materials', 'public');
            $validated['file_size'] = $file->getSize();
        }
        $material = LearningMaterial::create($validated);
        return response()->json(['success' => true, 'material' => $material, 'message' => 'Material uploaded.']);
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
            'file_type' => 'nullable|in:pdf,ppt,doc,xls,zip,image,link,other',
            'external_url' => 'nullable|url|max:500',
            'visibility' => 'nullable|in:enrolled,public',
            'is_published' => 'boolean',
        ]);
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $validated['file_path'] = $file->store('materials', 'public');
            $validated['file_size'] = $file->getSize();
        }
        $material->update($validated);
        return response()->json(['success' => true, 'message' => 'Material updated.']);
    }

    public function destroy(LearningMaterial $material)
    {
        $material->delete();
        return response()->json(['success' => true, 'message' => 'Material deleted.']);
    }
}
