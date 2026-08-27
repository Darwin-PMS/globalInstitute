<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_class_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('live_class_id')->nullable()->constrained('online_classes')->nullOnDelete();
            $table->string('egress_id')->unique()->comment('LiveKit egress ID');
            $table->string('room_name');
            $table->enum('status', ['starting', 'processing', 'ready', 'failed', 'deleted'])->default('starting');
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->text('file_url')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->string('storage_provider')->default('local');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->enum('visibility', ['private', 'students', 'public'])->default('students');
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('view_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['class_room_id', 'status']);
            $table->index(['status', 'is_published']);
            $table->index('egress_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_class_recordings');
    }
};
