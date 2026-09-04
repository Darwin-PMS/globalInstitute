<?php

namespace Database\Seeders;

use App\Models\AdminMenu;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RbacSeeder extends Seeder
{
    /**
     * Run the RBAC seeders in order and assign permissions to roles.
     *
     * Idempotent — safe to run repeatedly.
     */
    public function run(): void
    {
        // 1. Seed roles
        $this->call(RoleSeeder::class);

        // 2. Seed permissions
        $this->call(PermissionSeeder::class);

        // 3. Seed admin menus
        $this->call(AdminMenuSeeder::class);

        // 4. Assign permissions to roles
        $this->assignPermissionsToRoles();

        // 5. Assign menus to roles
        $this->assignMenusToRoles();

        // 6. Assign super-admin role to existing admin user
        $this->assignSuperAdmin();
    }

    private function assignPermissionsToRoles(): void
    {
        $allPermissions = Permission::where('is_active', true)->get()->keyBy('slug');

        // ── Super Admin gets everything ──────────────────
        $superAdmin = Role::where('slug', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->sync($allPermissions->pluck('id'));
        }

        // ── Admin permissions ────────────────────────────
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $adminSlugs = [
                'dashboard.view',
                // CMS
                'courses.view', 'courses.create', 'courses.edit', 'courses.delete', 'courses.export', 'courses.publish',
                'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
                'gallery.view', 'gallery.create', 'gallery.edit', 'gallery.delete', 'gallery.export',
                'testimonials.view', 'testimonials.create', 'testimonials.edit', 'testimonials.delete',
                'faqs.view', 'faqs.create', 'faqs.edit', 'faqs.delete',
                'pages.view', 'pages.create', 'pages.edit', 'pages.delete',
                // CRM
                'enquiries.view', 'enquiries.create', 'enquiries.edit', 'enquiries.delete', 'enquiries.export', 'enquiries.assign',
                'admissions.view', 'admissions.create', 'admissions.edit', 'admissions.delete', 'admissions.approve', 'admissions.reject', 'admissions.export',
                'lead_sources.view', 'lead_sources.create', 'lead_sources.edit', 'lead_sources.delete',
                // Students
                'students.view', 'students.create', 'students.edit', 'students.delete', 'students.export', 'students.import',
                'enrollments.view', 'enrollments.create', 'enrollments.edit', 'enrollments.delete', 'enrollments.export',
                // Academic
                'teachers.view', 'teachers.create', 'teachers.edit', 'teachers.delete', 'teachers.assign',
                'batches.view', 'batches.create', 'batches.edit', 'batches.delete', 'batches.assign',
                'rooms.view', 'rooms.create', 'rooms.edit', 'rooms.delete',
                'modules.view', 'modules.create', 'modules.edit', 'modules.delete',
                // Attendance
                'attendance.view', 'attendance.create', 'attendance.edit', 'attendance.export',
                // Online Learning
                'online_classes.view', 'online_classes.create', 'online_classes.edit', 'online_classes.delete', 'online_classes.export',
                'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.delete',
                'materials.view', 'materials.create', 'materials.edit', 'materials.delete',
                'assignments.view', 'assignments.create', 'assignments.edit', 'assignments.delete', 'assignments.export',
                'quizzes.view', 'quizzes.create', 'quizzes.edit', 'quizzes.delete', 'quizzes.export',
                'recordings.view', 'recordings.create', 'recordings.edit', 'recordings.delete', 'recordings.export',
                'student_progress.view', 'student_progress.export',
                // Live Classroom
                'live_classroom.join', 'live_classroom.manage', 'live_classroom.chat', 'live_classroom.screen_share', 'live_classroom.record', 'live_classroom.attendance',
                'live_classes.view', 'live_classes.create', 'live_classes.edit', 'live_classes.delete', 'live_classes.start', 'live_classes.end', 'live_classes.manage',
                // Finance
                'fees.view', 'fees.create', 'fees.edit', 'fees.delete', 'fees.export',
                'payments.view', 'payments.create', 'payments.edit', 'payments.delete', 'payments.export',
                'receipts.view', 'receipts.create', 'receipts.export',
                'fee_structure.view', 'fee_structure.create', 'fee_structure.edit', 'fee_structure.delete',
                // Reports
                'reports.view', 'reports.export',
                // Communications
                'announcements.view', 'announcements.create', 'announcements.edit', 'announcements.delete',
                'notifications.view', 'notifications.send',
                // Settings
                'settings.view', 'settings.edit',
                // Audit
                'audit_logs.view', 'audit_logs.export',
            ];
            $ids = $allPermissions->filter(fn ($p) => in_array($p->slug, $adminSlugs))->pluck('id');
            $admin->permissions()->sync($ids);
        }

        // ── Teacher permissions ──────────────────────────
        $teacher = Role::where('slug', 'teacher')->first();
        if ($teacher) {
            $teacherSlugs = [
                'dashboard.view',
                'courses.view',
                'students.view',
                'batches.view',
                'attendance.view', 'attendance.create', 'attendance.edit', 'attendance.export',
                'online_classes.view',
                'lessons.view', 'lessons.create', 'lessons.edit',
                'materials.view', 'materials.create', 'materials.edit',
                'assignments.view', 'assignments.create', 'assignments.edit', 'assignments.export',
                'quizzes.view', 'quizzes.create', 'quizzes.edit', 'quizzes.export',
                'recordings.view',
                'student_progress.view',
                'live_classroom.join', 'live_classroom.chat', 'live_classroom.screen_share',
                'live_classes.view',
                'announcements.view',
                'notifications.view',
            ];
            $ids = $allPermissions->filter(fn ($p) => in_array($p->slug, $teacherSlugs))->pluck('id');
            $teacher->permissions()->sync($ids);
        }

        // ── Student permissions ──────────────────────────
        $student = Role::where('slug', 'student')->first();
        if ($student) {
            $studentSlugs = [
                'dashboard.view',
                'courses.view',
                'online_classes.view',
                'lessons.view',
                'materials.view',
                'assignments.view',
                'quizzes.view',
                'recordings.view',
                'student_progress.view',
                'live_classroom.join', 'live_classroom.chat',
                'announcements.view',
                'notifications.view',
            ];
            $ids = $allPermissions->filter(fn ($p) => in_array($p->slug, $studentSlugs))->pluck('id');
            $student->permissions()->sync($ids);
        }

        // ── Finance permissions ──────────────────────────
        $finance = Role::where('slug', 'finance')->first();
        if ($finance) {
            $financeSlugs = [
                'dashboard.view',
                'students.view', 'students.export',
                'admissions.view', 'admissions.export',
                'enrollments.view', 'enrollments.export',
                'fees.view', 'fees.export',
                'payments.view', 'payments.create', 'payments.edit', 'payments.export',
                'receipts.view', 'receipts.create', 'receipts.export',
                'fee_structure.view',
                'reports.view', 'reports.export',
                'announcements.view',
            ];
            $ids = $allPermissions->filter(fn ($p) => in_array($p->slug, $financeSlugs))->pluck('id');
            $finance->permissions()->sync($ids);
        }
    }

    private function assignMenusToRoles(): void
    {
        $allMenus = AdminMenu::all()->keyBy('slug');

        // ── Super Admin gets all menus ───────────────────
        $superAdmin = Role::where('slug', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->menus()->sync($allMenus->pluck('id'));
        }

        // ── Admin gets all menus ─────────────────────────
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $admin->menus()->sync($allMenus->pluck('id'));
        }

        // ── Teacher menus ────────────────────────────────
        $teacher = Role::where('slug', 'teacher')->first();
        if ($teacher) {
            $teacherMenuSlugs = [
                'dashboard', 'learning',
                'live-classroom', 'online-classes', 'lessons', 'materials',
                'assignments', 'quizzes', 'recordings', 'student-progress',
            ];
            $ids = $allMenus->filter(fn ($m) => in_array($m->slug, $teacherMenuSlugs))->pluck('id');
            $teacher->menus()->sync($ids);
        }

        // ── Student menus ────────────────────────────────
        $student = Role::where('slug', 'student')->first();
        if ($student) {
            $studentMenuSlugs = [
                'dashboard', 'learning',
                'lessons', 'materials', 'assignments', 'quizzes', 'recordings', 'student-progress',
            ];
            $ids = $allMenus->filter(fn ($m) => in_array($m->slug, $studentMenuSlugs))->pluck('id');
            $student->menus()->sync($ids);
        }

        // ── Finance menus ────────────────────────────────
        $finance = Role::where('slug', 'finance')->first();
        if ($finance) {
            $financeMenuSlugs = [
                'dashboard', 'students-section', 'students', 'enrollments',
                'finance', 'fee-structures', 'payments',
                'reports-section', 'reports',
            ];
            $ids = $allMenus->filter(fn ($m) => in_array($m->slug, $financeMenuSlugs))->pluck('id');
            $finance->menus()->sync($ids);
        }
    }

    private function assignSuperAdmin(): void
    {
        $adminUser = DB::table('users')->where('email', 'admin@globalinstitute.edu.in')->first();
        if ($adminUser) {
            $superAdmin = Role::where('slug', 'super-admin')->first();
            if ($superAdmin) {
                DB::table('user_roles')->updateOrInsert(
                    ['user_id' => $adminUser->id, 'role_id' => $superAdmin->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}
