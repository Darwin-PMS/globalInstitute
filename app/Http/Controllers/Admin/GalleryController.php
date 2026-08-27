<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Models\GalleryCategory;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $query = GalleryImage::with('galleryCategory');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('galleryCategory', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('featured') && $request->featured !== '') {
            $query->where('is_featured', $request->featured === '1');
        }

        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'title_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'title_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'most_images':
                $query->withCount('galleryCategory')
                      ->orderBy('gallery_category_id', 'desc');
                break;
            case 'updated':
                $query->latest('updated_at');
                break;
            default:
                $query->latest();
                break;
        }

        $perPage = (int) $request->get('per_page', 15);
        $galleryImages = $query->paginate($perPage)->withQueryString();
        $categories = GalleryCategory::orderBy('name')->get();
        $totalGalleries = GalleryImage::count();

        return view('admin.gallery.index', compact('galleryImages', 'categories', 'totalGalleries'));
    }

    public function create()
    {
        $categories = GalleryCategory::orderBy('name')->get();
        return view('admin.gallery.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|max:5120',
            'category_id' => 'nullable|exists:gallery_categories,id',
            'description' => 'nullable|string',
            'alt_text' => 'nullable|string|max:255',
            'sort_order' => 'integer|min:0',
            'status' => 'required|in:published,draft',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('gallery', 'public');
        }

        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        GalleryImage::create($validated);

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery image added successfully.');
    }

    public function edit(GalleryImage $galleryImage)
    {
        $categories = GalleryCategory::orderBy('name')->get();
        return view('admin.gallery.edit', compact('galleryImage', 'categories'));
    }

    public function update(Request $request, GalleryImage $galleryImage)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'nullable|image|max:5120',
            'category_id' => 'nullable|exists:gallery_categories,id',
            'description' => 'nullable|string',
            'alt_text' => 'nullable|string|max:255',
            'sort_order' => 'integer|min:0',
            'status' => 'required|in:published,draft',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('gallery', 'public');
        }

        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $galleryImage->update($validated);

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery image updated successfully.');
    }

    public function destroy(GalleryImage $galleryImage)
    {
        $galleryImage->delete();

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery image deleted successfully.');
    }

    public function show(GalleryImage $galleryImage)
    {
        $galleryImage->load('galleryCategory');

        if (request()->header('X-Requested-With') === 'XMLHttpRequest' || request()->get('_format') === 'json') {
            return response()->json([
                'id' => $galleryImage->id,
                'title' => $galleryImage->title,
                'description' => $galleryImage->description,
                'image' => $galleryImage->image,
                'alt_text' => $galleryImage->alt_text,
                'status' => $galleryImage->status,
                'is_featured' => $galleryImage->is_featured,
                'sort_order' => $galleryImage->sort_order,
                'category_name' => $galleryImage->galleryCategory?->name,
                'created_at' => $galleryImage->created_at,
                'updated_at' => $galleryImage->updated_at,
            ]);
        }

        return view('admin.gallery.show', compact('galleryImage'));
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'action' => 'required|in:publish,unpublish,feature,unfeature,delete',
        ]);

        $ids = $request->ids;
        $action = $request->action;

        switch ($action) {
            case 'publish':
                GalleryImage::whereIn('id', $ids)->update(['status' => 'published']);
                $message = count($ids) . ' image(s) published successfully.';
                break;
            case 'unpublish':
                GalleryImage::whereIn('id', $ids)->update(['status' => 'draft']);
                $message = count($ids) . ' image(s) unpublished successfully.';
                break;
            case 'feature':
                GalleryImage::whereIn('id', $ids)->update(['is_featured' => true]);
                $message = count($ids) . ' image(s) marked as featured.';
                break;
            case 'unfeature':
                GalleryImage::whereIn('id', $ids)->update(['is_featured' => false]);
                $message = count($ids) . ' image(s) removed from featured.';
                break;
            case 'delete':
                GalleryImage::whereIn('id', $ids)->delete();
                $message = count($ids) . ' image(s) deleted successfully.';
                break;
        }

        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('admin.gallery.index')->with('success', $message);
    }

    public function toggleStatus(GalleryImage $galleryImage)
    {
        $galleryImage->status = $galleryImage->status === 'published' ? 'draft' : 'published';
        $galleryImage->save();

        if (request()->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['success' => true, 'status' => $galleryImage->status]);
        }

        return redirect()->route('admin.gallery.index')->with('success', 'Status updated successfully.');
    }

    public function toggleFeatured(GalleryImage $galleryImage)
    {
        $galleryImage->is_featured = !$galleryImage->is_featured;
        $galleryImage->save();

        if (request()->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['success' => true, 'is_featured' => $galleryImage->is_featured]);
        }

        return redirect()->route('admin.gallery.index')->with('success', 'Featured status updated.');
    }
}
