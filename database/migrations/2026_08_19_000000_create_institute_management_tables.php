<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =============================================
        // 1. ENHANCE USERS TABLE
        // =============================================
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->after('is_admin'); // super_admin, admin, counsellor, fee_manager, academic_manager, teacher, viewer
            $table->string('phone')->nullable()->after('role');
            $table->string('avatar')->nullable()->after('phone');
        });

        // =============================================
        // 2. ENHANCE TRAINERS TABLE (rename to teachers conceptually)
        // =============================================
        Schema::table('trainers', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('name');
            $table->string('email')->nullable()->after('phone');
            $table->string('employment_type')->default('full_time')->after('experience'); // full_time, part_time, visiting, guest
            $table->date('joining_date')->nullable()->after('employment_type');
            $table->text('notes')->nullable()->after('bio');
        });

        // =============================================
        // 3. ROOMS
        // =============================================
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('room_number')->nullable();
            $table->integer('capacity')->default(30);
            $table->string('type')->default('classroom'); // classroom, computer_lab, cad_lab, cnc_lab, workshop, other
            $table->string('status')->default('active'); // active, inactive, maintenance
            $table->timestamps();
        });

        // =============================================
        // 4. COURSE MODULES
        // =============================================
        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('duration')->nullable();
            $table->integer('sequence')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // =============================================
        // 5. FEE STRUCTURES
        // =============================================
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
        });

        // =============================================
        // 6. FEE STRUCTURE ITEMS
        // =============================================
        Schema::create('fee_structure_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained()->onDelete('cascade');
            $table->string('fee_type'); // registration, tuition, material, examination, certificate, other
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->boolean('mandatory')->default(true);
            $table->integer('sequence')->default(0);
            $table->timestamps();
        });

        // =============================================
        // 7. ENHANCE BATCHES TABLE
        // =============================================
        Schema::table('batches', function (Blueprint $table) {
            $table->string('batch_code')->nullable()->after('batch_name');
            $table->foreignId('room_id')->nullable()->after('trainer_id')->constrained()->nullOnDelete();
            $table->text('notes')->nullable()->after('status');
            $table->integer('current_strength')->default(0)->after('capacity');
        });

        // =============================================
        // 8. BATCH SCHEDULES
        // =============================================
        Schema::create('batch_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->onDelete('cascade');
            $table->string('day'); // monday, tuesday, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('trainers')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // =============================================
        // 9. TEACHER AVAILABILITY
        // =============================================
        Schema::create('teacher_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('trainers')->onDelete('cascade');
            $table->string('day');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('available')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // =============================================
        // 10. TEACHER BATCH ASSIGNMENTS
        // =============================================
        Schema::create('teacher_batch_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('trainers')->onDelete('cascade');
            $table->foreignId('batch_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_module_id')->nullable()->constrained()->nullOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('assignment_type')->default('primary'); // primary, assistant, guest, substitute
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // =============================================
        // 11. STUDENTS (dedicated table linked to users)
        // =============================================
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique(); // GIS-2026-00001
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('whatsapp')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pin_code')->nullable();
            $table->string('qualification')->nullable();
            $table->string('institution')->nullable();
            $table->string('passing_year')->nullable();
            $table->string('experience')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_relationship')->nullable();
            $table->string('emergency_contact_number')->nullable();
            $table->string('photo')->nullable();
            $table->string('id_proof')->nullable();
            $table->string('qualification_proof')->nullable();
            $table->json('other_documents')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        // =============================================
        // 12. STUDENT DOCUMENTS
        // =============================================
        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->string('document_type'); // photo, id_proof, qualification_proof, other
            $table->string('document_name');
            $table->string('file_path');
            $table->string('status')->default('pending'); // pending, verified, rejected
            $table->timestamps();
        });

        // =============================================
        // 13. ADMISSIONS (re-create)
        // =============================================
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->string('admission_number')->unique();
            $table->foreignId('enquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('admission_date');
            $table->decimal('fee', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('discount_type')->default('fixed'); // fixed, percentage
            $table->text('discount_reason')->nullable();
            $table->decimal('final_fee', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2);
            $table->string('payment_status')->default('pending'); // pending, partial, paid, overdue
            $table->string('admission_status')->default('pending'); // pending, confirmed, active, completed, dropped, transferred, cancelled
            $table->foreignId('counsellor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('academic_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // =============================================
        // 14. ENROLLMENTS
        // =============================================
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('enrollment_number')->unique();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->date('enrollment_date');
            $table->date('start_date');
            $table->date('expected_end_date')->nullable();
            $table->foreignId('fee_structure_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total_fee', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('discount_type')->default('fixed');
            $table->text('discount_reason')->nullable();
            $table->decimal('final_fee', 12, 2);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('fee_status')->default('unpaid'); // unpaid, partially_paid, paid, overdue
            $table->string('status')->default('active'); // active, completed, dropped, transferred, on_hold, cancelled
            $table->timestamps();
            $table->softDeletes();
        });

        // =============================================
        // 15. FEE PLANS
        // =============================================
        Schema::create('fee_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->onDelete('cascade');
            $table->string('plan_name'); // Full Payment, 2 Installments, 3 Installments, Custom
            $table->decimal('total_amount', 12, 2);
            $table->integer('total_installments')->default(1);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // =============================================
        // 16. FEE INSTALLMENTS
        // =============================================
        Schema::create('fee_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_plan_id')->constrained()->onDelete('cascade');
            $table->integer('installment_number');
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('status')->default('pending'); // pending, partially_paid, paid, overdue, waived
            $table->timestamps();
        });

        // =============================================
        // 17. PAYMENTS
        // =============================================
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id')->unique(); // PAY-2026-000001
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fee_installment_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method'); // cash, upi, gpay, phonepe, paytm, bank_transfer, card, net_banking, gateway, other
            $table->string('transaction_id')->nullable();
            $table->string('gateway')->nullable(); // razorpay, cashfree, payu, other
            $table->string('reference_number')->nullable();
            $table->string('upi_id')->nullable();
            $table->string('status')->default('initiated'); // initiated, pending, successful, failed, cancelled, refunded
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // =============================================
        // 18. PAYMENT TRANSACTIONS (gateway logs)
        // =============================================
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->string('gateway_transaction_id')->nullable();
            $table->string('gateway_order_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->string('status'); // initiated, pending, success, failed
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // =============================================
        // 19. RECEIPTS
        // =============================================
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique(); // REC-2026-000001
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('previous_balance', 12, 2);
            $table->decimal('remaining_balance', 12, 2);
            $table->string('payment_method');
            $table->string('transaction_id')->nullable();
            $table->date('payment_date');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // =============================================
        // 20. REFUNDS
        // =============================================
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->text('reason')->nullable();
            $table->date('refund_date');
            $table->string('method')->default('original'); // original, bank_transfer, cash, upi
            $table->string('reference_number')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('requested'); // requested, approved, processed, rejected
            $table->timestamps();
        });

        // =============================================
        // 21. FEE ADJUSTMENTS
        // =============================================
        Schema::create('fee_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->onDelete('cascade');
            $table->string('type'); // scholarship, discount, waiver, correction, other
            $table->decimal('amount', 12, 2);
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('adjustment_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // =============================================
        // 22. FEE REMINDERS
        // =============================================
        Schema::create('fee_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->onDelete('cascade');
            $table->foreignId('fee_installment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reminder_type'); // upcoming, due_today, overdue, final
            $table->text('message');
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_reminders');
        Schema::dropIfExists('fee_adjustments');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('fee_installments');
        Schema::dropIfExists('fee_plans');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('admissions');
        Schema::dropIfExists('student_documents');
        Schema::dropIfExists('students');
        Schema::dropIfExists('teacher_batch_assignments');
        Schema::dropIfExists('teacher_availability');
        Schema::dropIfExists('batch_schedules');
        Schema::dropIfExists('fee_structure_items');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('rooms');

        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn(['batch_code', 'room_id', 'notes', 'current_strength']);
        });

        Schema::table('trainers', function (Blueprint $table) {
            $table->dropColumn(['phone', 'email', 'employment_type', 'joining_date', 'notes']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'avatar']);
        });
    }
};
