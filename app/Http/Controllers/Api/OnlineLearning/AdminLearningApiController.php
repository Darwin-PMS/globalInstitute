<?php

namespace App\Http\Controllers\Api\OnlineLearning;

use App\Http\Controllers\Api\OnlineLearning\Concerns\ApiResponses;
use App\Http\Controllers\Api\OnlineLearning\Concerns\ResolvesLearningActor;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\LearningMaterial;
use App\Models\OnlineClass;
use App\Models\Recording;
use Illuminate\Http\Request;

/**
 * Admin-only Online Learning screens: operations summary, attendance log,
 * recordings library, materials library.
 *
 * Route group already carries auth + AdminMiddleware; the guards here are a
 * second line rather than the only one.
 */
class AdminLearningApiController extends Controller
{
    use ApiResponses;
    use ResolvesLearningActor;

    /**
     * GET /api/online-learning/admin/summary
     *
     * The dashboard summary cards. Each figure is counted in the database
     * rather than derived from a page of rows in the browser.
     */
    public function summary()
    {
        $today = today();

        $todayScope = fn () => OnlineClass::whereDate('class_date', $today);

        return $this->ok([
            'today' => [
                'total' => $todayScope()->count(),
                'live' => $todayScope()->where('status', 'live')->count(),
                'starting_soon' => $todayScope()->where('status', 'starting_soon')->count(),
                'completed' => $todayScope()->where('status', 'completed')->count(),
                'cancelled' => $todayScope()->where('status', 'cancelled')->count(),
            ],
            'upcoming' => OnlineClass::whereDate('class_date', '>', $today)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->count(),
            'live_now' => OnlineClass::where('status', 'live')->count(),
            'total_classes' => OnlineClass::count(),
            'completed_classes' => OnlineClass::where('status', 'completed')->count(),
            'cancelled_classes' => OnlineClass::where('status', 'cancelled')->count(),
            // A finished class flagged as recorded but with nothing published yet.
            'pending_recordings' => OnlineClass::where('status', 'completed')
                ->where('is_recording_available', true)
                ->whereDoesntHave('recordings', fn ($q) => $q->where('is_published', true))
                ->count(),
            'pending_assignments' => AssignmentSubmission::whereNull('marks')->count(),
            'published_recordings' => Recording::where('is_published', true)->count(),
            'draft_assignments' => Assignment::where('status', 'draft')->count(),
        ]);
    }

    /**
     * GET /api/online-learning/admin/attendance
     *
     * One row per class with its attendance roll-up, so an admin can spot
     * classes that were never marked.
     */
    public function attendanceLog(Request $request)
    {
        $query = OnlineClass::with(['course', 'batch', 'teacher'])
            ->withCount([
                'attendance as attendance_total',
                'attendance as present_count' => fn ($q) => $q->where('status', 'present'),
                'attendance as late_count' => fn ($q) => $q->where('status', 'late'),
                'attendance as absent_count' => fn ($q) => $q->where('status', 'absent'),
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('class_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('class_date', '<=', $request->to);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // marked=no surfaces the classes still missing an attendance record.
        if ($request->get('marked') === 'no') {
            $query->whereDoesntHave('attendance');
        } elseif ($request->get('marked') === 'yes') {
            $query->whereHas('attendance');
        }

        return $this->paginated(
            $query->latest('class_date')->latest('start_time')
                ->paginate($this->perPage($request->get('per_page')))
        );
    }

    /**
     * GET /api/online-learning/admin/recordings
     */
    public function recordings(Request $request)
    {
        $query = Recording::with(['onlineClass.course', 'onlineClass.teacher', 'onlineClass.batch']);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('course_id')) {
            $query->whereHas('onlineClass', fn ($q) => $q->where('course_id', $request->course_id));
        }

        $published = $request->get('published');
        if ($published === 'yes') {
            $query->where('is_published', true);
        } elseif ($published === 'no') {
            $query->where('is_published', false);
        }

        return $this->paginated(
            $query->latest()->paginate($this->perPage($request->get('per_page')))
        );
    }

    /**
     * POST /api/online-learning/admin/recordings/{recording}/toggle-publish
     */
    public function toggleRecordingPublish(Recording $recording)
    {
        $recording->update(['is_published' => ! $recording->is_published]);

        return $this->ok(
            $recording->fresh(),
            $recording->is_published ? 'Recording published to students.' : 'Recording unpublished.'
        );
    }

    /**
     * GET /api/online-learning/admin/materials
     */
    public function materials(Request $request)
    {
        $query = LearningMaterial::with(['course']);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->filled('file_type')) {
            $query->where('file_type', $request->file_type);
        }

        $published = $request->get('published');
        if ($published === 'yes') {
            $query->where('is_published', true);
        } elseif ($published === 'no') {
            $query->where('is_published', false);
        }

        return $this->paginated(
            $query->latest()->paginate($this->perPage($request->get('per_page')))
        );
    }

    /**
     * POST /api/online-learning/admin/materials/{material}/toggle-publish
     */
    public function toggleMaterialPublish(LearningMaterial $material)
    {
        $material->update(['is_published' => ! $material->is_published]);

        return $this->ok(
            $material->fresh(),
            $material->is_published ? 'Material published to students.' : 'Material unpublished.'
        );
    }
}
