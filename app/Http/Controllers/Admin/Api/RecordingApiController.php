<?php
namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Recording;
use App\Models\OnlineClass;
use Illuminate\Http\Request;

class RecordingApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Recording::with('onlineClass.course');
        if ($request->filled('search')) $query->where('title', 'like', "%{$request->search}%");
        return response()->json($query->latest()->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'online_class_id' => 'nullable|exists:online_classes,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'recording_url' => 'required|url|max:500',
            'duration' => 'nullable|integer|min:0',
            'visibility' => 'nullable|in:enrolled,public',
            'is_published' => 'boolean',
        ]);
        $validated['visibility'] = $validated['visibility'] ?? 'enrolled';
        $recording = Recording::create($validated);
        if (!empty($validated['online_class_id'])) {
            $onlineClass = OnlineClass::find($validated['online_class_id']);
            if ($onlineClass) {
                $onlineClass->update(['is_recording_available' => true, 'recording_url' => $validated['recording_url']]);
            }
        }
        return response()->json(['success' => true, 'recording' => $recording, 'message' => 'Recording added.']);
    }

    public function update(Request $request, Recording $recording)
    {
        $validated = $request->validate([
            'online_class_id' => 'nullable|exists:online_classes,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'recording_url' => 'required|url|max:500',
            'duration' => 'nullable|integer|min:0',
            'visibility' => 'nullable|in:enrolled,public',
            'is_published' => 'boolean',
        ]);
        $recording->update($validated);
        return response()->json(['success' => true, 'message' => 'Recording updated.']);
    }

    public function destroy(Recording $recording)
    {
        $recording->delete();
        return response()->json(['success' => true, 'message' => 'Recording deleted.']);
    }
}
