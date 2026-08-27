<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('online_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained();
            $table->foreignId('enrollment_id')->constrained();
            $table->enum('status', ['present', 'late', 'absent', 'partial'])->default('absent');
            $table->datetime('joined_at')->nullable();
            $table->datetime('left_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['online_class_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_attendance');
    }
};
