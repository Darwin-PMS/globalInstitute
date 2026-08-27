<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;

class CourseApiController extends Controller
{
    public function index(): JsonResponse
    {
        $courses = Course::with('category')
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'slug' => $c->slug,
                'description' => $c->description,
                'short_description' => $c->short_description,
                'category' => $c->category?->name,
                'category_slug' => $c->category?->slug,
                'fees' => $c->fees,
                'duration' => $c->duration,
                'level' => $c->level,
                'mode' => $c->mode,
                'image' => $c->image ? asset('storage/' . $c->image) : null,
                'sort_order' => $c->sort_order,
            ]);

        return response()->json(['data' => $courses]);
    }

    public function show(string $slug): JsonResponse
    {
        $course = Course::with('category')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->description,
                'short_description' => $course->short_description,
                'category' => $course->category?->name,
                'category_slug' => $course->category?->slug,
                'fees' => $course->fees,
                'duration' => $course->duration,
                'level' => $course->level,
                'mode' => $course->mode,
                'syllabus' => $course->syllabus,
                'career_prospects' => $course->career_prospects,
                'image' => $course->image ? asset('storage/' . $course->image) : null,
            ],
        ]);
    }
}
