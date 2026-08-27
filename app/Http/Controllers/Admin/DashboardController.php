<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Student;
use App\Models\Batch;
use App\Models\Payment;
use App\Models\Enquiry;
use App\Models\Admission;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalStudents = Student::count();
        $totalTeachers = \App\Models\Trainer::count();
        $activeBatches = Batch::where('status', 'active')->count();
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');

        $thisMonthAdmissions = Admission::whereMonth('admission_date', Carbon::now()->month)
            ->whereYear('admission_date', Carbon::now()->year)
            ->count();

        $pendingEnquiries = Enquiry::where('status', 'new')->count();

        $recentPayments = Payment::with('student.user')
            ->latest('payment_date')
            ->take(5)
            ->get();

        $recentEnquiries = Enquiry::latest()->take(5)->get();

        $monthlyRevenue = Payment::where('status', 'completed')
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

        $revenueByMonth = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthData = $monthlyRevenue->first(function ($m) use ($date) {
                return $m->year == $date->year && $m->month == $date->month;
            });
            $revenueByMonth->push([
                'label' => $date->format('M Y'),
                'total' => $monthData ? $monthData->total : 0,
            ]);
        }

        $topCourses = Course::withCount(['enrollments'])
            ->orderByDesc('enrollments_count')
            ->take(3)
            ->get()
            ->map(function ($course) {
                $course->revenue = $course->enrollments()->sum('total_paid');
                return $course;
            });

        return view('admin.dashboard', compact(
            'totalStudents',
            'totalTeachers',
            'activeBatches',
            'totalRevenue',
            'thisMonthAdmissions',
            'pendingEnquiries',
            'recentPayments',
            'recentEnquiries',
            'revenueByMonth',
            'topCourses'
        ));
    }
}
