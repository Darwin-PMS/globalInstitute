<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;

class AnnouncementApiController extends Controller
{
    /**
     * Live announcements only: published and inside their display window.
     */
    public function index(): JsonResponse
    {
        $today = now()->toDateString();

        $announcements = Announcement::where('status', 'published')
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->orderByDesc('priority')
            ->orderByDesc('start_date')
            ->limit(6)
            ->get()
            ->map(fn (Announcement $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'message' => $a->message,
                'button_text' => $a->button_text,
                'button_url' => $a->button_url,
                'date' => $a->start_date?->toDateString(),
                'priority' => $a->priority,
            ]);

        return response()->json(['data' => $announcements]);
    }
}
