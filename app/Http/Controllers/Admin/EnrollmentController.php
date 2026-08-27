<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Course;
use App\Models\Batch;
use App\Models\FeeStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Enrollment::with(['student.user', 'course', 'batch']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('enrollment_number', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($q2) use ($search) {
                      $q2->where('student_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($q3) use ($search) {
                            $q3->where('name', 'like', "%{$search}%");
                        });
                  });
            });
        }

        $enrollments = $query->latest()->paginate(15);
        $courses = Course::where('status', 'published')->orderBy('title')->get();

        return view('admin.enrollments.index', compact('enrollments', 'courses'));
    }

    public function create()
    {
        $students = Student::with('user')->where('status', 'active')->orderBy('id')->get();
        $courses = Course::where('status', 'published')->orderBy('title')->get();
        $batches = Batch::where('status', 'active')->orderBy('batch_name')->get();
        $feeStructures = FeeStructure::where('status', 'active')->orderBy('name')->get();

        return view('admin.enrollments.create', compact('students', 'courses', 'batches', 'feeStructures'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'course_id' => 'required|exists:courses,id',
            'batch_id' => 'required|exists:batches,id',
            'admission_id' => 'nullable|exists:admissions,id',
            'enrollment_date' => 'required|date',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date|after_or_equal:start_date',
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'total_fee' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|string|max:50',
            'discount_reason' => 'nullable|string|max:255',
            'final_fee' => 'required|numeric|min:0',
            'status' => 'required|in:active,completed,cancelled,dropped',
        ]);

        DB::transaction(function () use ($validated) {
            $lastEnrollment = Enrollment::orderBy('id', 'desc')->first();
            $nextNumber = $lastEnrollment ? intval(substr($lastEnrollment->enrollment_number, -6)) + 1 : 1;
            $enrollmentNumber = 'ENR-' . date('Y') . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            $enrollment = Enrollment::create([
                'enrollment_number' => $enrollmentNumber,
                'student_id' => $validated['student_id'],
                'course_id' => $validated['course_id'],
                'batch_id' => $validated['batch_id'],
                'admission_id' => $validated['admission_id'] ?? null,
                'enrollment_date' => $validated['enrollment_date'],
                'start_date' => $validated['start_date'] ?? null,
                'expected_end_date' => $validated['expected_end_date'] ?? null,
                'fee_structure_id' => $validated['fee_structure_id'],
                'total_fee' => $validated['total_fee'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_reason' => $validated['discount_reason'] ?? null,
                'final_fee' => $validated['final_fee'],
                'total_paid' => 0,
                'balance' => $validated['final_fee'],
                'fee_status' => 'unpaid',
                'status' => $validated['status'],
            ]);

            $feeStructure = FeeStructure::with('items')->find($validated['fee_structure_id']);
            if ($feeStructure) {
                $totalPaid = 0;
                $installmentNumber = 1;
                $amountPerInstallment = $validated['final_fee'];

                foreach ($feeStructure->items as $item) {
                    if ($item->fee_type === 'installment') {
                        $amountPerInstallment = $validated['final_fee'] / max(1, $validated['total_fee'] / $item->amount);
                    }
                }
            }
        });

        return redirect()->route('admin.enrollments.index')->with('success', 'Enrollment created successfully.');
    }

    public function show(Enrollment $enrollment)
    {
        $enrollment->load(['student.user', 'course', 'batch', 'admission', 'feeStructure.items', 'feePlans.installments']);

        return view('admin.enrollments.show', compact('enrollment'));
    }

    public function edit(Enrollment $enrollment)
    {
        $students = Student::with('user')->where('status', 'active')->orderBy('id')->get();
        $courses = Course::where('status', 'published')->orderBy('title')->get();
        $batches = Batch::orderBy('batch_name')->get();
        $feeStructures = FeeStructure::where('status', 'active')->orderBy('name')->get();

        return view('admin.enrollments.edit', compact('enrollment', 'students', 'courses', 'batches', 'feeStructures'));
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'course_id' => 'required|exists:courses,id',
            'batch_id' => 'required|exists:batches,id',
            'admission_id' => 'nullable|exists:admissions,id',
            'enrollment_date' => 'required|date',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date|after_or_equal:start_date',
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'total_fee' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|string|max:50',
            'discount_reason' => 'nullable|string|max:255',
            'final_fee' => 'required|numeric|min:0',
            'status' => 'required|in:active,completed,cancelled,dropped',
        ]);

        $balance = $validated['final_fee'] - $enrollment->total_paid;

        $enrollment->update([
            'student_id' => $validated['student_id'],
            'course_id' => $validated['course_id'],
            'batch_id' => $validated['batch_id'],
            'admission_id' => $validated['admission_id'] ?? null,
            'enrollment_date' => $validated['enrollment_date'],
            'start_date' => $validated['start_date'] ?? null,
            'expected_end_date' => $validated['expected_end_date'] ?? null,
            'fee_structure_id' => $validated['fee_structure_id'],
            'total_fee' => $validated['total_fee'],
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'discount_type' => $validated['discount_type'] ?? null,
            'discount_reason' => $validated['discount_reason'] ?? null,
            'final_fee' => $validated['final_fee'],
            'balance' => $balance,
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.enrollments.index')->with('success', 'Enrollment updated successfully.');
    }

    public function destroy(Enrollment $enrollment)
    {
        $enrollment->delete();

        return redirect()->route('admin.enrollments.index')->with('success', 'Enrollment deleted successfully.');
    }
}
