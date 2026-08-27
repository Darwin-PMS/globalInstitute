<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained('class_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->enum('type', ['announcement', 'message'])->default('message');
            $table->timestamps();

            $table->index(['class_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_messages');
    }
};
