<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('course_modules')->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_id')->constrained('trainers');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('class_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration')->nullable();
            $table->enum('meeting_provider', ['google_meet', 'zoom', 'teams', 'other'])->default('google_meet');
            $table->string('meeting_url')->nullable();
            $table->string('meeting_id')->nullable();
            $table->string('meeting_password')->nullable();
            $table->string('recording_url')->nullable();
            $table->enum('status', ['scheduled', 'starting_soon', 'live', 'completed', 'cancelled', 'postponed'])->default('scheduled');
            $table->boolean('is_attendance_required')->default(true);
            $table->boolean('is_recording_available')->default(false);
            $table->text('cancelled_reason')->nullable();
            $table->datetime('rescheduled_from')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_classes');
    }
};
