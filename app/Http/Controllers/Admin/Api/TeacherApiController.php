<?php
namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeacherApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Trainer::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $teachers = $query->orderBy('name')->paginate($request->get('per_page', 20));
        return response()->json($teachers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:trainers,email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'designation' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'employment_type' => 'nullable|in:full_time,part_time,contract',
            'specialization' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'status' => 'nullable|in:active,inactive',
        ]);
        $validated['employment_type'] = $validated['employment_type'] ?? 'full_time';
        $validated['status'] = $validated['status'] ?? 'active';

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make('password'),
            'role' => 'teacher',
        ]);

        $validated['user_id'] = $user->id;
        $teacher = Trainer::create($validated);
        return response()->json(['success' => true, 'teacher' => $teacher, 'message' => 'Teacher created.']);
    }

    public function show(Trainer $teacher)
    {
        return response()->json($teacher);
    }

    public function update(Request $request, Trainer $teacher)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:trainers,email,{$teacher->id}",
            'phone' => 'nullable|string|max:20',
            'designation' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'employment_type' => 'nullable|in:full_time,part_time,contract',
            'specialization' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'status' => 'nullable|in:active,inactive',
        ]);
        $teacher->update($validated);
        if ($teacher->user_id) {
            $teacher->user->update(['name' => $validated['name'], 'email' => $validated['email']]);
        }
        return response()->json(['success' => true, 'message' => 'Teacher updated.']);
    }

    public function destroy(Trainer $teacher)
    {
        $teacher->delete();
        return response()->json(['success' => true, 'message' => 'Teacher deleted.']);
    }
}