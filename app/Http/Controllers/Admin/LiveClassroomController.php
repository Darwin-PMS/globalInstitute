<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OnlineClass;
use Illuminate\Http\Request;

class LiveClassroomController extends Controller
{
    /**
     * Show the live classroom management page.
     * Lists all classes with status and join/lobby buttons.
     */
    public function index(Request $request)
    {
        $classes = OnlineClass::with(['course', 'teacher.user'])
            ->orderBy('class_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function ($class) {
                return [
                    'id' => $class->id,
                    'title' => $class->title,
                    'course' => $class->course?->only('id', 'title'),
                    'teacher' => $class->teacher?->user?->only('id', 'name'),
                    'class_date' => $class->class_date,
                    'start_time' => $class->start_time,
                    'end_time' => $class->end_time,
                    'status' => $class->status,
                    'meeting_url' => $class->meeting_url,
                    'meeting_provider' => $class->meeting_provider,
                ];
            });

        return view('admin.live-classroom', compact('classes'));
    }
}
