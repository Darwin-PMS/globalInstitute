<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('course_modules')->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('teacher_id')->constrained('trainers');
            $table->enum('class_type', ['online', 'hybrid', 'recorded'])->default('online');
            $table->enum('access_policy', ['enrolled', 'public', 'free_preview'])->default('enrolled');
            $table->enum('status', ['active', 'archived', 'draft'])->default('draft');
            $table->string('course_provider')->nullable()->comment('Which system provides the course');
            $table->string('external_course_id')->nullable()->comment('ID in external system');
            $table->unsignedBigInteger('tenant_id')->nullable()->comment('Multi-tenant support');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_classes');
    }
};
