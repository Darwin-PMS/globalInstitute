<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('counselling_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquiry_id')->constrained()->onDelete('cascade');
            $table->foreignId('counsellor_id')->constrained('users')->onDelete('cascade');
            $table->string('student_goal')->nullable();
            $table->string('recommended_course')->nullable();
            $table->string('alternative_course')->nullable();
            $table->string('career_objective')->nullable();
            $table->string('current_qualification')->nullable();
            $table->string('skill_level')->nullable(); // Beginner, Intermediate, Advanced
            $table->string('recommended_batch')->nullable();
            $table->text('counselling_notes')->nullable();
            $table->text('counsellor_recommendation')->nullable();
            $table->timestamp('session_date')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counselling_sessions');
    }
};
