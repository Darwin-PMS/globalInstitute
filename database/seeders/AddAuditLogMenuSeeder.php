<?php

namespace Database\Seeders;

use App\Models\AdminMenu;
use Illuminate\Database\Seeder;

class AddAuditLogMenuSeeder extends Seeder
{
    public function run(): void
    {
        $system = AdminMenu::where('slug', 'system')->first();
        if ($system) {
            AdminMenu::updateOrCreate(
                ['slug' => 'audit-logs'],
                [
                    'parent_id' => $system->id,
                    'name' => 'Audit Logs',
                    'icon' => 'history',
                    'url' => '/admin/audit-logs',
                    'permission_key' => 'audit_logs.view',
                    'sort_order' => 4,
                    'is_spa' => false,
                    'is_active' => true,
                    'is_visible' => true,
                ]
            );
        }
    }
}
