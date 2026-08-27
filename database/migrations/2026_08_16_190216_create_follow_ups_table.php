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
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquiry_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->constrained('users')->onDelete('cascade');
            $table->date('follow_up_date');
            $table->time('follow_up_time')->nullable();
            $table->string('follow_up_type'); // Call, WhatsApp, SMS, Email, Institute Visit, Demo, Counselling
            $table->string('reminder')->nullable(); // For setting reminders
            $table->text('notes')->nullable();
            $table->string('outcome')->nullable(); // Interested, Call Back Later, Need More Information, Fee Concern, Family Discussion, Course Changed, Admission Confirmed, Not Interested, No Response
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
