<?php

namespace Database\Seeders;

use App\Models\AdminMenu;
use Illuminate\Database\Seeder;

class AdminMenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            // ── Main ─────────────────────────────────────
            [
                'name' => 'Dashboard', 'slug' => 'dashboard', 'icon' => 'dashboard',
                'route_name' => 'admin.dashboard', 'permission_key' => 'dashboard.view',
                'sort_order' => 0, 'is_spa' => true, 'menu_type' => 'sidebar',
            ],

            // ── Content ──────────────────────────────────
            [
                'name' => 'Content', 'slug' => 'content', 'icon' => 'folder',
                'sort_order' => 10, 'is_visible' => false, 'menu_type' => 'sidebar',
            ],
            ['name' => 'Courses', 'slug' => 'courses', 'icon' => 'school', 'parent_slug' => 'content', 'route_name' => 'admin.courses.index', 'permission_key' => 'courses.view', 'sort_order' => 1, 'is_spa' => true, 'menu_type' => 'sidebar'],
            ['name' => 'Categories', 'slug' => 'categories', 'icon' => 'category', 'parent_slug' => 'content', 'route_name' => 'admin.categories.index', 'permission_key' => 'categories.view', 'sort_order' => 2, 'is_spa' => true, 'menu_type' => 'sidebar'],
            ['name' => 'Gallery', 'slug' => 'gallery', 'icon' => 'photo_library', 'parent_slug' => 'content', 'route_name' => 'admin.gallery.index', 'permission_key' => 'gallery.view', 'sort_order' => 3, 'is_spa' => true, 'menu_type' => 'sidebar'],
            ['name' => 'Testimonials', 'slug' => 'testimonials', 'icon' => 'reviews', 'parent_slug' => 'content', 'route_name' => 'admin.testimonials.index', 'permission_key' => 'testimonials.view', 'sort_order' => 4, 'menu_type' => 'sidebar'],
            ['name' => 'FAQs', 'slug' => 'faqs', 'icon' => 'help', 'parent_slug' => 'content', 'route_name' => 'admin.faqs.index', 'permission_key' => 'faqs.view', 'sort_order' => 5, 'menu_type' => 'sidebar'],
            ['name' => 'Pages', 'slug' => 'pages', 'icon' => 'description', 'parent_slug' => 'content', 'route_name' => 'admin.pages.index', 'permission_key' => 'pages.view', 'sort_order' => 6, 'menu_type' => 'sidebar'],

            // ── CRM ──────────────────────────────────────
            [
                'name' => 'CRM', 'slug' => 'crm', 'icon' => 'contact_phone',
                'sort_order' => 20, 'is_visible' => false, 'menu_type' => 'sidebar',
            ],
            ['name' => 'Enquiries', 'slug' => 'enquiries', 'icon' => 'contact_page', 'parent_slug' => 'crm', 'route_name' => 'admin.enquiries.index', 'permission_key' => 'enquiries.view', 'sort_order' => 1, 'is_spa' => true, 'menu_type' => 'sidebar'],
            ['name' => 'Admissions', 'slug' => 'admissions', 'icon' => 'how_to_reg', 'parent_slug' => 'crm', 'route_name' => 'admin.admissions.index', 'permission_key' => 'admissions.view', 'sort_order' => 2, 'menu_type' => 'sidebar'],
            ['name' => 'Lead Sources', 'slug' => 'lead-sources', 'icon' => 'source', 'parent_slug' => 'crm', 'route_name' => 'admin.lead_sources.index', 'permission_key' => 'lead_sources.view', 'sort_order' => 3, 'menu_type' => 'sidebar'],

            // ── Students ─────────────────────────────────
            [
                'name' => 'Students', 'slug' => 'students-section', 'icon' => 'people',
                'sort_order' => 30, 'is_visible' => false, 'menu_type' => 'sidebar',
            ],
            ['name' => 'Students', 'slug' => 'students', 'icon' => 'person', 'parent_slug' => 'students-section', 'route_name' => 'admin.students.index', 'permission_key' => 'students.view', 'sort_order' => 1, 'is_spa' => true, 'menu_type' => 'sidebar'],
            ['name' => 'Enrollments', 'slug' => 'enrollments', 'icon' => 'badge', 'parent_slug' => 'students-section', 'route_name' => 'admin.enrollments.index', 'permission_key' => 'enrollments.view', 'sort_order' => 2, 'menu_type' => 'sidebar'],

            // ── Academic ─────────────────────────────────
            [
                'name' => 'Academic', 'slug' => 'academic', 'icon' => 'menu_book',
                'sort_order' => 40, 'is_visible' => false, 'menu_type' => 'sidebar',
            ],
            ['name' => 'Teachers', 'slug' => 'teachers', 'icon' => 'groups', 'parent_slug' => 'academic', 'route_name' => 'admin.teachers.index', 'permission_key' => 'teachers.view', 'sort_order' => 1, 'is_spa' => true, 'menu_type' => 'sidebar'],
            ['name' => 'Batches', 'slug' => 'batches', 'icon' => 'calendar_month', 'parent_slug' => 'academic', 'route_name' => 'admin.batches.index', 'permission_key' => 'batches.view', 'sort_order' => 2, 'is_spa' => true, 'menu_type' => 'sidebar'],
            ['name' => 'Rooms', 'slug' => 'rooms', 'icon' => 'meeting_room', 'parent_slug' => 'academic', 'route_name' => 'admin.rooms.index', 'permission_key' => 'rooms.view', 'sort_order' => 3, 'menu_type' => 'sidebar'],
            ['name' => 'Modules', 'slug' => 'modules', 'icon' => 'view_module', 'parent_slug' => 'academic', 'route_name' => 'admin.course-modules.index', 'permission_key' => 'modules.view', 'sort_order' => 4, 'menu_type' => 'sidebar'],

            // ── Online Learning ──────────────────────────
            [
                'name' => 'Online Learning', 'slug' => 'learning', 'icon' => 'cast_for_education',
                'sort_order' => 50, 'is_visible' => false, 'menu_type' => 'sidebar',
            ],
            ['name' => 'Live Classroom', 'slug' => 'live-classroom', 'icon' => 'live_tv', 'parent_slug' => 'learning', 'route_name' => 'admin.live-classroom.index', 'permission_key' => 'live_classroom.manage', 'sort_order' => 1, 'is_spa' => true, 'menu_type' => 'sidebar'],
            ['name' => 'Schedule Classes', 'slug' => 'online-classes', 'icon' => 'videocam', 'parent_slug' => 'learning', 'route_name' => 'admin.online-classes.index', 'permission_key' => 'online_classes.view', 'sort_order' => 2, 'is_spa' => true, 'menu_type' => 'sidebar'],
            ['name' => 'Lessons', 'slug' => 'lessons', 'icon' => 'menu_book', 'parent_slug' => 'learning', 'route_name' => 'admin.lessons.index', 'permission_key' => 'lessons.view', 'sort_order' => 3, 'is_spa' => true, 'menu_type' => 'sidebar'],
            ['name' => 'Materials', 'slug' => 'materials', 'icon' => 'folder_open', 'parent_slug' => 'learning', 'route_name' => 'admin.materials.index', 'permission_key' => 'materials.view', 'sort_order' => 4, 'is_spa' => true, 'menu_type' => 'sidebar'],
            ['name' => 'Assignments', 'slug' => 'assignments', 'icon' => 'assignment', 'parent_slug' => 'learning', 'route_name' => 'admin.assignments.index', 'permission_key' => 'assignments.view', 'sort_order' => 5, 'is_spa' => true, 'menu_type' => 'sidebar'],
            ['name' => 'Quizzes', 'slug' => 'quizzes', 'icon' => 'quiz', 'parent_slug' => 'learning', 'route_name' => 'admin.quizzes.index', 'permission_key' => 'quizzes.view', 'sort_order' => 6, 'is_spa' => true, 'menu_type' => 'sidebar'],
            ['name' => 'Recordings', 'slug' => 'recordings', 'icon' => 'video_library', 'parent_slug' => 'learning', 'route_name' => 'admin.recordings.index', 'permission_key' => 'recordings.view', 'sort_order' => 7, 'is_spa' => true, 'menu_type' => 'sidebar'],
            ['name' => 'Progress', 'slug' => 'student-progress', 'icon' => 'trending_up', 'parent_slug' => 'learning', 'route_name' => 'admin.student-progress.index', 'permission_key' => 'student_progress.view', 'sort_order' => 8, 'menu_type' => 'sidebar'],

            // ── Finance ──────────────────────────────────
            [
                'name' => 'Finance', 'slug' => 'finance', 'icon' => 'account_balance',
                'sort_order' => 60, 'is_visible' => false, 'menu_type' => 'sidebar',
            ],
            ['name' => 'Fee Structure', 'slug' => 'fee-structures', 'icon' => 'account_balance_wallet', 'parent_slug' => 'finance', 'route_name' => 'admin.fee-structures.index', 'permission_key' => 'fee_structure.view', 'sort_order' => 1, 'menu_type' => 'sidebar'],
            ['name' => 'Payments', 'slug' => 'payments', 'icon' => 'payments', 'parent_slug' => 'finance', 'route_name' => 'admin.payments.index', 'permission_key' => 'payments.view', 'sort_order' => 2, 'menu_type' => 'sidebar'],

            // ── Reports ──────────────────────────────────
            [
                'name' => 'Reports', 'slug' => 'reports-section', 'icon' => 'assessment',
                'sort_order' => 70, 'is_visible' => false, 'menu_type' => 'sidebar',
            ],
            ['name' => 'Reports', 'slug' => 'reports', 'icon' => 'analytics', 'parent_slug' => 'reports-section', 'route_name' => 'admin.reports.index', 'permission_key' => 'reports.view', 'sort_order' => 1, 'menu_type' => 'sidebar'],

            // ── System ───────────────────────────────────
            [
                'name' => 'System', 'slug' => 'system', 'icon' => 'settings',
                'sort_order' => 80, 'is_visible' => false, 'menu_type' => 'sidebar',
            ],
            ['name' => 'Users', 'slug' => 'users', 'icon' => 'manage_accounts', 'parent_slug' => 'system', 'route_name' => 'admin.users.index', 'permission_key' => 'users.view', 'sort_order' => 1, 'is_spa' => false, 'menu_type' => 'sidebar'],
            ['name' => 'Roles & Permissions', 'slug' => 'roles', 'icon' => 'admin_panel_settings', 'parent_slug' => 'system', 'route_name' => 'admin.roles.index', 'permission_key' => 'roles.view', 'sort_order' => 2, 'is_spa' => false, 'menu_type' => 'sidebar'],
            ['name' => 'Settings', 'slug' => 'settings', 'icon' => 'settings', 'parent_slug' => 'system', 'route_name' => 'admin.settings.edit', 'permission_key' => 'settings.view', 'sort_order' => 3, 'menu_type' => 'sidebar'],
            ['name' => 'Audit Logs', 'slug' => 'audit-logs', 'icon' => 'history', 'parent_slug' => 'system', 'route_name' => 'admin.audit-logs.index', 'permission_key' => 'audit_logs.view', 'sort_order' => 4, 'is_spa' => false, 'menu_type' => 'sidebar'],
        ];

        $parentMap = [];

        // First pass: create parent menus
        foreach ($menus as $item) {
            if (!isset($item['parent_slug'])) {
                $menu = AdminMenu::updateOrCreate(
                    ['slug' => $item['slug']],
                    [
                        'name' => $item['name'],
                        'icon' => $item['icon'],
                        'route_name' => $item['route_name'] ?? null,
                        'url' => $item['url'] ?? null,
                        'permission_key' => $item['permission_key'] ?? null,
                        'sort_order' => $item['sort_order'],
                        'is_visible' => $item['is_visible'] ?? true,
                        'is_active' => true,
                        'menu_type' => $item['menu_type'] ?? 'sidebar',
                    ]
                );
                $parentMap[$item['slug']] = $menu->id;
            }
        }

        // Second pass: create child menus
        foreach ($menus as $item) {
            if (isset($item['parent_slug'])) {
                $parentId = $parentMap[$item['parent_slug']] ?? null;

                AdminMenu::updateOrCreate(
                    ['slug' => $item['slug']],
                    [
                        'name' => $item['name'],
                        'parent_id' => $parentId,
                        'icon' => $item['icon'] ?? null,
                        'route_name' => $item['route_name'] ?? null,
                        'url' => $item['url'] ?? null,
                        'permission_key' => $item['permission_key'] ?? null,
                        'sort_order' => $item['sort_order'],
                        'is_spa' => $item['is_spa'] ?? false,
                        'is_active' => true,
                        'is_visible' => true,
                        'menu_type' => $item['menu_type'] ?? 'sidebar',
                    ]
                );
            }
        }
    }
}
