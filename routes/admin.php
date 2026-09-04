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
use App\Http\Controllers\Admin\LiveClassroomController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserPermissionController;
use App\Http\Controllers\Admin\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.submit');

    Route::middleware(['auth', \App\Http\Middleware\AdminMiddleware::class])->group(function () {

        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', [AdminSPAController::class, 'index'])->name('dashboard')
            ->middleware('permission:dashboard.view');

        // CMS
        Route::resource('courses', CourseController::class)->except(['show'])
            ->middleware('permission:courses.view,requireAll');
        Route::resource('categories', CourseCategoryController::class)->except(['show'])
            ->middleware('permission:categories.view,requireAll');
        Route::resource('testimonials', TestimonialController::class)->except(['show'])
            ->middleware('permission:testimonials.view,requireAll');
        Route::resource('faqs', FaqController::class)->except(['show'])
            ->middleware('permission:faqs.view,requireAll');
        Route::resource('gallery', GalleryController::class)
            ->middleware('permission:gallery.view,requireAll');
        Route::post('gallery/bulk-action', [GalleryController::class, 'bulkAction'])->name('gallery.bulk-action')
            ->middleware('permission:gallery.edit');
        Route::post('gallery/{galleryImage}/toggle-status', [GalleryController::class, 'toggleStatus'])->name('gallery.toggle-status')
            ->middleware('permission:gallery.edit');
        Route::post('gallery/{galleryImage}/toggle-featured', [GalleryController::class, 'toggleFeatured'])->name('gallery.toggle-featured')
            ->middleware('permission:gallery.edit');
        Route::resource('pages', PageController::class)->except(['show'])
            ->middleware('permission:pages.view,requireAll');

        // CRM
        Route::resource('enquiries', EnquiryController::class)
            ->middleware('permission:enquiries.view,requireAll');

        // Academic
        Route::resource('teachers', TeacherController::class)->except(['show'])
            ->middleware('permission:teachers.view,requireAll');
        Route::resource('rooms', RoomController::class)->except(['show'])
            ->middleware('permission:rooms.view,requireAll');
        Route::resource('batches', BatchController::class)->except(['show'])
            ->middleware('permission:batches.view,requireAll');
        Route::resource('course-modules', CourseModuleController::class)->except(['show'])
            ->middleware('permission:modules.view,requireAll');

        // Fee Structure
        Route::resource('fee-structures', FeeStructureController::class)->except(['show'])
            ->middleware('permission:fee_structure.view,requireAll');

        // Students
        Route::resource('students', StudentController::class)
            ->middleware('permission:students.view,requireAll');

        // Admissions
        Route::resource('admissions', AdmissionController::class)
            ->middleware('permission:admissions.view,requireAll');

        // Enrollments
        Route::resource('enrollments', EnrollmentController::class)
            ->middleware('permission:enrollments.view,requireAll');

        // Payments
        Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show'])
            ->middleware('permission:payments.view,requireAll');

        // Reports
        Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('fee-collection', [ReportController::class, 'feeCollection'])->name('fee-collection');
            Route::get('payments', [ReportController::class, 'paymentReport'])->name('payments');
            Route::get('students', [ReportController::class, 'studentReport'])->name('students');
            Route::get('courses', [ReportController::class, 'courseReport'])->name('courses');
            Route::get('batches', [ReportController::class, 'batchReport'])->name('batches');
        });

        // Settings
        Route::get('settings', [SiteSettingController::class, 'edit'])->name('settings.edit')
            ->middleware('permission:settings.view');
        Route::put('settings', [SiteSettingController::class, 'update'])->name('settings.update')
            ->middleware('permission:settings.edit');

        // Lead Sources (CRM)
        Route::resource('lead-sources', LeadSourceController::class)
            ->except(['show'])
            ->parameters(['lead-sources' => 'leadSource'])
            ->names('lead_sources')
            ->middleware('permission:lead_sources.view,requireAll');

        // Online Learning / LMS
        Route::get('live-classroom', [LiveClassroomController::class, 'index'])->name('live-classroom.index')
            ->middleware('permission:live_classroom.manage');
        Route::resource('lessons', LessonController::class)->except(['show'])
            ->middleware('permission:lessons.view,requireAll');
        Route::resource('online-classes', OnlineClassController::class)->except(['show'])
            ->middleware('permission:online_classes.view,requireAll');
        Route::resource('materials', MaterialController::class)->except(['show'])
            ->middleware('permission:materials.view,requireAll');
        Route::resource('assignments', AssignmentController::class)->except(['show'])
            ->middleware('permission:assignments.view,requireAll');
        Route::resource('recordings', RecordingController::class)->except(['show'])
            ->middleware('permission:recordings.view,requireAll');

        // Quizzes with question management
        Route::resource('quizzes', QuizController::class)->except(['show'])
            ->middleware('permission:quizzes.view,requireAll');
        Route::get('quizzes/{quiz}/add-question', [QuizController::class, 'addQuestion'])->name('quizzes.add-question')
            ->middleware('permission:quizzes.create');
        Route::post('quizzes/{quiz}/store-question', [QuizController::class, 'storeQuestion'])->name('quizzes.store-question')
            ->middleware('permission:quizzes.create');
        Route::delete('quizzes/{quiz}/questions/{question}', [QuizController::class, 'destroyQuestion'])->name('quizzes.destroy-question')
            ->middleware('permission:quizzes.delete');

        // Student Progress
        Route::get('student-progress', [StudentProgressController::class, 'index'])->name('student-progress.index')
            ->middleware('permission:student_progress.view');
        Route::get('student-progress/{enrollment}', [StudentProgressController::class, 'show'])->name('student-progress.show')
            ->middleware('permission:student_progress.view');

        // Roles & Permissions (RBAC Management)
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index')
            ->middleware('permission:roles.view');
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create')
            ->middleware('permission:roles.create');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store')
            ->middleware('permission:roles.create');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit')
            ->middleware('permission:roles.edit');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update')
            ->middleware('permission:roles.edit');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy')
            ->middleware('permission:roles.delete');
        Route::post('roles/{role}/duplicate', [RoleController::class, 'duplicate'])->name('roles.duplicate')
            ->middleware('permission:roles.create');

        // User Management
        Route::get('users', [UserPermissionController::class, 'index'])->name('users.index')
            ->middleware('permission:users.view');
        Route::get('users/{user}/permissions', [UserPermissionController::class, 'permissions'])->name('users.permissions')
            ->middleware('permission:users.edit');
        Route::put('users/{user}/permissions', [UserPermissionController::class, 'updatePermissions'])->name('users.permissions.update')
            ->middleware('permission:users.edit');

        // Audit Logs
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index')
            ->middleware('permission:audit_logs.view');
    });
});
