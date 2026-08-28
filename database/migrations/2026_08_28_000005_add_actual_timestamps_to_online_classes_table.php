<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_classes', function (Blueprint $table) {
            $table->timestamp('actual_start_at')->nullable()->after('status');
            $table->timestamp('actual_end_at')->nullable()->after('actual_start_at');
        });
    }

    public function down(): void
    {
        Schema::table('online_classes', function (Blueprint $table) {
            $table->dropColumn(['actual_start_at', 'actual_end_at']);
        });
    }
};
