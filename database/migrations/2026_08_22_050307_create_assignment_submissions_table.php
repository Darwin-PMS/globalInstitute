<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('enrollment_id')->constrained('enrollments');
            $table->text('submission_text')->nullable();
            $table->string('file_path')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->enum('status', ['pending', 'submitted', 'late', 'reviewed', 'returned'])->default('pending');
            $table->integer('marks')->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->dateTime('evaluated_at')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['assignment_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
