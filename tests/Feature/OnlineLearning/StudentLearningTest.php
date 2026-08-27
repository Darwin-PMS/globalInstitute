<?php

namespace Tests\Feature\OnlineLearning;

use App\Models\Recording;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Student screens: recordings, assignments (list + submit) and progress.
 */
class StudentLearningTest extends TestCase
{
    use RefreshDatabase;
    use LearningTestFixtures;

    public function test_recordings_only_include_published_ones_from_enrolled_courses(): void
    {
        $trainer = $this->makeTrainer();

        $mine = $this->makeCourse('My Course');
        $myBatch = $this->makeBatch($mine, $trainer);
        $myClass = $this->makeClass($mine, $myBatch, $trainer);

        $theirs = $this->makeCourse('Other Course');
        $theirBatch = $this->makeBatch($theirs, $trainer);
        $theirClass = $this->makeClass($theirs, $theirBatch, $trainer);

        $visible = Recording::create([
            'online_class_id' => $myClass->id,
            'title' => 'Published session',
            'recording_url' => 'https://example.test/published',
            'is_published' => true,
        ]);
        Recording::create([
            'online_class_id' => $myClass->id,
            'title' => 'Draft session',
            'recording_url' => 'https://example.test/draft',
            'is_published' => false,
        ]);
        Recording::create([
            'online_class_id' => $theirClass->id,
            'title' => 'Someone elses session',
            'recording_url' => 'https://example.test/other',
            'is_published' => true,
        ]);

        $studentUser = User::factory()->create();
        $this->enroll($this->makeStudent($studentUser), $mine, $myBatch);

        $response = $this->actingAs($studentUser)
            ->getJson('/api/online-learning/student/recordings');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $visible->id);
    }

    public function test_filtering_recordings_by_an_unenrolled_course_is_refused(): void
    {
        $trainer = $this->makeTrainer();
        $mine = $this->makeCourse('Mine');
        $theirs = $this->makeCourse('Theirs');

        $studentUser = User::factory()->create();
        $this->enroll($this->makeStudent($studentUser), $mine);

        $this->actingAs($studentUser)
            ->getJson("/api/online-learning/student/recordings?course_id={$theirs->id}")
            ->assertForbidden();
    }

    public function test_assignments_list_carries_filter_counts(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $this->makeAssignment($course, $trainer, ['title' => 'Open task']);
        $this->makeAssignment($course, $trainer, [
            'title' => 'Past task',
            'due_date' => now()->subWeek(),
        ]);
        $this->makeAssignment($course, $trainer, ['title' => 'Hidden draft', 'status' => 'draft']);

        $studentUser = User::factory()->create();
        $this->enroll($this->makeStudent($studentUser), $course);

        $response = $this->actingAs($studentUser)
            ->getJson('/api/online-learning/student/assignments');

        $response->assertOk()
            // Only the two published assignments are visible.
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.counts.all', 2)
            ->assertJsonPath('meta.counts.pending', 2)
            ->assertJsonPath('meta.counts.overdue', 1)
            ->assertJsonPath('meta.counts.submitted', 0);
    }

    public function test_submitting_an_assignment_records_the_enrollment_and_marks_on_time(): void
    {
        Storage::fake('public');
        Carbon::setTestNow(Carbon::parse('2026-08-22 10:00:00'));

        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $assignment = $this->makeAssignment($course, $trainer, ['due_date' => '2026-08-30 23:59:00']);

        $studentUser = User::factory()->create();
        $student = $this->makeStudent($studentUser);
        $enrollment = $this->enroll($student, $course);

        $response = $this->actingAs($studentUser)->postJson(
            "/api/online-learning/student/assignments/{$assignment->id}/submit",
            ['submission_text' => 'Here is my floor plan write-up.']
        );

        $response->assertOk()->assertJsonPath('success', true);

        // enrollment_id is NOT NULL on the table; resolving it is the whole point.
        $this->assertDatabaseHas('assignment_submissions', [
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'status' => 'submitted',
        ]);

        Carbon::setTestNow();
    }

    public function test_submitting_after_the_due_date_is_marked_late_by_the_server(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-05 10:00:00'));

        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $assignment = $this->makeAssignment($course, $trainer, ['due_date' => '2026-08-30 23:59:00']);

        $studentUser = User::factory()->create();
        $student = $this->makeStudent($studentUser);
        $this->enroll($student, $course);

        $this->actingAs($studentUser)->postJson(
            "/api/online-learning/student/assignments/{$assignment->id}/submit",
            ['submission_text' => 'Sorry this is late.']
        )->assertOk();

        $this->assertDatabaseHas('assignment_submissions', [
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'status' => 'late',
        ]);

        Carbon::setTestNow();
    }

    public function test_a_student_without_an_enrollment_cannot_submit(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $assignment = $this->makeAssignment($course, $trainer);

        $studentUser = User::factory()->create();
        $this->makeStudent($studentUser); // not enrolled

        $this->actingAs($studentUser)->postJson(
            "/api/online-learning/student/assignments/{$assignment->id}/submit",
            ['submission_text' => 'Let me in.']
        )->assertForbidden();

        $this->assertDatabaseCount('assignment_submissions', 0);
    }

    public function test_a_draft_assignment_cannot_be_submitted_to(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $assignment = $this->makeAssignment($course, $trainer, ['status' => 'draft']);

        $studentUser = User::factory()->create();
        $this->enroll($this->makeStudent($studentUser), $course);

        $this->actingAs($studentUser)->postJson(
            "/api/online-learning/student/assignments/{$assignment->id}/submit",
            ['submission_text' => 'Too early.']
        )->assertStatus(422);
    }

    public function test_submission_requires_text_or_a_file(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $assignment = $this->makeAssignment($course, $trainer);

        $studentUser = User::factory()->create();
        $this->enroll($this->makeStudent($studentUser), $course);

        $this->actingAs($studentUser)->postJson(
            "/api/online-learning/student/assignments/{$assignment->id}/submit",
            []
        )->assertStatus(422)->assertJsonValidationErrors('submission_text');
    }

    public function test_a_file_only_submission_is_accepted(): void
    {
        Storage::fake('public');

        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $assignment = $this->makeAssignment($course, $trainer);

        $studentUser = User::factory()->create();
        $student = $this->makeStudent($studentUser);
        $this->enroll($student, $course);

        $this->actingAs($studentUser)->post(
            "/api/online-learning/student/assignments/{$assignment->id}/submit",
            ['file' => UploadedFile::fake()->create('plan.pdf', 120, 'application/pdf')],
            ['Accept' => 'application/json']
        )->assertOk();

        $path = \App\Models\AssignmentSubmission::where('assignment_id', $assignment->id)->value('file_path');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_progress_reports_per_course_and_overall_figures(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse('AutoCAD');

        $studentUser = User::factory()->create();
        $student = $this->makeStudent($studentUser);
        $this->enroll($student, $course);

        $response = $this->actingAs($studentUser)
            ->getJson('/api/online-learning/student/progress');

        $response->assertOk()
            ->assertJsonPath('data.overall.courses_count', 1)
            ->assertJsonPath('data.courses.0.course_title', 'AutoCAD')
            ->assertJsonStructure([
                'data' => [
                    'courses' => [['course_id', 'progress_percent', 'attendance_rate', 'total_lessons']],
                    'overall' => ['progress_percent', 'average_attendance', 'total_lessons'],
                ],
            ]);
    }

    public function test_a_user_without_a_student_profile_gets_a_clean_404(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/online-learning/student/progress')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
