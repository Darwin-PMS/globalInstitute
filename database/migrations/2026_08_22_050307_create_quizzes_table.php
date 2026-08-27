<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses');
            $table->foreignId('module_id')->nullable()->constrained('course_modules');
            $table->foreignId('lesson_id')->nullable()->constrained('lessons');
            $table->foreignId('teacher_id')->constrained('trainers');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->integer('time_limit')->nullable()->comment('minutes');
            $table->integer('max_attempts')->default(1);
            $table->integer('pass_marks')->default(50);
            $table->integer('total_marks')->default(100);
            $table->boolean('is_published')->default(false);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
