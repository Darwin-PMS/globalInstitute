<?php

use App\Http\Controllers\Admin\Api\AuthApiController;
use App\Http\Controllers\Admin\Api\DashboardApiController;
use App\Http\Controllers\Admin\Api\CourseApiController;
use App\Http\Controllers\Admin\Api\CategoryApiController;
use App\Http\Controllers\Admin\Api\TeacherApiController;
use App\Http\Controllers\Admin\Api\StudentApiController;
use App\Http\Controllers\Admin\Api\BatchApiController;
use App\Http\Controllers\Admin\Api\EnquiryApiController;
use App\Http\Controllers\Admin\Api\GalleryApiController;
use App\Http\Controllers\Admin\Api\LessonApiController;
use App\Http\Controllers\Admin\Api\OnlineClassApiController;
use App\Http\Controllers\Admin\Api\MaterialApiController;
use App\Http\Controllers\Admin\Api\AssignmentApiController;
use App\Http\Controllers\Admin\Api\QuizApiController;
use App\Http\Controllers\Admin\Api\RecordingApiController;
use App\Http\Controllers\Admin\Api\ProgressApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/admin')->name('api.admin.')->group(function () {

    // Public (no auth required)
    Route::post('login', [AuthApiController::class, 'login'])->name('login');

    // Protected (auth required)
    Route::middleware(['auth', \App\Http\Middleware\AdminMiddleware::class])->group(function () {

        Route::post('logout', [AuthApiController::class, 'logout'])->name('logout');
        Route::get('me', [AuthApiController::class, 'me'])->name('me');

        // Sidebar (dynamic menu)
        Route::get('sidebar', function () {
            $user = auth()->user();
            $sidebar = \App\Helpers\SidebarHelper::forUser($user);
            $permissions = $user->getEffectivePermissions();
            return response()->json([
                'success' => true,
                'data' => [
                    'menus' => $sidebar,
                    'permissions' => $permissions,
                    'is_super_admin' => $user->hasRole('super-admin'),
                ],
            ]);
        })->name('sidebar');

        // Dashboard
        Route::get('dashboard', [DashboardApiController::class, 'index'])->name('dashboard');

        // Courses
        Route::apiResource('courses', CourseApiController::class);

        // Categories
        Route::apiResource('categories', CategoryApiController::class);

        // Teachers
        Route::apiResource('teachers', TeacherApiController::class);

        // Students
        Route::apiResource('students', StudentApiController::class)->only(['index', 'show']);

        // Batches
        Route::apiResource('batches', BatchApiController::class);

        // Enquiries
        Route::apiResource('enquiries', EnquiryApiController::class)->only(['index', 'show', 'update', 'destroy']);

        // Gallery
        Route::get('gallery', [GalleryApiController::class, 'index'])->name('gallery.index');
        Route::get('gallery/categories', [GalleryApiController::class, 'categories'])->name('gallery.categories');
        Route::post('gallery', [GalleryApiController::class, 'store'])->name('gallery.store');
        Route::put('gallery/{image}', [GalleryApiController::class, 'update'])->name('gallery.update');
        Route::delete('gallery/{image}', [GalleryApiController::class, 'destroy'])->name('gallery.destroy');
        Route::post('gallery/bulk-action', [GalleryApiController::class, 'bulkAction'])->name('gallery.bulk-action');
        Route::post('gallery/{image}/toggle-status', [GalleryApiController::class, 'toggleStatus'])->name('gallery.toggle-status');
        Route::post('gallery/{image}/toggle-featured', [GalleryApiController::class, 'toggleFeatured'])->name('gallery.toggle-featured');

        // Lessons
        Route::get('lessons/modules', [LessonApiController::class, 'modules'])->name('lessons.modules');
        Route::apiResource('lessons', LessonApiController::class);

        // Online Classes
        Route::get('online-classes/form-data', [OnlineClassApiController::class, 'formData'])->name('online-classes.form-data');
        Route::apiResource('online-classes', OnlineClassApiController::class);

        // Materials
        Route::apiResource('materials', MaterialApiController::class);

        // Assignments
        Route::apiResource('assignments', AssignmentApiController::class);

        // Quizzes
        Route::apiResource('quizzes', QuizApiController::class);
        Route::post('quizzes/{quiz}/questions', [QuizApiController::class, 'storeQuestion'])->name('quizzes.store-question');
        Route::delete('quizzes/{quiz}/questions/{question}', [QuizApiController::class, 'destroyQuestion'])->name('quizzes.destroy-question');

        // Recordings
        Route::apiResource('recordings', RecordingApiController::class);

        // Progress
        Route::get('progress', [ProgressApiController::class, 'index'])->name('progress.index');
        Route::get('progress/{enrollment}', [ProgressApiController::class, 'show'])->name('progress.show');
    });
});
