import { showPageLoading, hidePageLoading } from '../ui/loading.js';
import { Sidebar } from './components/Sidebar.js';
import { Topbar } from './components/Topbar.js';

// Admin
import { AdminDashboardPage } from './pages/admin/DashboardPage.js';
import { AdminClassesPage } from './pages/admin/ClassesPage.js';
import { AdminAttendancePage } from './pages/admin/AttendancePage.js';
import { AdminRecordingsPage } from './pages/admin/RecordingsPage.js';
import { AdminMaterialsPage } from './pages/admin/MaterialsPage.js';

// Teacher
import { TeacherDashboardPage } from './pages/teacher/DashboardPage.js';
import { TeacherClassesPage } from './pages/teacher/ClassesPage.js';
import { TeacherSubmissionsPage } from './pages/teacher/SubmissionsPage.js';

// Student
import { StudentDashboardPage } from './pages/student/DashboardPage.js';
import { StudentCoursesPage } from './pages/student/CoursesPage.js';
import { StudentClassesPage } from './pages/student/ClassesPage.js';
import { StudentClassDetailPage } from './pages/student/ClassDetailPage.js';
import { StudentRecordingsPage } from './pages/student/RecordingsPage.js';
import { StudentAssignmentsPage } from './pages/student/AssignmentsPage.js';
import { StudentProgressPage } from './pages/student/ProgressPage.js';

// Shared
import { CalendarPage } from './pages/shared/CalendarPage.js';
import { StaffClassDetailPage } from './pages/shared/StaffClassDetailPage.js';

const ADMIN = '/admin/online-learning';
const TEACHER = '/teacher/online-learning';
const STUDENT = '/student/learning';

/**
 * Route table. Each entry declares its pattern, the page class, the title and
 * the breadcrumb trail. `:id` captures a numeric segment.
 *
 * Order matters: more specific patterns come before their prefixes.
 */
function routeTable() {
    return [
        /* ---------------------------------------------------------- admin */
        {
            path: `${ADMIN}/classes/:id`, page: StaffClassDetailPage, title: 'Class Detail',
            crumbs: [{ label: 'Dashboard', href: ADMIN }, { label: 'Classes', href: `${ADMIN}/classes` }, { label: 'Detail' }],
        },
        {
            path: `${ADMIN}/classes`, page: AdminClassesPage, title: 'Classes Management',
            crumbs: [{ label: 'Dashboard', href: ADMIN }, { label: 'Classes' }],
        },
        {
            path: `${ADMIN}/calendar`, page: CalendarPage, title: 'Class Calendar',
            crumbs: [{ label: 'Dashboard', href: ADMIN }, { label: 'Calendar' }],
        },
        {
            path: `${ADMIN}/attendance`, page: AdminAttendancePage, title: 'Attendance Log',
            crumbs: [{ label: 'Dashboard', href: ADMIN }, { label: 'Attendance' }],
        },
        {
            path: `${ADMIN}/recordings`, page: AdminRecordingsPage, title: 'Recordings Library',
            crumbs: [{ label: 'Dashboard', href: ADMIN }, { label: 'Recordings' }],
        },
        {
            path: `${ADMIN}/materials`, page: AdminMaterialsPage, title: 'Materials Library',
            crumbs: [{ label: 'Dashboard', href: ADMIN }, { label: 'Materials' }],
        },
        {
            path: ADMIN, page: AdminDashboardPage, title: 'Online Learning',
            crumbs: [{ label: 'Online Learning' }],
        },

        /* -------------------------------------------------------- teacher */
        {
            path: `${TEACHER}/classes/:id`, page: StaffClassDetailPage, title: 'Class Detail',
            crumbs: [{ label: 'Dashboard', href: TEACHER }, { label: 'My Classes', href: `${TEACHER}/classes` }, { label: 'Detail' }],
        },
        {
            path: `${TEACHER}/classes`, page: TeacherClassesPage, title: 'My Classes',
            crumbs: [{ label: 'Dashboard', href: TEACHER }, { label: 'My Classes' }],
        },
        {
            path: `${TEACHER}/calendar`, page: CalendarPage, title: 'My Calendar',
            crumbs: [{ label: 'Dashboard', href: TEACHER }, { label: 'Calendar' }],
        },
        {
            path: `${TEACHER}/submissions`, page: TeacherSubmissionsPage, title: 'Submissions',
            crumbs: [{ label: 'Dashboard', href: TEACHER }, { label: 'Submissions' }],
        },
        {
            path: TEACHER, page: TeacherDashboardPage, title: 'Teacher Portal',
            crumbs: [{ label: 'Dashboard' }],
        },

        /* -------------------------------------------------------- student */
        {
            path: `${STUDENT}/classes/:id`, page: StudentClassDetailPage, title: 'Live Class',
            crumbs: [{ label: 'Dashboard', href: STUDENT }, { label: 'Classes', href: `${STUDENT}/classes` }, { label: 'Class' }],
        },
        {
            path: `${STUDENT}/classes`, page: StudentClassesPage, title: 'Live Classes',
            crumbs: [{ label: 'Dashboard', href: STUDENT }, { label: 'Classes' }],
        },
        {
            path: `${STUDENT}/courses/:id`, page: StudentCoursesPage, title: 'Course',
            crumbs: [{ label: 'Dashboard', href: STUDENT }, { label: 'Courses', href: `${STUDENT}/courses` }, { label: 'Course' }],
        },
        {
            path: `${STUDENT}/courses`, page: StudentCoursesPage, title: 'My Courses',
            crumbs: [{ label: 'Dashboard', href: STUDENT }, { label: 'Courses' }],
        },
        {
            path: `${STUDENT}/calendar`, page: CalendarPage, title: 'My Calendar',
            crumbs: [{ label: 'Dashboard', href: STUDENT }, { label: 'Calendar' }],
        },
        {
            path: `${STUDENT}/recordings`, page: StudentRecordingsPage, title: 'Recordings',
            crumbs: [{ label: 'Dashboard', href: STUDENT }, { label: 'Recordings' }],
        },
        {
            path: `${STUDENT}/assignments`, page: StudentAssignmentsPage, title: 'Assignments',
            crumbs: [{ label: 'Dashboard', href: STUDENT }, { label: 'Assignments' }],
        },
        {
            path: `${STUDENT}/progress`, page: StudentProgressPage, title: 'My Progress',
            crumbs: [{ label: 'Dashboard', href: STUDENT }, { label: 'Progress' }],
        },
        {
            path: STUDENT, page: StudentDashboardPage, title: 'Student Portal',
            crumbs: [{ label: 'Dashboard' }],
        },
    ];
}

/**
 * Client-side router for the Online Learning module.
 *
 * Uses the History API so back/forward/refresh and deep links behave the way
 * they would on a server-rendered site (§6, §55, §56). Laravel serves the same
 * shell for every path under the three role prefixes, so resolution happens here.
 */
export class Router {
    constructor() {
        this.sidebar = new Sidebar();
        this.topbar = new Topbar();
        this.routes = routeTable();
        this.currentPage = null;
        this.init();
    }

    init() {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href]');
            if (!link) return;

            // Let modified clicks (new tab, download, save) behave natively.
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;

            const href = link.getAttribute('href');
            if (!href) return;

            if (href.startsWith('http') || href.startsWith('#') || href.startsWith('mailto:')
                || link.target === '_blank' || link.hasAttribute('download')
                || link.dataset.spa === 'false') {
                return;
            }

            if (this.isModulePath(href.split('?')[0])) {
                e.preventDefault();
                this.navigate(href);
            }
        });

        window.addEventListener('popstate', () => {
            this.handleRoute(window.location.pathname + window.location.search);
        });

        this.handleRoute(window.location.pathname + window.location.search);
    }

    isModulePath(pathname) {
        return pathname.startsWith(ADMIN) || pathname.startsWith(TEACHER) || pathname.startsWith(STUDENT);
    }

    navigate(url) {
        const current = window.location.pathname + window.location.search;
        if (current !== url) {
            window.history.pushState(null, '', url);
        }
        this.handleRoute(url);
    }

    /**
     * Match a pathname against the table, capturing `:id` segments.
     */
    match(pathname) {
        const clean = pathname.replace(/\/+$/, '') || pathname;

        for (const route of this.routes) {
            const params = this.matchPattern(route.path, clean);
            if (params) return { route, params };
        }
        return null;
    }

    matchPattern(pattern, pathname) {
        const patternParts = pattern.split('/').filter(Boolean);
        const pathParts = pathname.split('/').filter(Boolean);

        if (patternParts.length !== pathParts.length) return null;

        const params = {};
        for (let i = 0; i < patternParts.length; i += 1) {
            const p = patternParts[i];

            if (p.startsWith(':')) {
                params[p.slice(1)] = pathParts[i];
            } else if (p !== pathParts[i]) {
                return null;
            }
        }
        return params;
    }

    async handleRoute(url) {
        const pathname = url.split('?')[0];

        // Tear down the previous page so timers (countdowns) do not leak.
        if (this.currentPage?.destroy) {
            try { this.currentPage.destroy(); } catch { /* teardown is best-effort */ }
        }
        this.currentPage = null;

        const matched = this.match(pathname);

        if (!matched) {
            this.renderNotFound(pathname);
            return;
        }

        const { route, params } = matched;

        this.sidebar.render();
        this.topbar.render(route.title, route.crumbs || []);

        const page = new route.page();
        this.currentPage = page;

        // A page that takes an id receives it; the rest ignore the argument.
        const id = params.id ? parseInt(params.id, 10) : null;

        showPageLoading();

        try {
            await page.render(id);
        } catch (err) {
            console.error('[Router] page render failed:', err);
            this.renderError(err);
        } finally {
            hidePageLoading();
        }
    }

    /**
     * Unknown sub-path: say so and offer a way back, rather than bouncing the
     * user to the dashboard as though nothing happened.
     */
    renderNotFound(pathname) {
        const role = window.OnlineLearningConfig?.role || 'student';
        const home = role === 'admin' ? ADMIN : role === 'teacher' ? TEACHER : STUDENT;

        // Outside the module entirely - hand back to the server.
        if (!this.isModulePath(pathname)) {
            window.location.href = home;
            return;
        }

        this.sidebar.render();
        this.topbar.render('Page not found', [{ label: 'Online Learning', href: home }, { label: 'Not found' }]);

        const content = document.getElementById('learning-content');
        if (content) {
            content.innerHTML = `
                <div class="max-w-md mx-auto mt-16 text-center space-y-4">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-400">
                        <span class="material-symbols-outlined text-3xl" aria-hidden="true">explore_off</span>
                    </div>
                    <h2 class="text-base font-bold text-slate-200">This page does not exist</h2>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        <span class="font-mono text-slate-500">${pathname}</span> is not part of the Online Learning module.
                    </p>
                    <a href="${home}" data-spa-link
                       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition-colors">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">arrow_back</span> Back to dashboard
                    </a>
                </div>
            `;
        }
    }

    renderError(err) {
        const content = document.getElementById('learning-content');
        if (!content) return;

        content.innerHTML = `
            <div class="max-w-md mx-auto mt-16 text-center space-y-4">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-rose-950/40 border border-rose-500/30 flex items-center justify-center text-rose-400">
                    <span class="material-symbols-outlined text-3xl" aria-hidden="true">error</span>
                </div>
                <h2 class="text-base font-bold text-slate-200">Something went wrong</h2>
                <p class="text-xs text-slate-400 leading-relaxed">${err?.message || 'This screen could not be loaded.'}</p>
                <button type="button" id="router-reload"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold border border-slate-700 transition-colors">
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">refresh</span> Try again
                </button>
            </div>
        `;

        content.querySelector('#router-reload')?.addEventListener('click', () => {
            this.handleRoute(window.location.pathname + window.location.search);
        });
    }
}
