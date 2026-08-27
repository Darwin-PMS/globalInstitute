<?php

use App\Http\Controllers\Api\CourseApiController;
use App\Http\Controllers\Api\FaqApiController;
use App\Http\Controllers\Api\GalleryApiController;
use App\Http\Controllers\Api\SettingApiController;
use App\Http\Controllers\Api\TestimonialApiController;
use App\Http\Controllers\Frontend\SpaController;
use App\Http\Controllers\Frontend\StudentDashboardController;
use App\Http\Controllers\Frontend\TeacherDashboardController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/admin.php';
require __DIR__.'/admin-api.php';
require __DIR__.'/online-learning.php';

// Public API endpoints
Route::get('/api/courses', [CourseApiController::class, 'index'])->name('api.courses.index');
Route::get('/api/courses/{slug}', [CourseApiController::class, 'show'])->name('api.courses.show');
Route::get('/api/testimonials', [TestimonialApiController::class, 'index'])->name('api.testimonials');
Route::get('/api/faqs', [FaqApiController::class, 'index'])->name('api.faqs');
Route::get('/api/gallery', [GalleryApiController::class, 'index'])->name('api.gallery');
Route::get('/api/settings', [SettingApiController::class, 'index'])->name('api.settings');
Route::post('/api/enquiries', [SpaController::class, 'submitEnquiry'])->name('api.enquiries');
Route::get('/api/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->name('api.csrf');

// Legacy Student Dashboard Routes
Route::prefix('student')->name('student.')->middleware('auth')->group(function () {
    Route::get('/', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/courses', [StudentDashboardController::class, 'myCourses'])->name('courses');
    Route::get('/courses/{courseId}', [StudentDashboardController::class, 'courseDetail'])->name('course-detail');
    Route::get('/classes', [StudentDashboardController::class, 'myClasses'])->name('classes');
    Route::get('/assignments', [StudentDashboardController::class, 'myAssignments'])->name('assignments');
    Route::post('/assignments/{assignment}/submit', [StudentDashboardController::class, 'submitAssignment'])->name('submit-assignment');
    Route::get('/quizzes', [StudentDashboardController::class, 'myQuizzes'])->name('quizzes');
    Route::get('/quizzes/{quiz}/take', [StudentDashboardController::class, 'takeQuiz'])->name('take-quiz');
    Route::get('/recordings', [StudentDashboardController::class, 'myRecordings'])->name('recordings');
});

// Legacy Teacher Dashboard Routes
Route::prefix('teacher')->name('teacher.')->middleware('auth')->group(function () {
    Route::get('/', [TeacherDashboardController::class, 'index'])->name('dashboard');
    Route::get('/classes', [TeacherDashboardController::class, 'myClasses'])->name('classes');
    Route::post('/classes/{onlineClass}/attendance', [TeacherDashboardController::class, 'markAttendance'])->name('mark-attendance');
    Route::get('/submissions', [TeacherDashboardController::class, 'submissions'])->name('submissions');
    Route::post('/submissions/{submission}/evaluate', [TeacherDashboardController::class, 'evaluateSubmission'])->name('evaluate-submission');
    Route::get('/quiz-results', [TeacherDashboardController::class, 'quizResults'])->name('quiz-results');
});

Route::get('/{any?}', [SpaController::class, 'index'])
    ->where('any', '.*')
    ->name('spa');

