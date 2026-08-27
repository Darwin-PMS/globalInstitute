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
        Schema::table('enquiries', function (Blueprint $table) {
            // Basic contact information
            $table->string('whatsapp_number')->nullable()->after('phone');
            $table->string('age')->nullable()->after('whatsapp_number');
            $table->string('gender')->nullable()->after('age');
            $table->string('current_occupation')->nullable()->after('qualification');
            $table->string('city')->nullable()->after('current_occupation');
            $table->string('preferred_batch')->nullable()->after('course_id');
            $table->string('utm_source')->nullable()->after('source');
            $table->string('utm_medium')->nullable()->after('utm_source');
            $table->string('utm_campaign')->nullable()->after('utm_medium');
            $table->string('utm_content')->nullable()->after('utm_campaign');
            $table->string('utm_term')->nullable()->after('utm_content');
            $table->string('landing_page')->nullable()->after('utm_term');
            $table->string('campaign')->nullable()->after('landing_page');
            
            // Lead qualification and scoring
            $table->string('career_goal')->nullable()->after('qualification');
            $table->string('previous_experience')->nullable()->after('career_goal');
            $table->string('preferred_duration')->nullable()->after('preferred_batch');
            $table->string('budget_range')->nullable()->after('preferred_duration');
            $table->string('expected_admission_date')->nullable()->after('budget_range');
            $table->string('lead_status')->default('new')->after('status')->index();
            $table->integer('lead_score')->default(0)->after('lead_status');
            $table->string('qualification_status')->default('cold')->after('lead_score')->index(); // HOT, WARM, COLD, NOT_QUALIFIED
            
            // Counselling information
            $table->foreignId('assigned_counsellor_id')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            $table->timestamp('last_contacted_at')->nullable()->after('assigned_counsellor_id');
            $table->timestamp('next_follow_up_at')->nullable()->after('last_contacted_at');
            $table->text('counselling_notes')->nullable()->after('next_follow_up_at');
            $table->text('recommended_course')->nullable()->after('counselling_notes');
            $table->text('alternative_course')->nullable()->after('recommended_course');
            $table->string('counsellor_recommendation')->nullable()->after('alternative_course');
            
            // Admission related fields (for snapshot)
            $table->string('selected_course')->nullable()->after('course_id');
            $table->string('selected_batch')->nullable()->after('selected_course');
            $table->decimal('selected_course_fee', 10, 2)->nullable()->after('selected_batch');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('selected_course_fee');
            $table->decimal('final_fee', 10, 2)->nullable()->after('discount_amount');
            $table->date('expected_admission_date_field')->nullable()->after('final_fee');
            $table->boolean('documents_received')->default(false)->after('expected_admission_date_field');
            $table->boolean('payment_received')->default(false)->after('documents_received');
            $table->boolean('admission_form_completed')->default(false)->after('payment_received');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_number', 'age', 'gender', 'current_occupation', 'city', 'preferred_batch', 
                              'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'landing_page', 'campaign',
                              'career_goal', 'previous_experience', 'preferred_duration', 'budget_range', 'expected_admission_date',
                              'lead_status', 'lead_score', 'qualification_status', 'assigned_counsellor_id', 'last_contacted_at', 
                              'next_follow_up_at', 'counselling_notes', 'recommended_course', 'alternative_course', 
                              'counsellor_recommendation', 'selected_course', 'selected_batch', 'selected_course_fee', 
                              'discount_amount', 'final_fee', 'expected_admission_date_field', 'documents_received', 
                              'payment_received', 'admission_form_completed']);
        });
    }
};
