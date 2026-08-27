<?php

namespace Tests\Feature\OnlineLearning;

use App\Models\ClassAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The join path (§27): the server decides, records the attempt, and only then
 * hands over the meeting link.
 */
class JoinClassTest extends TestCase
{
    use RefreshDatabase;
    use LearningTestFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        // A fixed clock keeps the join-window assertions deterministic.
        Carbon::setTestNow(Carbon::parse('2026-08-22 09:55:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_student_inside_the_join_window_gets_the_url_and_is_marked_present(): void
    {
        $course = $this->makeCourse();
        $trainer = $this->makeTrainer();
        $batch = $this->makeBatch($course, $trainer);

        // Starts at 10:00; the configured window opens 15 minutes earlier.
        $class = $this->makeClass($course, $batch, $trainer, [
            'class_date' => '2026-08-22',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $studentUser = User::factory()->create();
        $student = $this->makeStudent($studentUser);
        $this->enroll($student, $course, $batch);

        $response = $this->actingAs($studentUser)
            ->postJson("/api/online-learning/student/classes/{$class->id}/join");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meeting_url', $class->meeting_url);

        // The attempt is recorded, not merely trusted from the client.
        $this->assertDatabaseHas('class_attendance', [
            'online_class_id' => $class->id,
            'student_id' => $student->id,
            'status' => 'present',
        ]);
    }

    public function test_student_before_the_join_window_is_refused_and_gets_no_url(): void
    {
        $course = $this->makeCourse();
        $trainer = $this->makeTrainer();
        $batch = $this->makeBatch($course, $trainer);

        // Two hours out - well before the 15-minute window.
        $class = $this->makeClass($course, $batch, $trainer, [
            'class_date' => '2026-08-22',
            'start_time' => '12:00:00',
            'end_time' => '14:00:00',
        ]);

        $studentUser = User::factory()->create();
        $student = $this->makeStudent($studentUser);
        $this->enroll($student, $course, $batch);

        $response = $this->actingAs($studentUser)
            ->postJson("/api/online-learning/student/classes/{$class->id}/join");

        $response->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonMissingPath('meeting_url');

        $this->assertDatabaseCount('class_attendance', 0);
    }

    public function test_student_not_enrolled_cannot_join(): void
    {
        $course = $this->makeCourse();
        $trainer = $this->makeTrainer();
        $batch = $this->makeBatch($course, $trainer);
        $class = $this->makeClass($course, $batch, $trainer, [
            'class_date' => '2026-08-22',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $studentUser = User::factory()->create();
        $this->makeStudent($studentUser); // no enrollment at all

        $this->actingAs($studentUser)
            ->postJson("/api/online-learning/student/classes/{$class->id}/join")
            ->assertForbidden()
            ->assertJsonMissingPath('meeting_url');
    }

    public function test_cancelled_class_cannot_be_joined(): void
    {
        $course = $this->makeCourse();
        $trainer = $this->makeTrainer();
        $batch = $this->makeBatch($course, $trainer);
        $class = $this->makeClass($course, $batch, $trainer, [
            'class_date' => '2026-08-22',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'cancelled',
        ]);

        $studentUser = User::factory()->create();
        $this->enroll($this->makeStudent($studentUser), $course, $batch);

        $this->actingAs($studentUser)
            ->postJson("/api/online-learning/student/classes/{$class->id}/join")
            ->assertForbidden();
    }

    public function test_host_teacher_joining_takes_the_class_live(): void
    {
        $teacherUser = User::factory()->create();
        $trainer = $this->makeTrainer($teacherUser);
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);
        $class = $this->makeClass($course, $batch, $trainer, [
            'class_date' => '2026-08-22',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($teacherUser)
            ->postJson("/api/online-learning/student/classes/{$class->id}/join")
            ->assertOk()
            ->assertJsonPath('meeting_url', $class->meeting_url);

        $this->assertSame('live', $class->fresh()->status);
    }

    /**
     * Regression guard for the join-window bug: start_time is a Carbon cast, so
     * building the timestamp by concatenation produced a doubled date and threw.
     */
    public function test_join_window_timestamps_are_built_without_a_doubled_date(): void
    {
        $course = $this->makeCourse();
        $trainer = $this->makeTrainer();
        $batch = $this->makeBatch($course, $trainer);
        $class = $this->makeClass($course, $batch, $trainer, [
            'class_date' => '2026-08-22',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $this->assertSame('2026-08-22 10:00:00', $class->startsAt()->toDateTimeString());
        $this->assertSame('2026-08-22 12:00:00', $class->endsAt()->toDateTimeString());
        $this->assertSame('10:00:00', $class->timeOnly('start_time'));
    }
}
