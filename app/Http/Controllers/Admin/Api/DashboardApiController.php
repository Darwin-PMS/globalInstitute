<?php
namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Student;
use App\Models\Admission;
use App\Models\Trainer;
use App\Models\Enrollment;
use App\Models\Enquiry;
use App\Models\OnlineClass;
use App\Models\Payment;
use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function index()
    {
        $totalStudents = Student::count();
        $totalTeachers = Trainer::count();
        $activeBatches = Batch::where('status', 'active')->count();
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');

        $thisMonthAdmissions = Admission::whereMonth('admission_date', Carbon::now()->month)
            ->whereYear('admission_date', Carbon::now()->year)
            ->count();

        $pendingEnquiries = Enquiry::where('status', 'new')->count();

        $topCourses = Course::withCount(['enrollments'])
            ->orderByDesc('enrollments_count')
            ->take(3)
            ->get()
            ->map(function ($course) {
                $course->revenue = $course->enrollments()->sum('total_paid');
                return $course;
            });

        $revenueByMonth = Payment::where('status', 'completed')
            ->where('payment_date', '>=', Carbon::now()->subMonths(5)->startOfMonth())
            ->select(
                DB::raw('YEAR(payment_date) as year'),
                DB::raw('MONTH(payment_date) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return response()->json([
            'totalStudents' => $totalStudents,
            'totalTeachers' => $totalTeachers,
            'activeBatches' => $activeBatches,
            'totalRevenue' => $totalRevenue,
            'thisMonthAdmissions' => $thisMonthAdmissions,
            'pendingEnquiries' => $pendingEnquiries,
            'topCourses' => $topCourses,
            'revenueByMonth' => $revenueByMonth,
            'recent_enquiries' => Enquiry::latest()->limit(5)->get(['id', 'name', 'email', 'subject', 'status', 'created_at']),
            'recent_payments' => Payment::with('student.user')->latest()->limit(5)->get(),
        ]);
    }
}