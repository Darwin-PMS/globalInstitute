<?php

namespace Tests\Feature\OnlineLearning;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Authorisation and payload-shaping around a single class.
 *
 * These cover §48 (no front-end-only access control): the endpoints must decide
 * for themselves who the caller is and what they may see.
 */
class ClassAccessTest extends TestCase
{
    use RefreshDatabase;
    use LearningTestFixtures;

    public function test_enrolled_student_can_view_a_class_but_not_its_meeting_url(): void
    {
        $course = $this->makeCourse();
        $trainer = $this->makeTrainer();
        $batch = $this->makeBatch($course, $trainer);
        $class = $this->makeClass($course, $batch, $trainer);

        $studentUser = User::factory()->create();
        $student = $this->makeStudent($studentUser);
        $this->enroll($student, $course, $batch);

        $response = $this->actingAs($studentUser)
            ->getJson("/api/online-learning/classes/{$class->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.class.id', $class->id)
            ->assertJsonPath('data.role', 'student');

        // The link is only handed over by the join endpoint, after it authorises.
        $response->assertJsonMissingPath('data.meeting_url');
    }

    public function test_student_not_enrolled_is_forbidden(): void
    {
        $course = $this->makeCourse();
        $otherCourse = $this->makeCourse('Revit Architecture');
        $trainer = $this->makeTrainer();
        $batch = $this->makeBatch($course, $trainer);
        $class = $this->makeClass($course, $batch, $trainer);

        $studentUser = User::factory()->create();
        $student = $this->makeStudent($studentUser);
        // Enrolled in a different course entirely.
        $this->enroll($student, $otherCourse);

        $this->actingAs($studentUser)
            ->getJson("/api/online-learning/classes/{$class->id}")
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_host_teacher_sees_the_meeting_url_and_attendance_stats(): void
    {
        $teacherUser = User::factory()->create();
        $trainer = $this->makeTrainer($teacherUser);
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);
        $class = $this->makeClass($course, $batch, $trainer);

        $this->actingAs($teacherUser)
            ->getJson("/api/online-learning/classes/{$class->id}")
            ->assertOk()
            ->assertJsonPath('data.role', 'teacher')
            ->assertJsonPath('data.meeting_url', $class->meeting_url)
            ->assertJsonStructure(['data' => ['attendance_stats' => ['total', 'present', 'late', 'absent']]]);
    }

    public function test_teacher_cannot_view_another_teachers_class(): void
    {
        $teacherUser = User::factory()->create();
        $this->makeTrainer($teacherUser, 'Own Trainer');

        $otherTrainer = $this->makeTrainer(null, 'Someone Else');
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $otherTrainer);
        $class = $this->makeClass($course, $batch, $otherTrainer);

        $this->actingAs($teacherUser)
            ->getJson("/api/online-learning/classes/{$class->id}")
            ->assertForbidden();
    }

    public function test_admin_can_view_any_class(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);
        $class = $this->makeClass($course, $batch, $trainer);

        $this->actingAs($this->makeAdmin())
            ->getJson("/api/online-learning/classes/{$class->id}")
            ->assertOk()
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonPath('data.meeting_url', $class->meeting_url);
    }

    public function test_guest_is_rejected(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);
        $class = $this->makeClass($course, $batch, $trainer);

        $this->getJson("/api/online-learning/classes/{$class->id}")->assertUnauthorized();
    }

    public function test_calendar_is_scoped_to_the_students_own_courses(): void
    {
        $trainer = $this->makeTrainer();

        $mine = $this->makeCourse('My Course');
        $myBatch = $this->makeBatch($mine, $trainer);
        $myClass = $this->makeClass($mine, $myBatch, $trainer, ['title' => 'Mine']);

        $theirs = $this->makeCourse('Someone Elses Course');
        $theirBatch = $this->makeBatch($theirs, $trainer);
        $this->makeClass($theirs, $theirBatch, $trainer, ['title' => 'Theirs']);

        $studentUser = User::factory()->create();
        $student = $this->makeStudent($studentUser);
        $this->enroll($student, $mine, $myBatch);

        $response = $this->actingAs($studentUser)->getJson(
            '/api/online-learning/calendar?from=' . now()->subWeek()->toDateString()
            . '&to=' . now()->addWeek()->toDateString()
        );

        $response->assertOk()->assertJsonPath('meta.count', 1);
        $this->assertSame($myClass->id, $response->json('data.0.id'));
    }

    public function test_calendar_requires_a_valid_range(): void
    {
        $this->actingAs($this->makeAdmin())
            ->getJson('/api/online-learning/calendar?from=2026-09-01&to=2026-08-01')
            ->assertStatus(422);
    }
}
