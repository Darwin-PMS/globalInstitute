<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Links a trainer record to a login.
 *
 * The Online Learning controllers resolve the acting teacher with
 * Trainer::where('user_id', auth()->id()), but the trainers table never had
 * that column - so every teacher lookup failed with an unknown-column error
 * and the shell fell back to treating teachers as students.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('trainers', 'user_id')) {
            return;
        }

        Schema::table('trainers', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Backfill existing trainers whose email already matches a user account.
        // Anything unmatched stays null and can be linked from the admin screen.
        DB::table('trainers')
            ->whereNotNull('email')
            ->orderBy('id')
            ->get(['id', 'email'])
            ->each(function ($trainer) {
                $userId = DB::table('users')->where('email', $trainer->email)->value('id');

                if ($userId) {
                    DB::table('trainers')->where('id', $trainer->id)->update(['user_id' => $userId]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('trainers', 'user_id')) {
            return;
        }

        Schema::table('trainers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
