<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('course_categories')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_description');
            $table->longText('description');
            $table->text('overview')->nullable();
            $table->text('eligibility')->nullable();
            $table->string('duration')->nullable();
            $table->decimal('fees', 10, 2)->nullable();
            $table->text('batch_information')->nullable();
            $table->json('skills')->nullable();
            $table->text('career_information')->nullable();
            $table->string('image')->nullable();
            $table->boolean('featured')->default(false);
            $table->string('status')->default('published')->index();
            $table->integer('sort_order')->default(0)->index();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
