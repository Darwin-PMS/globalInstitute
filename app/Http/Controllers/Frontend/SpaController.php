<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enquiry;
use Illuminate\Http\Request;

class SpaController extends Controller
{
    public function index()
    {
        $path = public_path('index_spa.html');

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path);
    }

    public function submitEnquiry(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'required|string|max:20',
            'email'       => 'nullable|email|max:255',
            'course_slug' => 'nullable|string',
            'message'     => 'nullable|string|max:1000',
            'subject'     => 'nullable|string|max:255',
            'source'      => 'nullable|string|max:50',
        ]);

        $courseId = null;
        if (!empty($validated['course_slug']) && $validated['course_slug'] !== 'other') {
            $course = Course::where('slug', $validated['course_slug'])->first();
            if ($course) {
                $courseId = $course->id;
            }
        }

        $subject = $validated['subject'] ?? null;
        if ($courseId && !$subject) {
            $course = Course::find($courseId);
            $subject = $course->title ?? null;
        }

        Enquiry::create([
            'name'      => $validated['name'],
            'phone'     => $validated['phone'],
            'email'     => $validated['email'] ?? null,
            'course_id' => $courseId,
            'message'   => $validated['message'] ?? null,
            'subject'   => $subject,
            'source'    => $validated['source'] ?? 'website',
            'status'    => 'new',
        ]);

        return response()->json(['success' => true, 'message' => 'Enquiry submitted successfully!']);
    }

    public function onlineLearningShell()
    {
        return view('layouts.online-learning');
    }

    /**
     * The Live Class Room shell.
     *
     * Deliberately its own view and its own JS bundle rather than a page inside
     * the Online Learning SPA: the room is a self-contained application, which is
     * what allows it to be extracted later. Authorisation is not done here — the
     * shell only hands the room key to the client, and every room endpoint
     * re-checks membership on the session user.
     */
    public function classRoomShell(string $roomKey)
    {
        return view('online-learning.room', ['roomKey' => $roomKey]);
    }
}
