<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('student_id', 'like', "%{$search}%")
                  ->orWhere('father_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $students = $query->latest()->paginate(15);

        return view('admin.students.index', compact('students'));
    }

    public function create()
    {
        return view('admin.students.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'whatsapp' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pin_code' => 'nullable|string|max:10',
            'qualification' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
            'passing_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'experience' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_relationship' => 'nullable|string|max:255',
            'emergency_contact_number' => 'nullable|string|max:20',
            'photo' => 'nullable|image|max:2048',
            'id_proof' => 'nullable|file|max:2048',
            'qualification_proof' => 'nullable|file|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make('password'),
                'role' => 'student',
            ]);

            $lastStudent = Student::orderBy('id', 'desc')->first();
            $nextNumber = $lastStudent ? intval(substr($lastStudent->student_id, -5)) + 1 : 1;
            $studentId = 'GIS-' . date('Y') . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            $studentData = [
                'student_id' => $studentId,
                'user_id' => $user->id,
                'father_name' => $validated['father_name'] ?? null,
                'mother_name' => $validated['mother_name'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'whatsapp' => $validated['whatsapp'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'pin_code' => $validated['pin_code'] ?? null,
                'qualification' => $validated['qualification'] ?? null,
                'institution' => $validated['institution'] ?? null,
                'passing_year' => $validated['passing_year'] ?? null,
                'experience' => $validated['experience'] ?? null,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_relationship' => $validated['emergency_relationship'] ?? null,
                'emergency_contact_number' => $validated['emergency_contact_number'] ?? null,
                'status' => $validated['status'],
            ];

            if ($request->hasFile('photo')) {
                $studentData['photo'] = $request->file('photo')->store('students', 'public');
            }
            if ($request->hasFile('id_proof')) {
                $studentData['id_proof'] = $request->file('id_proof')->store('students/documents', 'public');
            }
            if ($request->hasFile('qualification_proof')) {
                $studentData['qualification_proof'] = $request->file('qualification_proof')->store('students/documents', 'public');
            }

            Student::create($studentData);
        });

        return redirect()->route('admin.students.index')->with('success', 'Student created successfully.');
    }

    public function show(Student $student)
    {
        $student->load(['user', 'enrollments.course', 'enrollments.batch', 'documents']);

        return view('admin.students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $student->load('user');

        return view('admin.students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $student->user_id,
            'phone' => 'nullable|string|max:20',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'whatsapp' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pin_code' => 'nullable|string|max:10',
            'qualification' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
            'passing_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'experience' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_relationship' => 'nullable|string|max:255',
            'emergency_contact_number' => 'nullable|string|max:20',
            'photo' => 'nullable|image|max:2048',
            'id_proof' => 'nullable|file|max:2048',
            'qualification_proof' => 'nullable|file|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        DB::transaction(function () use ($validated, $request, $student) {
            $student->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
            ]);

            $studentData = [
                'father_name' => $validated['father_name'] ?? null,
                'mother_name' => $validated['mother_name'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'whatsapp' => $validated['whatsapp'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'pin_code' => $validated['pin_code'] ?? null,
                'qualification' => $validated['qualification'] ?? null,
                'institution' => $validated['institution'] ?? null,
                'passing_year' => $validated['passing_year'] ?? null,
                'experience' => $validated['experience'] ?? null,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_relationship' => $validated['emergency_relationship'] ?? null,
                'emergency_contact_number' => $validated['emergency_contact_number'] ?? null,
                'status' => $validated['status'],
            ];

            if ($request->hasFile('photo')) {
                $studentData['photo'] = $request->file('photo')->store('students', 'public');
            }
            if ($request->hasFile('id_proof')) {
                $studentData['id_proof'] = $request->file('id_proof')->store('students/documents', 'public');
            }
            if ($request->hasFile('qualification_proof')) {
                $studentData['qualification_proof'] = $request->file('qualification_proof')->store('students/documents', 'public');
            }

            $student->update($studentData);
        });

        return redirect()->route('admin.students.index')->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student)
    {
        DB::transaction(function () use ($student) {
            $student->user->delete();
            $student->delete();
        });

        return redirect()->route('admin.students.index')->with('success', 'Student deleted successfully.');
    }
}
