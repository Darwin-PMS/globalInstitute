<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CourseCategoryController;
use App\Http\Controllers\Admin\CourseModuleController;
use App\Http\Controllers\Admin\AdminSPAController;
use App\Http\Controllers\Admin\EnquiryController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\LeadSourceController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\BatchController;
use App\Http\Controllers\Admin\FeeStructureController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\AdmissionController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\OnlineClassController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\RecordingController;
use App\Http\Controllers\Admin\StudentProgressController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.submit');

    Route::middleware(['auth', \App\Http\Middleware\AdminMiddleware::class])->group(function () {

        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', [AdminSPAController::class, 'index'])->name('dashboard');

        // CMS
        Route::resource('courses', CourseController::class)->except(['show']);
        Route::resource('categories', CourseCategoryController::class)->except(['show']);
        Route::resource('testimonials', TestimonialController::class)->except(['show']);
        Route::resource('faqs', FaqController::class)->except(['show']);
        Route::resource('gallery', GalleryController::class);
        Route::post('gallery/bulk-action', [GalleryController::class, 'bulkAction'])->name('gallery.bulk-action');
        Route::post('gallery/{galleryImage}/toggle-status', [GalleryController::class, 'toggleStatus'])->name('gallery.toggle-status');
        Route::post('gallery/{galleryImage}/toggle-featured', [GalleryController::class, 'toggleFeatured'])->name('gallery.toggle-featured');
        Route::resource('pages', PageController::class)->except(['show']);

        // CRM
        Route::resource('enquiries', EnquiryController::class);

        // Academic
        Route::resource('teachers', TeacherController::class)->except(['show']);
        Route::resource('rooms', RoomController::class)->except(['show']);
        Route::resource('batches', BatchController::class)->except(['show']);
        Route::resource('course-modules', CourseModuleController::class)->except(['show']);

        // Fee Structure
        Route::resource('fee-structures', FeeStructureController::class)->except(['show']);

        // Students
        Route::resource('students', StudentController::class);

        // Admissions
        Route::resource('admissions', AdmissionController::class);

        // Enrollments
        Route::resource('enrollments', EnrollmentController::class);

        // Payments
        Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show']);

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('fee-collection', [ReportController::class, 'feeCollection'])->name('fee-collection');
            Route::get('payments', [ReportController::class, 'paymentReport'])->name('payments');
            Route::get('students', [ReportController::class, 'studentReport'])->name('students');
            Route::get('courses', [ReportController::class, 'courseReport'])->name('courses');
            Route::get('batches', [ReportController::class, 'batchReport'])->name('batches');
        });

        // Settings
        Route::get('settings', [SiteSettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SiteSettingController::class, 'update'])->name('settings.update');

        // Lead Sources (CRM)
        Route::resource('lead-sources', LeadSourceController::class)
            ->except(['show'])
            ->parameters(['lead-sources' => 'leadSource'])
            ->names('lead_sources');

        // Online Learning / LMS
        Route::resource('lessons', LessonController::class)->except(['show']);
        Route::resource('online-classes', OnlineClassController::class)->except(['show']);
        Route::resource('materials', MaterialController::class)->except(['show']);
        Route::resource('assignments', AssignmentController::class)->except(['show']);
        Route::resource('recordings', RecordingController::class)->except(['show']);

        // Quizzes with question management
        Route::resource('quizzes', QuizController::class)->except(['show']);
        Route::get('quizzes/{quiz}/add-question', [QuizController::class, 'addQuestion'])->name('quizzes.add-question');
        Route::post('quizzes/{quiz}/store-question', [QuizController::class, 'storeQuestion'])->name('quizzes.store-question');
        Route::delete('quizzes/{quiz}/questions/{question}', [QuizController::class, 'destroyQuestion'])->name('quizzes.destroy-question');

        // Student Progress
        Route::get('student-progress', [StudentProgressController::class, 'index'])->name('student-progress.index');
        Route::get('student-progress/{enrollment}', [StudentProgressController::class, 'show'])->name('student-progress.show');
    });
});
