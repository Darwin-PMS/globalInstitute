<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('enrollment_id')->constrained('enrollments');
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->integer('progress_percentage')->default(0);
            $table->timestamps();

            $table->unique(['student_id', 'enrollment_id', 'lesson_id'], 'slp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_lesson_progress');
    }
};
