<?php
namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use Illuminate\Http\Request;

class BatchApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Batch::with(['course', 'trainer', 'room']);
        if ($request->filled('search')) $query->where('batch_name', 'like', "%{$request->search}%");
        if ($request->filled('course_id')) $query->where('course_id', $request->course_id);
        if ($request->filled('status')) $query->where('status', $request->status);
        return response()->json($query->orderBy('id', 'desc')->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'batch_name' => 'required|string|max:255',
            'batch_code' => 'nullable|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'days' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:1',
            'trainer_id' => 'nullable|exists:trainers,id',
            'room_id' => 'nullable|exists:rooms,id',
            'status' => 'required|in:upcoming,active,completed,cancelled',
        ]);
        $validated['available_seats'] = $validated['capacity'];
        $batch = Batch::create($validated);
        return response()->json(['success' => true, 'batch' => $batch, 'message' => 'Batch created.']);
    }

    public function update(Request $request, Batch $batch)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'batch_name' => 'required|string|max:255',
            'batch_code' => 'nullable|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'days' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:1',
            'trainer_id' => 'nullable|exists:trainers,id',
            'room_id' => 'nullable|exists:rooms,id',
            'status' => 'required|in:upcoming,active,completed,cancelled',
        ]);
        $batch->update($validated);
        return response()->json(['success' => true, 'message' => 'Batch updated.']);
    }

    public function destroy(Batch $batch)
    {
        $batch->delete();
        return response()->json(['success' => true, 'message' => 'Batch deleted.']);
    }
}
