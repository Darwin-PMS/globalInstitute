<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trainer;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $query = Trainer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $teachers = $query->latest()->paginate(15);

        return view('admin.teachers.index', compact('teachers'));
    }

    public function create()
    {
        return view('admin.teachers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'qualification' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'employment_type' => 'required|in:full_time,part_time,visiting,guest',
            'status' => 'required|in:active,inactive',
            'bio' => 'nullable|string',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'joining_date' => 'nullable|date',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('teachers', 'public');
        }

        Trainer::create($validated);

        return redirect()->route('admin.teachers.index')->with('success', 'Teacher created successfully.');
    }

    public function edit(Trainer $teacher)
    {
        return view('admin.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, Trainer $teacher)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'qualification' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'employment_type' => 'required|in:full_time,part_time,visiting,guest',
            'status' => 'required|in:active,inactive',
            'bio' => 'nullable|string',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'joining_date' => 'nullable|date',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('teachers', 'public');
        }

        $teacher->update($validated);

        return redirect()->route('admin.teachers.index')->with('success', 'Teacher updated successfully.');
    }

    public function destroy(Trainer $teacher)
    {
        $teacher->delete();

        return redirect()->route('admin.teachers.index')->with('success', 'Teacher deleted successfully.');
    }
}
