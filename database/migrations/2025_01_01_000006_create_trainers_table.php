<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('designation')->nullable();
            $table->string('qualification')->nullable();
            $table->string('experience')->nullable();
            $table->string('specialization')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo')->nullable();
            $table->string('status')->default('active')->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainers');
    }
};
