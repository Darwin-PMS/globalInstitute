<?php

namespace App\Http\Controllers\Api\OnlineLearning\Concerns;

use App\Models\Enrollment;
use App\Models\OnlineClass;
use App\Models\Student;
use App\Models\Trainer;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the acting role from the session — never from client input.
 *
 * The `role` published in window.OnlineLearningConfig drives presentation only;
 * every endpoint re-derives the actor here so a student cannot request an
 * admin payload by changing a request parameter.
 */
trait ResolvesLearningActor
{
    protected function actingStudent(): ?Student
    {
        return Student::where('user_id', Auth::id())->first();
    }

    protected function actingTeacher(): ?Trainer
    {
        return Trainer::where('user_id', Auth::id())->first();
    }

    protected function isAdmin(): bool
    {
        return (bool) Auth::user()?->isAdministrator();
    }

    /**
     * Resolve the effective role for the authenticated user.
     */
    protected function actingRole(): string
    {
        if ($this->isAdmin()) {
            return 'admin';
        }

        return $this->actingTeacher() ? 'teacher' : 'student';
    }

    /**
     * Course ids the acting student holds an active enrollment for.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    protected function enrolledCourseIds(Student $student)
    {
        return Enrollment::where('student_id', $student->id)
            ->where('status', 'active')
            ->pluck('course_id');
    }

    /**
     * The acting student's active enrollment for a course, if any.
     */
    protected function enrollmentFor(Student $student, int $courseId): ?Enrollment
    {
        return Enrollment::where('student_id', $student->id)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Server-side read authorisation for a single class.
     * Admins see all; teachers see the classes they host; students see
     * classes belonging to a course they are actively enrolled in.
     */
    protected function canViewClass(OnlineClass $onlineClass): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $teacher = $this->actingTeacher();
        if ($teacher && (int) $onlineClass->teacher_id === (int) $teacher->id) {
            return true;
        }

        $student = $this->actingStudent();
        if ($student) {
            return $this->enrolledCourseIds($student)->contains($onlineClass->course_id);
        }

        return false;
    }
}
