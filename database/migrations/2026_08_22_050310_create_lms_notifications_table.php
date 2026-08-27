<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->enum('type', [
                'class_scheduled',
                'class_reminder',
                'class_starting_soon',
                'class_rescheduled',
                'class_cancelled',
                'recording_available',
                'assignment_created',
                'assignment_due',
                'assignment_evaluated',
                'quiz_available',
                'general',
            ]);
            $table->json('data')->nullable();
            $table->dateTime('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_notifications');
    }
};
