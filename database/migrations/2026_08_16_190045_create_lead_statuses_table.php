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
        Schema::create('lead_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // NEW ENQUIRY, CONTACTED, QUALIFIED, COUNSELLING, COURSE SELECTED, FOLLOW-UP, DEMO / VISIT, ADMISSION READY, ADMITTED, STUDENT, NOT INTERESTED, LOST
            $table->string('color')->default('#6b7280'); // For UI display
            $table->integer('sort_order')->default(0); // For ordering in pipeline
            $table->boolean('is_active')->default(true);
            $table->boolean('is_closed')->default(false); // For LOST, NOT INTERESTED, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_statuses');
    }
};
