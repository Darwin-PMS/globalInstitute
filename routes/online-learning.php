<?php

use App\Http\Controllers\Api\OnlineLearning\AdminLearningApiController;
use App\Http\Controllers\Api\OnlineLearning\ClassApiController;
use App\Http\Controllers\Api\OnlineLearning\StudentLearningApiController;
use App\Http\Controllers\Api\OnlineLearning\TeacherLearningApiController;
use App\Http\Controllers\Api\OnlineLearning\Room\RoomApiController;
use App\Http\Controllers\Api\OnlineLearning\Room\RoomEntryApiController;
use App\Http\Controllers\Api\OnlineLearning\Room\RoomMessageApiController;
use App\Http\Controllers\Api\OnlineLearning\Room\RoomPanelApiController;
use App\Http\Controllers\Api\OnlineLearningApiController;
use App\Http\Controllers\Frontend\SpaController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Online Learning Module
|--------------------------------------------------------------------------
| JSON API + SPA application shell routes for the Online Learning domain.
|
| The API returns the standard envelope { success, message, data, meta };
| the shell routes are catch-all so deep links and F5 both resolve in JS.
*/

Route::prefix('api/online-learning')
    ->middleware('auth')
    ->name('learning.api.')
    ->group(function () {

        /*
        | Shared - role resolved server-side inside the controller
        */
        Route::get('classes/{onlineClass}', [ClassApiController::class, 'show'])->name('classes.show');
        Route::get('calendar', [ClassApiController::class, 'calendar'])->name('calendar');

        Route::get('classes/{onlineClass}/attendance', [OnlineLearningApiController::class, 'getClassAttendance'])->name('classes.attendance.index');
        Route::post('classes/{onlineClass}/attendance', [OnlineLearningApiController::class, 'markAttendance'])->name('classes.attendance.store');
        Route::post('classes/{onlineClass}/reschedule', [OnlineLearningApiController::class, 'rescheduleClass'])->name('classes.reschedule');
        Route::post('classes/{onlineClass}/cancel', [OnlineLearningApiController::class, 'cancelClass'])->name('classes.cancel');

        /*
        |----------------------------------------------------------------------
        | Live Class Room
        |----------------------------------------------------------------------
        | Every scheduled class has its own independent room, addressed by an
        | opaque key. Reads are split per panel so the client lazy-loads, and the
        | feed endpoints take `?after=<id>` so they return deltas — the same
        | shape a WebSocket transport would push.
        */
        Route::get('classes/{onlineClass}/room', [RoomEntryApiController::class, 'resolve'])->name('rooms.resolve');

        Route::prefix('rooms/{room}')->name('rooms.')->group(function () {
            // Shell + lifecycle
            Route::get('/', [RoomApiController::class, 'bootstrap'])->name('bootstrap');
            Route::get('state', [RoomApiController::class, 'state'])->name('state');
            Route::post('enter', [RoomApiController::class, 'enter'])->name('enter');
            Route::post('heartbeat', [RoomApiController::class, 'heartbeat'])->name('heartbeat');
            Route::post('leave', [RoomApiController::class, 'leave'])->name('leave');
            Route::post('start', [RoomApiController::class, 'start'])->name('start');
            Route::post('end', [RoomApiController::class, 'end'])->name('end');
            Route::put('settings', [RoomApiController::class, 'updateSettings'])->name('settings');

            // Lazy-loaded panels
            Route::get('materials', [RoomPanelApiController::class, 'materials'])->name('materials');
            Route::get('recordings', [RoomPanelApiController::class, 'recordings'])->name('recordings');
            Route::get('assignments', [RoomPanelApiController::class, 'assignments'])->name('assignments');
            Route::get('roster', [RoomPanelApiController::class, 'roster'])->name('roster');
            Route::get('presence', [RoomPanelApiController::class, 'presence'])->name('presence');
            Route::get('activity', [RoomPanelApiController::class, 'activity'])->name('activity');
            Route::get('attendance', [RoomPanelApiController::class, 'attendance'])->name('attendance.index');
            Route::post('attendance', [RoomPanelApiController::class, 'saveAttendance'])->name('attendance.store');

            // Chat + announcements
            Route::get('messages', [RoomMessageApiController::class, 'index'])->name('messages.index');
            Route::post('messages', [RoomMessageApiController::class, 'store'])->name('messages.store');
            Route::post('messages/{message}/pin', [RoomMessageApiController::class, 'pin'])->name('messages.pin');
            Route::delete('messages/{message}', [RoomMessageApiController::class, 'destroy'])->name('messages.destroy');
        });

        /*
        | Student
        */
        Route::prefix('student')->name('student.')->group(function () {
            Route::get('dashboard', [OnlineLearningApiController::class, 'studentDashboard'])->name('dashboard');
            Route::get('classes', [OnlineLearningApiController::class, 'studentClasses'])->name('classes');
            Route::get('courses/{courseId}', [OnlineLearningApiController::class, 'studentCourseDetail'])->name('courses.show');
            Route::post('classes/{onlineClass}/join', [OnlineLearningApiController::class, 'joinClass'])->name('classes.join');

            Route::get('recordings', [StudentLearningApiController::class, 'recordings'])->name('recordings');
            Route::get('materials', [StudentLearningApiController::class, 'materials'])->name('materials');
            Route::get('assignments', [StudentLearningApiController::class, 'assignments'])->name('assignments');
            Route::post('assignments/{assignment}/submit', [StudentLearningApiController::class, 'submitAssignment'])->name('assignments.submit');
            Route::get('progress', [StudentLearningApiController::class, 'progress'])->name('progress');
        });

        /*
        | Teacher
        */
        Route::prefix('teacher')->name('teacher.')->group(function () {
            Route::get('dashboard', [OnlineLearningApiController::class, 'teacherDashboard'])->name('dashboard');
            Route::get('classes', [OnlineLearningApiController::class, 'teacherClasses'])->name('classes');

            Route::get('submissions', [TeacherLearningApiController::class, 'submissions'])->name('submissions');
            Route::post('submissions/{submission}/evaluate', [TeacherLearningApiController::class, 'evaluateSubmission'])->name('submissions.evaluate');
            Route::post('classes/{onlineClass}/complete', [TeacherLearningApiController::class, 'completeClass'])->name('classes.complete');
        });

        /*
        | Admin
        */
        Route::prefix('admin')->name('admin.')->middleware(AdminMiddleware::class)->group(function () {
            Route::get('summary', [AdminLearningApiController::class, 'summary'])->name('summary');
            Route::get('attendance', [AdminLearningApiController::class, 'attendanceLog'])->name('attendance');
            Route::get('recordings', [AdminLearningApiController::class, 'recordings'])->name('recordings');
            Route::post('recordings/{recording}/toggle-publish', [AdminLearningApiController::class, 'toggleRecordingPublish'])->name('recordings.toggle-publish');
            Route::get('materials', [AdminLearningApiController::class, 'materials'])->name('materials');
            Route::post('materials/{material}/toggle-publish', [AdminLearningApiController::class, 'toggleMaterialPublish'])->name('materials.toggle-publish');
        });
    });

/*
|--------------------------------------------------------------------------
| SPA Application Shell
|--------------------------------------------------------------------------
| Every path under these prefixes returns the same Blade shell; the JS
| router resolves the actual screen. This is what makes deep links such as
| /student/learning/classes/123 survive a refresh (§55, §56).
*/

Route::middleware('auth')->group(function () {
    /*
     | The Live Class Room is its own application shell on its own URL, shared by
     | every role. Keeping it separate from the role dashboards is what lets the
     | room be lifted out as a standalone product.
     */
    Route::get('/learning/room/{roomKey}', [SpaController::class, 'classRoomShell'])
        ->name('learning.room');

    Route::get('/student/learning/{any?}', [SpaController::class, 'onlineLearningShell'])
        ->where('any', '.*')->name('student.learning');

    Route::get('/teacher/online-learning/{any?}', [SpaController::class, 'onlineLearningShell'])
        ->where('any', '.*')->name('teacher.learning');

    Route::get('/admin/online-learning/{any?}', [SpaController::class, 'onlineLearningShell'])
        ->where('any', '.*')->middleware(AdminMiddleware::class)->name('admin.learning');
});
