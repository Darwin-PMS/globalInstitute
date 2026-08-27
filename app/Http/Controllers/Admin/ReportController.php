<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Course;
use App\Models\Batch;
use App\Models\Enrollment;
use App\Models\FeeInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function feeCollection(Request $request)
    {
        $query = Payment::with(['student.user', 'enrollment.course']);

        if ($request->filled('date_from')) {
            $query->where('payment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('payment_date', '<=', $request->date_to);
        }
        if ($request->filled('course_id')) {
            $query->whereHas('enrollment', function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }

        $payments = $query->latest('payment_date')->get();

        $totalCollected = $payments->where('status', 'completed')->sum('amount');
        $totalPending = $payments->where('status', 'pending')->sum('amount');
        $thisMonth = $payments->filter(function ($p) {
            return $p->payment_date->isCurrentMonth();
        })->where('status', 'completed')->sum('amount');

        $grouped = $payments->groupBy(function ($p) {
            return $p->payment_date->format('Y-m-d');
        });

        $courses = Course::orderBy('title')->get();

        return view('admin.reports.fee-collection', compact(
            'payments', 'totalCollected', 'totalPending', 'thisMonth', 'grouped', 'courses'
        ));
    }

    public function paymentReport(Request $request)
    {
        $query = Payment::with(['student.user', 'enrollment.course']);

        if ($request->filled('date_from')) {
            $query->where('payment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('payment_date', '<=', $request->date_to);
        }
        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->latest('payment_date')->paginate(20)->withQueryString();

        $totalAmount = $payments->total() ? $payments->getCollection()->sum('amount') : 0;
        $cashTotal = Payment::where('payment_method', 'cash')->sum('amount');
        $upiTotal = Payment::where('payment_method', 'upi')->sum('amount');
        $bankTotal = Payment::whereIn('payment_method', ['bank_transfer', 'online'])->sum('amount');

        return view('admin.reports.payments', compact(
            'payments', 'totalAmount', 'cashTotal', 'upiTotal', 'bankTotal'
        ));
    }

    public function studentReport(Request $request)
    {
        $query = Student::with(['user', 'enrollments.course', 'enrollments.batch']);

        if ($request->filled('course_id')) {
            $query->whereHas('enrollments', function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }
        if ($request->filled('batch_id')) {
            $query->whereHas('enrollments', function ($q) use ($request) {
                $q->where('batch_id', $request->batch_id);
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $students = $query->get()->map(function ($student) {
            $student->enrollment_count = $student->enrollments->count();
            $student->total_paid = $student->enrollments->sum('total_paid');
            $student->last_payment = Payment::where('student_id', $student->id)
                ->latest('payment_date')
                ->first();
            return $student;
        });

        $totalStudents = Student::count();
        $activeStudents = Student::where('status', 'active')->count();
        $enrolledStudents = Student::whereHas('enrollments')->count();

        $courses = Course::orderBy('title')->get();
        $batches = Batch::orderBy('batch_name')->get();

        return view('admin.reports.students', compact(
            'students', 'totalStudents', 'activeStudents', 'enrolledStudents', 'courses', 'batches'
        ));
    }

    public function courseReport()
    {
        $courses = Course::withCount(['enrollments'])
            ->get()
            ->map(function ($course) {
                $enrollments = $course->enrollments()->with('student')->get();
                $course->revenue = $enrollments->sum('total_paid');
                $course->avg_fee = $enrollments->count() > 0
                    ? $enrollments->avg('final_fee')
                    : 0;
                $totalEnrollments = $enrollments->count();
                $completedEnrollments = $enrollments->where('status', 'completed')->count();
                $course->completion_rate = $totalEnrollments > 0
                    ? round(($completedEnrollments / $totalEnrollments) * 100, 1)
                    : 0;
                return $course;
            });

        return view('admin.reports.courses', compact('courses'));
    }

    public function batchReport()
    {
        $batches = Batch::with(['course', 'trainer', 'enrollments'])
            ->get()
            ->map(function ($batch) {
                $batch->student_count = $batch->enrollments->count();
                $totalEnrollments = $batch->enrollments->count();
                $completedEnrollments = $batch->enrollments->where('status', 'completed')->count();
                $batch->progress = $totalEnrollments > 0
                    ? round(($completedEnrollments / $totalEnrollments) * 100, 1)
                    : 0;
                return $batch;
            });

        return view('admin.reports.batches', compact('batches'));
    }
}
