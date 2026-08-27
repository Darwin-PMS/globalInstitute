import { role as currentRole, currentUser } from '../utils/permissions.js';

const ADMIN = '/admin/online-learning';
const TEACHER = '/teacher/online-learning';
const STUDENT = '/student/learning';

/**
 * Role-aware navigation. Every destination here has a matching route in
 * router.js — a link with nowhere to go is worse than no link.
 */
const NAV = {
    admin: [
        { group: 'Operations', items: [
            { label: 'Dashboard', href: ADMIN, icon: 'dashboard' },
            { label: 'Classes', href: `${ADMIN}/classes`, icon: 'video_camera_front' },
            { label: 'Calendar', href: `${ADMIN}/calendar`, icon: 'calendar_month' },
        ]},
        { group: 'Records', items: [
            { label: 'Attendance', href: `${ADMIN}/attendance`, icon: 'how_to_reg' },
            { label: 'Recordings', href: `${ADMIN}/recordings`, icon: 'smart_display' },
            { label: 'Materials', href: `${ADMIN}/materials`, icon: 'folder_open' },
        ]},
    ],
    teacher: [
        { group: 'Teaching', items: [
            { label: 'Dashboard', href: TEACHER, icon: 'dashboard' },
            { label: 'My Classes', href: `${TEACHER}/classes`, icon: 'videocam' },
            { label: 'Calendar', href: `${TEACHER}/calendar`, icon: 'calendar_month' },
        ]},
        { group: 'Student work', items: [
            { label: 'Submissions', href: `${TEACHER}/submissions`, icon: 'assignment_turned_in' },
        ]},
    ],
    student: [
        { group: 'Learning', items: [
            { label: 'Dashboard', href: STUDENT, icon: 'dashboard' },
            { label: 'My Courses', href: `${STUDENT}/courses`, icon: 'auto_stories' },
            { label: 'Live Classes', href: `${STUDENT}/classes`, icon: 'live_tv' },
            { label: 'Calendar', href: `${STUDENT}/calendar`, icon: 'calendar_month' },
        ]},
        { group: 'Resources', items: [
            { label: 'Recordings', href: `${STUDENT}/recordings`, icon: 'play_circle' },
            { label: 'Assignments', href: `${STUDENT}/assignments`, icon: 'assignment' },
            { label: 'Progress', href: `${STUDENT}/progress`, icon: 'trending_up' },
        ]},
    ],
};

export class Sidebar {
    constructor() {
        this.container = document.getElementById('learning-sidebar');
        this.role = currentRole();
        this.user = currentUser();
    }

    /**
     * Active when the path matches exactly, or when it is a child of a
     * non-root nav item (so /classes/12 keeps "Classes" lit).
     */
    isActive(href, pathname) {
        if (pathname === href) return true;

        const base = { admin: ADMIN, teacher: TEACHER, student: STUDENT }[this.role];
        if (href === base) return false; // the dashboard should not own every child route

        return pathname.startsWith(`${href}/`);
    }

    render() {
        if (!this.container) return;

        const pathname = window.location.pathname.replace(/\/+$/, '') || window.location.pathname;
        const groups = NAV[this.role] || NAV.student;
        const initial = this.user.name ? this.user.name.charAt(0).toUpperCase() : 'U';

        this.container.innerHTML = `
            <div class="h-16 flex items-center gap-3 px-5 border-b border-slate-800 bg-slate-950/50 flex-shrink-0">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-600/30 flex-shrink-0">
                    <span class="material-symbols-outlined text-xl" aria-hidden="true">school</span>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="font-bold text-sm tracking-wide text-white leading-tight truncate">Global Institute</span>
                    <span class="text-[10px] font-semibold tracking-wider uppercase text-indigo-400">LMS &amp; CAD CAM</span>
                </div>

                <button type="button" id="sidebar-close" aria-label="Close navigation"
                    class="lg:hidden ml-auto p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-lg" aria-hidden="true">close</span>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-5 space-y-5" aria-label="${this.role} navigation">
                ${groups.map((group) => `
                    <div class="space-y-1">
                        <h2 class="px-3 mb-1.5 text-[10px] font-bold tracking-wider uppercase text-slate-500">
                            ${group.group}
                        </h2>

                        ${group.items.map((item) => {
                            const active = this.isActive(item.href, pathname);
                            return `
                                <a href="${item.href}" data-spa-link ${active ? 'aria-current="page"' : ''}
                                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all duration-200 ${active
                                       ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25 font-semibold'
                                       : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'}">
                                    <span class="material-symbols-outlined text-lg ${active ? 'text-white' : 'text-slate-400'}" aria-hidden="true">${item.icon}</span>
                                    <span class="flex-1 truncate">${item.label}</span>
                                </a>
                            `;
                        }).join('')}
                    </div>
                `).join('')}
            </nav>

            <div class="p-4 border-t border-slate-800/80 bg-slate-950/40 flex-shrink-0">
                <div class="flex items-center gap-3 px-1">
                    <div class="w-9 h-9 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-indigo-400 font-bold text-sm flex-shrink-0">
                        ${initial}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-slate-200 truncate">${this.user.name || 'User'}</p>
                        <p class="text-[10px] text-slate-400 truncate capitalize">${this.role}</p>
                    </div>
                </div>
            </div>
        `;

        this.container.querySelector('#sidebar-close')?.addEventListener('click', () => Sidebar.close());

        // On mobile the drawer overlays the content, so a chosen link closes it.
        this.container.querySelectorAll('a[data-spa-link]').forEach((link) => {
            link.addEventListener('click', () => {
                if (window.matchMedia('(max-width: 1023px)').matches) Sidebar.close();
            });
        });
    }

    /* ---------------- mobile drawer control (static: no instance needed) --- */

    static open() {
        const sidebar = document.getElementById('learning-sidebar');
        const scrim = document.getElementById('learning-sidebar-scrim');
        if (!sidebar) return;

        sidebar.classList.remove('-translate-x-full');
        scrim?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        // Escape closes it, matching the modal and drawer behaviour (§40).
        Sidebar.keyHandler = (e) => { if (e.key === 'Escape') Sidebar.close(); };
        document.addEventListener('keydown', Sidebar.keyHandler);

        scrim?.addEventListener('click', Sidebar.close, { once: true });
        sidebar.querySelector('a')?.focus();
    }

    static close() {
        const sidebar = document.getElementById('learning-sidebar');
        const scrim = document.getElementById('learning-sidebar-scrim');
        if (!sidebar) return;

        // Only re-hide below lg; on desktop the sidebar is a static column.
        if (window.matchMedia('(max-width: 1023px)').matches) {
            sidebar.classList.add('-translate-x-full');
        }
        scrim?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');

        if (Sidebar.keyHandler) {
            document.removeEventListener('keydown', Sidebar.keyHandler);
            Sidebar.keyHandler = null;
        }
    }

    static toggle() {
        const sidebar = document.getElementById('learning-sidebar');
        if (!sidebar) return;

        if (sidebar.classList.contains('-translate-x-full')) Sidebar.open();
        else Sidebar.close();
    }
}
