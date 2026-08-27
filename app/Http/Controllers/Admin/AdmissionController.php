<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Enquiry;
use App\Models\User;
use App\Models\Course;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Admission::with(['enquiry', 'course', 'batch']);

        if ($request->filled('status')) {
            $query->where('admission_status', $request->status);
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('admission_number', 'like', "%{$search}%")
                  ->orWhereHas('enquiry', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $admissions = $query->latest()->paginate(15);
        $courses = Course::where('status', 'published')->orderBy('title')->get();

        return view('admin.admissions.index', compact('admissions', 'courses'));
    }

    public function create()
    {
        $enquiries = Enquiry::where('status', '!=', 'converted')->orderBy('name')->get();
        $students = User::where('role', 'student')->orderBy('name')->get();
        $courses = Course::where('status', 'published')->orderBy('title')->get();
        $batches = Batch::where('status', 'active')->orderBy('batch_name')->get();

        return view('admin.admissions.create', compact('enquiries', 'students', 'courses', 'batches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'enquiry_id' => 'nullable|exists:enquiries,id',
            'student_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'batch_id' => 'required|exists:batches,id',
            'admission_date' => 'required|date',
            'fee' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'final_fee' => 'required|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'admission_status' => 'required|in:pending,confirmed,cancelled',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $lastAdmission = Admission::orderBy('id', 'desc')->first();
            $nextNumber = $lastAdmission ? intval(substr($lastAdmission->admission_number, -6)) + 1 : 1;
            $admissionNumber = 'ADM-' . date('Y') . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            $amountPaid = $validated['amount_paid'] ?? 0;
            $balance = $validated['final_fee'] - $amountPaid;

            Admission::create([
                'admission_number' => $admissionNumber,
                'enquiry_id' => $validated['enquiry_id'] ?? null,
                'student_id' => $validated['student_id'],
                'course_id' => $validated['course_id'],
                'batch_id' => $validated['batch_id'],
                'admission_date' => $validated['admission_date'],
                'fee' => $validated['fee'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'final_fee' => $validated['final_fee'],
                'amount_paid' => $amountPaid,
                'balance' => $balance,
                'payment_status' => $balance <= 0 ? 'paid' : ($amountPaid > 0 ? 'partial' : 'unpaid'),
                'admission_status' => $validated['admission_status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            if (!empty($validated['enquiry_id'])) {
                Enquiry::where('id', $validated['enquiry_id'])->update(['status' => 'converted']);
            }
        });

        return redirect()->route('admin.admissions.index')->with('success', 'Admission created successfully.');
    }

    public function show(Admission $admission)
    {
        $admission->load(['enquiry', 'student', 'course', 'batch', 'enrollment']);

        return view('admin.admissions.show', compact('admission'));
    }

    public function edit(Admission $admission)
    {
        $enquiries = Enquiry::orderBy('name')->get();
        $students = User::where('role', 'student')->orderBy('name')->get();
        $courses = Course::where('status', 'published')->orderBy('title')->get();
        $batches = Batch::orderBy('batch_name')->get();

        return view('admin.admissions.edit', compact('admission', 'enquiries', 'students', 'courses', 'batches'));
    }

    public function update(Request $request, Admission $admission)
    {
        $validated = $request->validate([
            'enquiry_id' => 'nullable|exists:enquiries,id',
            'student_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'batch_id' => 'required|exists:batches,id',
            'admission_date' => 'required|date',
            'fee' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'final_fee' => 'required|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'admission_status' => 'required|in:pending,confirmed,cancelled',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $admission) {
            $amountPaid = $validated['amount_paid'] ?? 0;
            $balance = $validated['final_fee'] - $amountPaid;

            $admission->update([
                'enquiry_id' => $validated['enquiry_id'] ?? null,
                'student_id' => $validated['student_id'],
                'course_id' => $validated['course_id'],
                'batch_id' => $validated['batch_id'],
                'admission_date' => $validated['admission_date'],
                'fee' => $validated['fee'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'final_fee' => $validated['final_fee'],
                'amount_paid' => $amountPaid,
                'balance' => $balance,
                'payment_status' => $balance <= 0 ? 'paid' : ($amountPaid > 0 ? 'partial' : 'unpaid'),
                'admission_status' => $validated['admission_status'],
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()->route('admin.admissions.index')->with('success', 'Admission updated successfully.');
    }

    public function destroy(Admission $admission)
    {
        $admission->delete();

        return redirect()->route('admin.admissions.index')->with('success', 'Admission deleted successfully.');
    }
}
