<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;

class TestimonialApiController extends Controller
{
    public function index(): JsonResponse
    {
        $testimonials = Testimonial::where('featured', true)
            ->orderByDesc('sort_order')
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'student_name' => $t->student_name,
                'designation' => $t->course,
                'content' => $t->testimonial,
                'rating' => 5,
                'photo' => $t->photo ? asset('storage/' . $t->photo) : null,
            ]);

        return response()->json(['data' => $testimonials]);
    }
}
