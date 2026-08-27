<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_class_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('can_speak')->default(true);
            $table->boolean('can_use_camera')->default(true);
            $table->boolean('can_share_screen')->default(false);
            $table->boolean('can_chat')->default(true);
            $table->boolean('can_record')->default(false);
            $table->boolean('can_present')->default(false);
            $table->timestamps();

            $table->unique(['class_room_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_class_permissions');
    }
};
