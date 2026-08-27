<?php

namespace App\Http\Controllers\Api\OnlineLearning;

use App\Http\Controllers\Api\OnlineLearning\Concerns\ApiResponses;
use App\Http\Controllers\Api\OnlineLearning\Concerns\ResolvesLearningActor;
use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\OnlineClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Teacher-only Online Learning screens: submissions queue + grading.
 * Deliberately narrow - a teacher does not get the admin surface.
 */
class TeacherLearningApiController extends Controller
{
    use ApiResponses;
    use ResolvesLearningActor;

    /**
     * GET /api/online-learning/teacher/submissions
     */
    public function submissions(Request $request)
    {
        $teacher = $this->actingTeacher();
        if (! $teacher) {
            return $this->notFound('Teacher profile not found for this account.');
        }

        $query = AssignmentSubmission::whereHas(
            'assignment',
            fn ($q) => $q->where('teacher_id', $teacher->id)
        )->with(['assignment.course', 'student.user']);

        // status=submitted|late|reviewed|returned, or pending = awaiting a mark
        $status = $request->get('status');
        if ($status === 'pending') {
            $query->whereNull('marks');
        } elseif ($status) {
            $query->where('status', $status);
        }

        if ($request->filled('course_id')) {
            $query->whereHas('assignment', fn ($q) => $q->where('course_id', $request->course_id));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student.user', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
        }

        $paginated = $query->latest('submitted_at')
            ->paginate($this->perPage($request->get('per_page')));

        $base = AssignmentSubmission::whereHas(
            'assignment',
            fn ($q) => $q->where('teacher_id', $teacher->id)
        );

        $response = $this->paginated($paginated);
        $data = $response->getData(true);
        $data['meta']['counts'] = [
            'all' => (clone $base)->count(),
            'pending' => (clone $base)->whereNull('marks')->count(),
            'reviewed' => (clone $base)->where('status', 'reviewed')->count(),
        ];

        return response()->json($data);
    }

    /**
     * POST /api/online-learning/teacher/submissions/{submission}/evaluate
     */
    public function evaluateSubmission(Request $request, AssignmentSubmission $submission)
    {
        $teacher = $this->actingTeacher();
        if (! $teacher) {
            return $this->notFound('Teacher profile not found for this account.');
        }

        $submission->load('assignment');

        // A teacher may only grade submissions for assignments they own.
        if ((int) $submission->assignment->teacher_id !== (int) $teacher->id) {
            return $this->forbidden('You can only evaluate submissions for your own assignments.');
        }

        $maxMarks = (int) ($submission->assignment->max_marks ?: 100);

        $validated = $request->validate([
            'marks' => 'required|integer|min:0|max:' . $maxMarks,
            'teacher_feedback' => 'nullable|string|max:2000',
        ]);

        $submission->update([
            'marks' => $validated['marks'],
            'teacher_feedback' => $validated['teacher_feedback'] ?? null,
            'status' => 'reviewed',
            'evaluated_at' => now(),
            'evaluated_by' => Auth::id(),
        ]);

        return $this->ok(
            $submission->fresh(['assignment.course', 'student.user']),
            'Submission evaluated. ' . $validated['marks'] . '/' . $maxMarks . ' awarded.'
        );
    }

    /**
     * POST /api/online-learning/teacher/classes/{onlineClass}/complete
     *
     * Marks a class finished once it is over - the teacher-facing close-out action.
     */
    public function completeClass(OnlineClass $onlineClass)
    {
        $teacher = $this->actingTeacher();
        if (! $teacher || (int) $onlineClass->teacher_id !== (int) $teacher->id) {
            return $this->forbidden('You are not the host of this class.');
        }

        if ($onlineClass->status === 'cancelled') {
            return $this->fail('A cancelled class cannot be marked completed.', 422);
        }

        $onlineClass->update(['status' => 'completed']);

        return $this->ok($onlineClass->fresh(), 'Class marked as completed.');
    }
}
