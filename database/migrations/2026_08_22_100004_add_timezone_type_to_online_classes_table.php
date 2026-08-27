<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_classes', function (Blueprint $table) {
            $table->string('timezone')->default('Asia/Kolkata')->after('created_by');
            $table->enum('class_type', ['online', 'hybrid', 'recorded'])->default('online')->after('status');
            $table->enum('access_policy', ['enrolled', 'public', 'free_preview'])->default('enrolled')->after('class_type');
            $table->unsignedBigInteger('tenant_id')->nullable()->after('access_policy');
        });
    }

    public function down(): void
    {
        Schema::table('online_classes', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'class_type', 'access_policy', 'tenant_id']);
        });
    }
};
