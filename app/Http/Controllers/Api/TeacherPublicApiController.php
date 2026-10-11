<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trainer;
use Illuminate\Http\JsonResponse;

class TeacherPublicApiController extends Controller
{
    /**
     * Public faculty listing.
     *
     * Only non-sensitive teaching details are exposed; phone, email and notes
     * stay behind the admin perimeter.
     */
    public function index(): JsonResponse
    {
        $trainers = Trainer::where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Trainer $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'designation' => $t->designation,
                'qualification' => $t->qualification,
                'experience' => $t->experience,
                'specialization' => $t->specialization,
                'bio' => $t->bio,
                'photo' => $t->photo ? asset('storage/' . $t->photo) : null,
            ]);

        return response()->json(['data' => $trainers]);
    }
}
