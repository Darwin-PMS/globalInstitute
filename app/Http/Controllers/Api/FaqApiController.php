<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;

class FaqApiController extends Controller
{
    public function index(): JsonResponse
    {
        $faqs = Faq::orderBy('sort_order')
            ->get()
            ->map(fn ($f) => [
                'id' => $f->id,
                'question' => $f->question,
                'answer' => $f->answer,
                'category' => $f->category,
            ]);

        return response()->json(['data' => $faqs]);
    }
}
