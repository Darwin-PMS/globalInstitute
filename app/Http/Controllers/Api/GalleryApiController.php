<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use Illuminate\Http\JsonResponse;

class GalleryApiController extends Controller
{
    public function index(): JsonResponse
    {
        $images = GalleryImage::orderByDesc('sort_order')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'title' => $g->title,
                'alt_text' => $g->alt_text,
                'category' => $g->galleryCategory?->name ?? 'Uncategorized',
                'image' => $g->image ? asset('storage/' . $g->image) : 'https://picsum.photos/seed/' . $g->id . '/800/600',
            ]);

        return response()->json(['data' => $images]);
    }
}
