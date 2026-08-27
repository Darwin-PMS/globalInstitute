<?php
namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Models\GalleryCategory;
use Illuminate\Http\Request;

class GalleryApiController extends Controller
{
    public function index(Request $request)
    {
        $query = GalleryImage::with('category');
        if ($request->filled('search')) $query->where('title', 'like', "%{$request->search}%");
        if ($request->filled('category_id')) $query->where('category_id', $request->category_id);
        return response()->json($query->orderBy('id', 'desc')->paginate($request->get('per_page', 20)));
    }

    public function categories()
    {
        return response()->json(GalleryCategory::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:gallery_categories,id',
            'image' => 'required|image|max:5120',
            'caption' => 'nullable|string|max:500',
            'status' => 'required|in:published,draft,archived',
        ]);
        $validated['image_path'] = $request->file('image')->store('gallery', 'public');
        $image = GalleryImage::create($validated);
        return response()->json(['success' => true, 'image' => $image, 'message' => 'Image uploaded.']);
    }

    public function update(Request $request, GalleryImage $image)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:gallery_categories,id',
            'image' => 'nullable|image|max:5120',
            'caption' => 'nullable|string|max:500',
            'status' => 'required|in:published,draft,archived',
        ]);
        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('gallery', 'public');
        }
        $image->update($validated);
        return response()->json(['success' => true, 'message' => 'Image updated.']);
    }

    public function destroy(GalleryImage $image)
    {
        $image->delete();
        return response()->json(['success' => true, 'message' => 'Image deleted.']);
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'action' => 'required|in:delete,publish,archive',
        ]);
        if ($validated['action'] === 'delete') {
            GalleryImage::whereIn('id', $validated['ids'])->delete();
        } else {
            GalleryImage::whereIn('id', $validated['ids'])->update(['status' => $validated['action'] === 'publish' ? 'published' : 'archived']);
        }
        return response()->json(['success' => true, 'message' => 'Bulk action completed.']);
    }

    public function toggleStatus(GalleryImage $image)
    {
        $image->update(['status' => $image->status === 'published' ? 'draft' : 'published']);
        return response()->json(['success' => true, 'status' => $image->status]);
    }

    public function toggleFeatured(GalleryImage $image)
    {
        $image->update(['is_featured' => !$image->is_featured]);
        return response()->json(['success' => true, 'is_featured' => $image->is_featured]);
    }
}
