<?php
namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with(['user', 'enrollments.course']);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }
        $students = $query->orderBy('id', 'desc')->paginate($request->get('per_page', 20));
        return response()->json($students);
    }

    public function show(Student $student)
    {
        $student->load(['user', 'enrollments.course', 'enrollments.batch']);
        return response()->json($student);
    }
}