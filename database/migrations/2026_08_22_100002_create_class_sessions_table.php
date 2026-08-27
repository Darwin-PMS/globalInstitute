<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_class_id')->constrained('live_classes')->cascadeOnDelete();
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone')->default('Asia/Kolkata');
            $table->enum('meeting_provider', ['google_meet', 'zoom', 'teams', 'other'])->default('other');
            $table->string('meeting_reference')->nullable()->comment('Provider meeting ID');
            $table->string('join_url')->nullable();
            $table->string('host_url')->nullable();
            $table->string('meeting_id')->nullable();
            $table->string('meeting_password')->nullable();
            $table->enum('status', ['scheduled', 'starting_soon', 'live', 'completed', 'cancelled', 'postponed'])->default('scheduled');
            $table->enum('recording_status', ['not_available', 'processing', 'available', 'failed', 'archived'])->default('not_available');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['live_class_id', 'session_date']);
            $table->index(['session_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
