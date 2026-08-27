<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recording;
use App\Models\OnlineClass;
use Illuminate\Http\Request;

class RecordingController extends Controller
{
    public function index(Request $request)
    {
        $query = Recording::with('onlineClass.course');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }
        $recordings = $query->latest()->paginate(20)->withQueryString();
        return view('admin.recordings.index', compact('recordings'));
    }

    public function create()
    {
        $classes = OnlineClass::orderBy('title')->get();
        return view('admin.recordings.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'online_class_id' => 'required|exists:online_classes,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'recording_url' => 'required|url|max:500',
            'duration' => 'nullable|integer|min:0',
            'visibility' => 'required|in:enrolled,public',
            'is_published' => 'boolean',
        ]);

        Recording::create($validated);

        $onlineClass = OnlineClass::find($validated['online_class_id']);
        $onlineClass->update(['is_recording_available' => true, 'recording_url' => $validated['recording_url']]);

        return redirect()->route('admin.recordings.index')->with('success', 'Recording added successfully.');
    }

    public function edit(Recording $recording)
    {
        $classes = OnlineClass::orderBy('title')->get();
        return view('admin.recordings.edit', compact('recording', 'classes'));
    }

    public function update(Request $request, Recording $recording)
    {
        $validated = $request->validate([
            'online_class_id' => 'required|exists:online_classes,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'recording_url' => 'required|url|max:500',
            'duration' => 'nullable|integer|min:0',
            'visibility' => 'required|in:enrolled,public',
            'is_published' => 'boolean',
        ]);

        $recording->update($validated);
        return redirect()->route('admin.recordings.index')->with('success', 'Recording updated successfully.');
    }

    public function destroy(Recording $recording)
    {
        $recording->delete();
        return redirect()->route('admin.recordings.index')->with('success', 'Recording deleted successfully.');
    }
}
