<?php

namespace Tests\Feature\OnlineLearning;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Deep linking into the SPA shell (§55, §56) and the reschedule / cancel paths.
 */
class ShellAndSchedulingTest extends TestCase
{
    use RefreshDatabase;
    use LearningTestFixtures;

    /* ----------------------------------------------------------------- shell */

    public static function deepLinkProvider(): array
    {
        return [
            'student root' => ['/student/learning'],
            'student class detail' => ['/student/learning/classes/123'],
            'student course detail' => ['/student/learning/courses/45'],
            'student assignments' => ['/student/learning/assignments'],
            'student progress' => ['/student/learning/progress'],
        ];
    }

    /**
     * @dataProvider deepLinkProvider
     */
    public function test_a_student_deep_link_returns_the_application_shell(string $path): void
    {
        $studentUser = User::factory()->create();
        $this->makeStudent($studentUser);

        $response = $this->actingAs($studentUser)->get($path);

        // Every path under the prefix returns the same shell; the JS router
        // resolves the screen. A refresh must not bounce to the dashboard.
        $response->assertOk()
            ->assertSee('id="learning-content"', false)
            ->assertSee('window.OnlineLearningConfig', false);
    }

    public function test_the_shell_publishes_the_role_resolved_on_the_server(): void
    {
        $teacherUser = User::factory()->create();
        $this->makeTrainer($teacherUser);

        $this->actingAs($teacherUser)
            ->get('/teacher/online-learning/classes')
            ->assertOk()
            ->assertSee('role: "teacher"', false);

        $studentUser = User::factory()->create();
        $this->makeStudent($studentUser);

        $this->actingAs($studentUser)
            ->get('/student/learning')
            ->assertOk()
            ->assertSee('role: "student"', false);
    }

    public function test_a_non_admin_cannot_open_the_admin_shell(): void
    {
        $studentUser = User::factory()->create();
        $this->makeStudent($studentUser);

        $response = $this->actingAs($studentUser)->get('/admin/online-learning/classes');

        // Refused, and - unlike before - the session survives the refusal.
        $response->assertRedirect();
        $this->assertTrue(auth()->check(), 'A refused admin request must not sign the user out.');
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get('/student/learning')->assertRedirect();
    }

    /* ------------------------------------------------------------ scheduling */

    /**
     * Regression guard: rescheduling called NotificationService::classRescheduled,
     * which did not exist - a 500 after the row had already been updated.
     */
    public function test_rescheduling_moves_the_class_and_notifies_without_erroring(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);
        $class = $this->makeClass($course, $batch, $trainer, [
            'class_date' => '2026-08-22',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $studentUser = User::factory()->create();
        $student = $this->makeStudent($studentUser);
        $this->enroll($student, $course, $batch);

        $this->actingAs($this->makeAdmin())->postJson(
            "/api/online-learning/classes/{$class->id}/reschedule",
            [
                'class_date' => '2026-08-25',
                'start_time' => '14:00',
                'end_time' => '16:00',
                'reason' => 'Instructor unavailable.',
            ]
        )->assertOk()->assertJsonPath('success', true);

        $fresh = $class->fresh();
        $this->assertSame('2026-08-25', $fresh->class_date->toDateString());
        $this->assertSame('postponed', $fresh->status);

        // The notification must be addressed to the student's USER id -
        // lms_notifications.user_id is a foreign key to users, and the handler
        // used to pass raw student ids straight through.
        $this->assertDatabaseHas('lms_notifications', [
            'user_id' => $studentUser->id,
            'type' => 'class_rescheduled',
        ]);
    }

    public function test_rescheduling_onto_a_conflicting_slot_is_rejected(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);

        // The instructor is already booked here.
        $this->makeClass($course, $batch, $trainer, [
            'title' => 'Existing booking',
            'class_date' => '2026-08-25',
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
        ]);

        $moving = $this->makeClass($course, $batch, $trainer, [
            'title' => 'Class being moved',
            'class_date' => '2026-08-22',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $response = $this->actingAs($this->makeAdmin())->postJson(
            "/api/online-learning/classes/{$moving->id}/reschedule",
            ['class_date' => '2026-08-25', 'start_time' => '14:00', 'end_time' => '16:00']
        );

        $response->assertStatus(422)->assertJsonPath('success', false);

        // The clash must leave the original schedule untouched.
        $this->assertSame('2026-08-22', $moving->fresh()->class_date->toDateString());
    }

    public function test_cancelling_requires_a_reason_and_stores_it(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);
        $class = $this->makeClass($course, $batch, $trainer);

        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->postJson("/api/online-learning/classes/{$class->id}/cancel", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');

        $this->assertSame('scheduled', $class->fresh()->status);

        $this->actingAs($admin)->postJson(
            "/api/online-learning/classes/{$class->id}/cancel",
            ['reason' => 'Instructor is unwell.']
        )->assertOk();

        $fresh = $class->fresh();
        $this->assertSame('cancelled', $fresh->status);
        $this->assertSame('Instructor is unwell.', $fresh->cancelled_reason);
    }

    public function test_creating_a_class_rejects_an_instructor_double_booking(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);

        $this->makeClass($course, $batch, $trainer, [
            'class_date' => '2026-09-01',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $this->actingAs($this->makeAdmin())->postJson('/api/admin/online-classes', [
            'course_id' => $course->id,
            'batch_id' => $batch->id,
            'teacher_id' => $trainer->id,
            'title' => 'Overlapping class',
            'class_date' => '2026-09-01',
            'start_time' => '10:30',
            'end_time' => '11:30',
            'meeting_provider' => 'google_meet',
            'status' => 'scheduled',
        ])->assertStatus(422)->assertJsonPath('success', false);
    }

    /**
     * Back-to-back sessions are not a clash. The old whereBetween predicate
     * matched on the shared boundary and refused a legitimate schedule.
     */
    public function test_a_back_to_back_class_is_allowed(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);

        $this->makeClass($course, $batch, $trainer, [
            'class_date' => '2026-09-01',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        // Starts exactly when the previous one ends.
        $this->actingAs($this->makeAdmin())->postJson('/api/admin/online-classes', [
            'course_id' => $course->id,
            'batch_id' => $batch->id,
            'teacher_id' => $trainer->id,
            'title' => 'Afternoon session',
            'class_date' => '2026-09-01',
            'start_time' => '12:00',
            'end_time' => '14:00',
            'meeting_provider' => 'google_meet',
            'status' => 'scheduled',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('online_classes', ['title' => 'Afternoon session']);
    }

    public function test_a_cancelled_class_does_not_block_the_slot(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);

        $this->makeClass($course, $batch, $trainer, [
            'class_date' => '2026-09-02',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'cancelled',
        ]);

        $this->actingAs($this->makeAdmin())->postJson('/api/admin/online-classes', [
            'course_id' => $course->id,
            'batch_id' => $batch->id,
            'teacher_id' => $trainer->id,
            'title' => 'Replacement session',
            'class_date' => '2026-09-02',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'meeting_provider' => 'google_meet',
            'status' => 'scheduled',
        ])->assertOk();
    }

    public function test_the_admin_class_list_uses_the_standard_envelope(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);
        $this->makeClass($course, $batch, $trainer);

        $this->actingAs($this->makeAdmin())
            ->getJson('/api/admin/online-classes')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }
}
