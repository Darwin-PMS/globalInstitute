<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

class FacilityApiController extends Controller
{
    /**
     * Public campus facility listing, derived from the rooms the institute
     * actually operates. No invented amenities.
     */
    public function index(): JsonResponse
    {
        $rooms = Room::where('status', 'active')
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(fn (Room $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'type' => $r->type,
                'capacity' => $r->capacity,
            ]);

        return response()->json(['data' => $rooms]);
    }
}
