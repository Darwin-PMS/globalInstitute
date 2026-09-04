<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Permission definitions: module => [actions]
     * Each action becomes: module.action (e.g. students.view)
     *
     * Special actions with custom slugs are defined separately.
     */
    public function run(): void
    {
        $definitions = [
            // ── Dashboard ─────────────────────────────────
            'dashboard' => ['view'],

            // ── CMS ───────────────────────────────────────
            'courses'    => ['view', 'create', 'edit', 'delete', 'export', 'publish'],
            'categories' => ['view', 'create', 'edit', 'delete'],
            'gallery'    => ['view', 'create', 'edit', 'delete', 'export'],
            'testimonials' => ['view', 'create', 'edit', 'delete'],
            'faqs'       => ['view', 'create', 'edit', 'delete'],
            'pages'      => ['view', 'create', 'edit', 'delete'],

            // ── CRM ───────────────────────────────────────
            'enquiries'  => ['view', 'create', 'edit', 'delete', 'export', 'assign'],
            'admissions' => ['view', 'create', 'edit', 'delete', 'approve', 'reject', 'export'],
            'lead_sources' => ['view', 'create', 'edit', 'delete'],

            // ── Academic ──────────────────────────────────
            'teachers'   => ['view', 'create', 'edit', 'delete', 'assign'],
            'batches'    => ['view', 'create', 'edit', 'delete', 'assign'],
            'rooms'      => ['view', 'create', 'edit', 'delete'],
            'modules'    => ['view', 'create', 'edit', 'delete'],

            // ── Students ──────────────────────────────────
            'students'   => ['view', 'create', 'edit', 'delete', 'export', 'import', 'restore'],
            'enrollments' => ['view', 'create', 'edit', 'delete', 'export'],

            // ── Attendance ────────────────────────────────
            'attendance' => ['view', 'create', 'edit', 'export', 'approve'],

            // ── Finance ───────────────────────────────────
            'fees'       => ['view', 'create', 'edit', 'delete', 'export'],
            'payments'   => ['view', 'create', 'edit', 'delete', 'export', 'refund'],
            'receipts'   => ['view', 'create', 'export'],
            'fee_structure' => ['view', 'create', 'edit', 'delete'],

            // ── Online Learning ───────────────────────────
            'online_classes' => ['view', 'create', 'edit', 'delete', 'export'],
            'lessons'    => ['view', 'create', 'edit', 'delete'],
            'materials'  => ['view', 'create', 'edit', 'delete'],
            'assignments' => ['view', 'create', 'edit', 'delete', 'export'],
            'quizzes'    => ['view', 'create', 'edit', 'delete', 'export'],
            'recordings' => ['view', 'create', 'edit', 'delete', 'export'],
            'student_progress' => ['view', 'export'],

            // ── Live Classroom ────────────────────────────
            'live_classroom' => ['join', 'manage', 'chat', 'screen_share', 'record', 'attendance'],
            'live_classes' => ['view', 'create', 'edit', 'delete', 'start', 'end', 'manage'],

            // ── Reports ───────────────────────────────────
            'reports'    => ['view', 'export'],

            // ── Communications ────────────────────────────
            'announcements' => ['view', 'create', 'edit', 'delete'],
            'notifications' => ['view', 'send'],

            // ── Administration ────────────────────────────
            'users'      => ['view', 'create', 'edit', 'delete', 'export'],
            'roles'      => ['view', 'create', 'edit', 'delete', 'manage'],
            'permissions' => ['view', 'manage'],
            'admin_menus' => ['view', 'create', 'edit', 'delete', 'manage'],
            'audit_logs' => ['view', 'export'],
            'settings'   => ['view', 'edit'],
        ];

        foreach ($definitions as $module => $actions) {
            foreach ($actions as $action) {
                $slug = "{$module}.{$action}";
                $name = ucfirst(str_replace('_', ' ', $module)) . ' — ' . ucfirst(str_replace('_', ' ', $action));

                Permission::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'module' => $module,
                        'action' => $action,
                    ]
                );
            }
        }
    }
}
