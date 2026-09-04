<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin',  'slug' => 'super-admin', 'description' => 'Full system access. Bypasses all permission checks.', 'is_system' => true],
            ['name' => 'Admin',        'slug' => 'admin',       'description' => 'Institute operational administrator.', 'is_system' => true],
            ['name' => 'Teacher',      'slug' => 'teacher',     'description' => 'Teaching-focused access to courses, batches, and students.', 'is_system' => true],
            ['name' => 'Student',      'slug' => 'student',     'description' => 'Student portal access.', 'is_system' => true],
            ['name' => 'Finance',      'slug' => 'finance',     'description' => 'Finance and accounting access.', 'is_system' => true],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
