<?php
namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryApiController extends Controller
{
    public function index(Request $request)
    {
        $query = CourseCategory::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        $categories = $query->orderBy($request->get('sort', 'sort_order'))
            ->paginate($request->get('per_page', 50));
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:255',
            'status' => 'required|in:published,draft,archived',
            'sort_order' => 'integer|min:0',
        ]);
        $validated['slug'] = Str::slug($validated['name']);
        $category = CourseCategory::create($validated);
        return response()->json(['success' => true, 'category' => $category, 'message' => 'Category created.']);
    }

    public function update(Request $request, CourseCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:255',
            'status' => 'required|in:published,draft,archived',
            'sort_order' => 'integer|min:0',
        ]);
        $validated['slug'] = Str::slug($validated['name']);
        $category->update($validated);
        return response()->json(['success' => true, 'message' => 'Category updated.']);
    }

    public function destroy(CourseCategory $category)
    {
        $category->delete();
        return response()->json(['success' => true, 'message' => 'Category deleted.']);
    }
}