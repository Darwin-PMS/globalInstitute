<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Trainer;
use App\Models\Room;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $query = Batch::with(['course', 'trainer', 'room']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('batch_name', 'like', "%{$search}%")
                  ->orWhere('batch_code', 'like', "%{$search}%");
            });
        }

        $batches = $query->latest()->paginate(15);
        $courses = Course::where('status', 'published')->orderBy('title')->get();

        return view('admin.batches.index', compact('batches', 'courses'));
    }

    public function create()
    {
        $courses = Course::where('status', 'published')->orderBy('title')->get();
        $trainers = Trainer::where('status', 'active')->orderBy('name')->get();
        $rooms = Room::where('status', 'active')->orderBy('name')->get();

        return view('admin.batches.create', compact('courses', 'trainers', 'rooms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'batch_name' => 'required|string|max:255',
            'batch_code' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'days' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'trainer_id' => 'nullable|exists:trainers,id',
            'room_id' => 'nullable|exists:rooms,id',
            'status' => 'required|in:upcoming,active,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        if (!empty($validated['capacity'])) {
            $validated['available_seats'] = $validated['capacity'];
        }

        Batch::create($validated);

        return redirect()->route('admin.batches.index')->with('success', 'Batch created successfully.');
    }

    public function edit(Batch $batch)
    {
        $courses = Course::where('status', 'published')->orderBy('title')->get();
        $trainers = Trainer::where('status', 'active')->orderBy('name')->get();
        $rooms = Room::where('status', 'active')->orderBy('name')->get();

        return view('admin.batches.edit', compact('batch', 'courses', 'trainers', 'rooms'));
    }

    public function update(Request $request, Batch $batch)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'batch_name' => 'required|string|max:255',
            'batch_code' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'days' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'trainer_id' => 'nullable|exists:trainers,id',
            'room_id' => 'nullable|exists:rooms,id',
            'status' => 'required|in:upcoming,active,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $batch->update($validated);

        return redirect()->route('admin.batches.index')->with('success', 'Batch updated successfully.');
    }

    public function destroy(Batch $batch)
    {
        $batch->delete();

        return redirect()->route('admin.batches.index')->with('success', 'Batch deleted successfully.');
    }
}
