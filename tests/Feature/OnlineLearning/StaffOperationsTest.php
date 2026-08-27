<?php

namespace Tests\Feature\OnlineLearning;

use App\Models\AssignmentSubmission;
use App\Models\Recording;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Teacher and admin operations: attendance, grading, publishing and the
 * dashboard summary.
 */
class StaffOperationsTest extends TestCase
{
    use RefreshDatabase;
    use LearningTestFixtures;

    /* ------------------------------------------------------------ attendance */

    public function test_attendance_can_be_marked_in_bulk(): void
    {
        $teacherUser = User::factory()->create();
        $trainer = $this->makeTrainer($teacherUser);
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);
        $class = $this->makeClass($course, $batch, $trainer);

        $studentA = $this->makeStudent();
        $studentB = $this->makeStudent();
        $this->enroll($studentA, $course, $batch);
        $this->enroll($studentB, $course, $batch);

        $this->actingAs($teacherUser)->postJson(
            "/api/online-learning/classes/{$class->id}/attendance",
            ['attendance' => [
                ['student_id' => $studentA->id, 'status' => 'present', 'notes' => 'On time'],
                ['student_id' => $studentB->id, 'status' => 'late', 'notes' => null],
            ]]
        )->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('class_attendance', [
            'online_class_id' => $class->id, 'student_id' => $studentA->id, 'status' => 'present',
        ]);
        $this->assertDatabaseHas('class_attendance', [
            'online_class_id' => $class->id, 'student_id' => $studentB->id, 'status' => 'late',
        ]);
    }

    /**
     * Regression guard: the UI used to offer "excused", which the
     * class_attendance status enum does not allow.
     */
    public function test_a_status_outside_the_column_enum_is_rejected(): void
    {
        $teacherUser = User::factory()->create();
        $trainer = $this->makeTrainer($teacherUser);
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);
        $class = $this->makeClass($course, $batch, $trainer);
        $student = $this->makeStudent();
        $this->enroll($student, $course, $batch);

        $this->actingAs($teacherUser)->postJson(
            "/api/online-learning/classes/{$class->id}/attendance",
            ['attendance' => [['student_id' => $student->id, 'status' => 'excused']]]
        )->assertStatus(422)->assertJsonValidationErrors('attendance.0.status');

        $this->assertDatabaseCount('class_attendance', 0);
    }

    public function test_attendance_roster_lists_enrolled_students_with_their_code(): void
    {
        $teacherUser = User::factory()->create();
        $trainer = $this->makeTrainer($teacherUser);
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);
        $class = $this->makeClass($course, $batch, $trainer);

        $student = $this->makeStudent();
        $this->enroll($student, $course, $batch);

        $response = $this->actingAs($teacherUser)
            ->getJson("/api/online-learning/classes/{$class->id}/attendance");

        $response->assertOk()
            ->assertJsonPath('data.students.0.student_id', $student->id)
            // student_code reads the students.student_id column, not a
            // non-existent student_code column.
            ->assertJsonPath('data.students.0.student_code', $student->student_id)
            ->assertJsonPath('data.students.0.status', 'absent');
    }

    /* --------------------------------------------------------------- grading */

    public function test_teacher_can_grade_their_own_assignment_submission(): void
    {
        $teacherUser = User::factory()->create();
        $trainer = $this->makeTrainer($teacherUser);
        $course = $this->makeCourse();
        $assignment = $this->makeAssignment($course, $trainer, ['max_marks' => 50]);

        $student = $this->makeStudent();
        $enrollment = $this->enroll($student, $course);

        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'submission_text' => 'My answer.',
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $this->actingAs($teacherUser)->postJson(
            "/api/online-learning/teacher/submissions/{$submission->id}/evaluate",
            ['marks' => 42, 'teacher_feedback' => 'Solid work.']
        )->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('assignment_submissions', [
            'id' => $submission->id,
            'marks' => 42,
            'status' => 'reviewed',
            'evaluated_by' => $teacherUser->id,
        ]);
    }

    public function test_marks_above_the_maximum_are_rejected(): void
    {
        $teacherUser = User::factory()->create();
        $trainer = $this->makeTrainer($teacherUser);
        $course = $this->makeCourse();
        $assignment = $this->makeAssignment($course, $trainer, ['max_marks' => 50]);

        $student = $this->makeStudent();
        $enrollment = $this->enroll($student, $course);

        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $this->actingAs($teacherUser)->postJson(
            "/api/online-learning/teacher/submissions/{$submission->id}/evaluate",
            ['marks' => 80]
        )->assertStatus(422)->assertJsonValidationErrors('marks');
    }

    public function test_a_teacher_cannot_grade_another_teachers_submission(): void
    {
        $intruderUser = User::factory()->create();
        $this->makeTrainer($intruderUser, 'Intruder');

        $owner = $this->makeTrainer(null, 'Owner');
        $course = $this->makeCourse();
        $assignment = $this->makeAssignment($course, $owner);

        $student = $this->makeStudent();
        $enrollment = $this->enroll($student, $course);

        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $this->actingAs($intruderUser)->postJson(
            "/api/online-learning/teacher/submissions/{$submission->id}/evaluate",
            ['marks' => 10]
        )->assertForbidden();

        $this->assertNull($submission->fresh()->marks);
    }

    public function test_teacher_submissions_queue_is_scoped_to_their_assignments(): void
    {
        $teacherUser = User::factory()->create();
        $trainer = $this->makeTrainer($teacherUser);
        $otherTrainer = $this->makeTrainer(null, 'Other');
        $course = $this->makeCourse();

        $mine = $this->makeAssignment($course, $trainer, ['title' => 'Mine']);
        $theirs = $this->makeAssignment($course, $otherTrainer, ['title' => 'Theirs']);

        $student = $this->makeStudent();
        $enrollment = $this->enroll($student, $course);

        foreach ([$mine, $theirs] as $assignment) {
            AssignmentSubmission::create([
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'submitted_at' => now(),
                'status' => 'submitted',
            ]);
        }

        $this->actingAs($teacherUser)
            ->getJson('/api/online-learning/teacher/submissions')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.counts.pending', 1);
    }

    public function test_teacher_can_complete_only_their_own_class(): void
    {
        $teacherUser = User::factory()->create();
        $trainer = $this->makeTrainer($teacherUser);
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);
        $mine = $this->makeClass($course, $batch, $trainer);

        $this->actingAs($teacherUser)
            ->postJson("/api/online-learning/teacher/classes/{$mine->id}/complete")
            ->assertOk();
        $this->assertSame('completed', $mine->fresh()->status);

        $otherTrainer = $this->makeTrainer(null, 'Other');
        $otherBatch = $this->makeBatch($course, $otherTrainer);
        $theirs = $this->makeClass($course, $otherBatch, $otherTrainer);

        $this->actingAs($teacherUser)
            ->postJson("/api/online-learning/teacher/classes/{$theirs->id}/complete")
            ->assertForbidden();
        $this->assertSame('scheduled', $theirs->fresh()->status);
    }

    /* ----------------------------------------------------------------- admin */

    public function test_admin_summary_counts_across_the_whole_table(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);

        $this->makeClass($course, $batch, $trainer, ['status' => 'live']);
        $this->makeClass($course, $batch, $trainer, ['status' => 'completed']);
        $this->makeClass($course, $batch, $trainer, ['status' => 'cancelled']);
        $this->makeClass($course, $batch, $trainer, [
            'status' => 'scheduled',
            'class_date' => now()->addWeek()->toDateString(),
        ]);

        $this->actingAs($this->makeAdmin())
            ->getJson('/api/online-learning/admin/summary')
            ->assertOk()
            ->assertJsonPath('data.total_classes', 4)
            ->assertJsonPath('data.live_now', 1)
            ->assertJsonPath('data.today.total', 3)
            ->assertJsonPath('data.today.completed', 1)
            ->assertJsonPath('data.today.cancelled', 1)
            ->assertJsonPath('data.upcoming', 1);
    }

    public function test_attendance_log_can_isolate_unmarked_classes(): void
    {
        $teacherUser = User::factory()->create();
        $trainer = $this->makeTrainer($teacherUser);
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);

        $marked = $this->makeClass($course, $batch, $trainer, ['title' => 'Marked']);
        $this->makeClass($course, $batch, $trainer, ['title' => 'Unmarked']);

        $student = $this->makeStudent();
        $this->enroll($student, $course, $batch);

        $this->actingAs($teacherUser)->postJson(
            "/api/online-learning/classes/{$marked->id}/attendance",
            ['attendance' => [['student_id' => $student->id, 'status' => 'present']]]
        )->assertOk();

        $response = $this->actingAs($this->makeAdmin())
            ->getJson('/api/online-learning/admin/attendance?marked=no');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Unmarked');
    }

    public function test_admin_can_toggle_a_recording_between_published_and_not(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);
        $class = $this->makeClass($course, $batch, $trainer);

        $recording = Recording::create([
            'online_class_id' => $class->id,
            'title' => 'Session one',
            'recording_url' => 'https://example.test/one',
            'is_published' => false,
        ]);

        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->postJson("/api/online-learning/admin/recordings/{$recording->id}/toggle-publish")
            ->assertOk();
        $this->assertTrue($recording->fresh()->is_published);

        $this->actingAs($admin)
            ->postJson("/api/online-learning/admin/recordings/{$recording->id}/toggle-publish")
            ->assertOk();
        $this->assertFalse($recording->fresh()->is_published);
    }

    public function test_non_admins_cannot_reach_the_admin_endpoints(): void
    {
        $teacherUser = User::factory()->create();
        $this->makeTrainer($teacherUser);

        // AdminMiddleware guards the group; a teacher must not get through.
        $response = $this->actingAs($teacherUser)->getJson('/api/online-learning/admin/summary');
        $this->assertContains($response->status(), [302, 403]);
    }
}
