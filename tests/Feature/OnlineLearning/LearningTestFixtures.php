<?php

namespace Tests\Feature\OnlineLearning;

use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\OnlineClass;
use App\Models\Student;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Minimal fixture builders for the Online Learning feature tests.
 *
 * The project has no model factories beyond UserFactory, so rows are created
 * explicitly here with only the columns the schema requires.
 */
trait LearningTestFixtures
{
    protected function makeAdmin(): User
    {
        // is_admin is the authoritative flag (see User::isAdministrator).
        return User::factory()->create(['is_admin' => true, 'role' => 'admin']);
    }

    protected function makeCourse(string $title = 'AutoCAD Professional'): Course
    {
        $categoryId = DB::table('course_categories')->insertGetId([
            'name' => 'CAD',
            'slug' => 'cad-' . uniqid(),
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Course::create([
            'category_id' => $categoryId,
            'title' => $title,
            'slug' => str()->slug($title) . '-' . uniqid(),
            'short_description' => 'Short description.',
            'description' => 'Full description.',
            'status' => 'published',
        ]);
    }

    protected function makeTrainer(?User $user = null, string $name = 'Priya Sharma'): Trainer
    {
        return Trainer::create([
            'user_id' => $user?->id,
            'name' => $name,
            'status' => 'active',
        ]);
    }

    protected function makeStudent(?User $user = null): Student
    {
        $user ??= User::factory()->create();

        return Student::create([
            'student_id' => 'GIS-' . uniqid(),
            'user_id' => $user->id,
            'status' => 'active',
        ]);
    }

    protected function makeBatch(Course $course, ?Trainer $trainer = null): Batch
    {
        return Batch::create([
            'course_id' => $course->id,
            'batch_name' => 'Morning Batch',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'days' => 'Monday,Wednesday,Friday',
            'capacity' => 30,
            'available_seats' => 10,
            'trainer_id' => $trainer?->id,
            'status' => 'active',
        ]);
    }

    protected function enroll(Student $student, Course $course, ?Batch $batch = null, string $status = 'active'): Enrollment
    {
        return Enrollment::create([
            'enrollment_number' => 'ENR-' . uniqid(),
            'student_id' => $student->id,
            'course_id' => $course->id,
            'batch_id' => $batch?->id,
            'enrollment_date' => now()->toDateString(),
            'start_date' => now()->toDateString(),
            'total_fee' => 20000,
            'final_fee' => 20000,
            'status' => $status,
        ]);
    }

    protected function makeClass(Course $course, Batch $batch, Trainer $trainer, array $overrides = []): OnlineClass
    {
        return OnlineClass::create(array_merge([
            'course_id' => $course->id,
            'batch_id' => $batch->id,
            'teacher_id' => $trainer->id,
            'title' => '2D Drafting Fundamentals',
            'class_date' => now()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'meeting_provider' => 'google_meet',
            'meeting_url' => 'https://meet.google.com/abc-defg-hij',
            'status' => 'scheduled',
            'is_attendance_required' => true,
        ], $overrides));
    }

    protected function makeAssignment(Course $course, Trainer $trainer, array $overrides = []): Assignment
    {
        return Assignment::create(array_merge([
            'course_id' => $course->id,
            'teacher_id' => $trainer->id,
            'title' => 'Draft a floor plan',
            'description' => 'Produce a dimensioned floor plan.',
            'due_date' => now()->addWeek(),
            'max_marks' => 100,
            'status' => 'published',
        ], $overrides));
    }
}
