<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `users.role` was created with a default of 'admin'.
 *
 * Combined with the old AdminMiddleware check (role === 'admin'), that meant
 * every newly created account - including student and teacher logins - would
 * have passed the admin gate. Authorisation now reads `is_admin`, and this
 * migration removes the dangerous default so the column cannot grant rights by
 * omission.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        // Raw DDL: changing a column default with the schema builder requires
        // doctrine/dbal, which this project does not install.
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE `users` ALTER COLUMN `role` SET DEFAULT 'student'");
        }

        // Bring existing rows in line with the authoritative flag so the two
        // columns cannot disagree.
        DB::table('users')->where('is_admin', true)->update(['role' => 'admin']);
        DB::table('users')->where('is_admin', false)->where('role', 'admin')->update(['role' => 'student']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE `users` ALTER COLUMN `role` SET DEFAULT 'admin'");
        }
    }
};
