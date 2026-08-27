import { classesApi } from '../../api/classes.js';
import { renderClassTable } from '../../components/ClassTable.js';
import { renderClassCard } from '../../components/ClassCard.js';
import { renderClassCalendar } from '../../components/ClassCalendar.js';
import { getTableSkeletonHTML, getCardsSkeletonHTML } from '../../components/LoadingState.js';
import { getEmptyStateHTML } from '../../components/EmptyState.js';
import { renderPagination, bindPagination } from '../../components/Pagination.js';
import { Drawer } from '../../components/Drawer.js';
import { confirmAction } from '../../components/ConfirmDialog.js';
import { debounce } from '../../utils/debounce.js';
import { formatDate, formatTime } from '../../utils/date.js';
import { getStatusBadgeHTML, getProviderBadgeHTML } from '../../utils/formatter.js';
import { hydrateFilters, writeQuery } from '../../utils/urlState.js';
import { path } from '../../utils/permissions.js';

const STATUSES = ['scheduled', 'starting_soon', 'live', 'completed', 'cancelled', 'postponed'];
const PROVIDERS = [
    { value: 'google_meet', label: 'Google Meet' },
    { value: 'zoom', label: 'Zoom' },
    { value: 'teams', label: 'Microsoft Teams' },
    { value: 'other', label: 'External link' },
];

const titleCase = (s) => String(s).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

/**
 * Admin class management: table / grid / calendar, filters mirrored into the
 * URL, server pagination, and drawer-based create / edit / reschedule / cancel.
 */
export class AdminClassesPage {
    constructor() {
        this.container = document.getElementById('learning-content');
        this.viewMode = localStorage.getItem('lms_admin_view_mode') || 'table';
        this.classes = [];
        this.meta = {};
        this.formData = null;
        this.filters = hydrateFilters({
            search: '', status: '', course_id: '', teacher_id: '', date: '', page: 1,
        });
    }

    async render() {
        if (!this.container) return;

        // View mode may be pinned by the URL so a shared link opens the same way.
        const params = new URLSearchParams(window.location.search);
        if (['table', 'grid', 'calendar'].includes(params.get('view'))) {
            this.viewMode = params.get('view');
        }

        this.renderShell();

        // Reference data and the list are independent requests.
        await Promise.all([this.loadFormData(), this.fetchClasses()]);

        // Deep params: ?action=create, or ?edit=<id> from the detail page.
        if (params.get('action') === 'create') this.openCreateDrawer();

        const editId = parseInt(params.get('edit'), 10);
        if (!Number.isNaN(editId)) {
            const target = this.classes.find((c) => c.id === editId);
            if (target) this.openEditDrawer(target);
            else this.loadAndEdit(editId);
        }
    }

    /* ------------------------------------------------------------------ shell */

    renderShell() {
        this.container.innerHTML = `
            <div class="space-y-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900 border border-slate-800 p-5 rounded-2xl shadow-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                            <span class="material-symbols-outlined text-xl">video_camera_front</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-100">Classes Management</h2>
                            <p class="text-xs text-slate-400">Schedules, instructor assignments and meeting rooms.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center bg-slate-950 p-1 rounded-xl border border-slate-800" role="tablist" aria-label="View mode">
                            ${[['table', 'table_rows', 'Table'], ['grid', 'grid_view', 'Grid'], ['calendar', 'calendar_month', 'Calendar']]
                                .map(([mode, icon, label]) => `
                                    <button type="button" role="tab" aria-selected="${this.viewMode === mode}" id="view-${mode}"
                                        class="p-2 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors ${this.viewMode === mode
                                            ? 'bg-indigo-600 text-white shadow'
                                            : 'text-slate-400 hover:text-slate-200'}">
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">${icon}</span>
                                        <span class="hidden sm:inline">${label}</span>
                                    </button>
                                `).join('')}
                        </div>

                        <button type="button" id="btn-create-class"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/25 transition-all">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">add</span> Schedule Class
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 bg-slate-900/60 p-4 rounded-xl border border-slate-800">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-base" aria-hidden="true">search</span>
                        <label for="filter-search" class="sr-only">Search classes</label>
                        <input type="search" id="filter-search" placeholder="Search class title…" value="${this.filters.search}"
                            class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl pl-9 pr-3 py-2 outline-none focus:border-indigo-500 transition-colors">
                    </div>

                    <label for="filter-status" class="sr-only">Filter by status</label>
                    <select id="filter-status" class="bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2 outline-none focus:border-indigo-500">
                        <option value="">All statuses</option>
                        ${STATUSES.map((s) => `<option value="${s}" ${this.filters.status === s ? 'selected' : ''}>${titleCase(s)}</option>`).join('')}
                    </select>

                    <label for="filter-course" class="sr-only">Filter by course</label>
                    <select id="filter-course" class="bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2 outline-none focus:border-indigo-500">
                        <option value="">All courses</option>
                    </select>

                    <label for="filter-teacher" class="sr-only">Filter by instructor</label>
                    <select id="filter-teacher" class="bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2 outline-none focus:border-indigo-500">
                        <option value="">All instructors</option>
                    </select>

                    <div class="flex items-center gap-2">
                        <label for="filter-date" class="sr-only">Filter by date</label>
                        <input type="date" id="filter-date" value="${this.filters.date}"
                            class="flex-1 bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2 outline-none focus:border-indigo-500">
                        <button type="button" id="filter-reset" title="Clear filters" aria-label="Clear filters"
                            class="p-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">filter_alt_off</span>
                        </button>
                    </div>
                </div>

                <div id="classes-display-area">${getTableSkeletonHTML(5)}</div>
                <div id="classes-pagination"></div>
            </div>
        `;

        this.bindEvents();
    }

    bindEvents() {
        ['table', 'grid', 'calendar'].forEach((mode) => {
            this.container.querySelector(`#view-${mode}`)?.addEventListener('click', () => {
                this.viewMode = mode;
                localStorage.setItem('lms_admin_view_mode', mode);
                writeQuery({ view: mode });
                this.renderShell();
                this.populateReferenceFilters();
                this.renderClassesContent();
            });
        });

        this.container.querySelector('#filter-search')?.addEventListener('input', debounce((e) => {
            this.filters.search = e.target.value;
            this.filters.page = 1;
            this.fetchClasses();
        }, 300));

        [['#filter-status', 'status'], ['#filter-course', 'course_id'],
         ['#filter-teacher', 'teacher_id'], ['#filter-date', 'date']]
            .forEach(([selector, key]) => {
                this.container.querySelector(selector)?.addEventListener('change', (e) => {
                    this.filters[key] = e.target.value;
                    this.filters.page = 1;
                    this.fetchClasses();
                });
            });

        this.container.querySelector('#filter-reset')?.addEventListener('click', () => {
            this.filters = { search: '', status: '', course_id: '', teacher_id: '', date: '', page: 1 };
            this.renderShell();
            this.populateReferenceFilters();
            this.fetchClasses();
        });

        this.container.querySelector('#btn-create-class')?.addEventListener('click', () => this.openCreateDrawer());

        // One delegated handler for every row/card/calendar action.
        this.container.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-action]');
            if (!trigger) return;

            const classId = parseInt(trigger.dataset.id, 10);
            const target = this.classes.find((c) => c.id === classId);
            if (!target) return;

            switch (trigger.dataset.action) {
                case 'view-details': this.openDetailsDrawer(target); break;
                case 'edit-class': this.openEditDrawer(target); break;
                case 'reschedule-class': this.openRescheduleDrawer(target); break;
                case 'cancel-class': this.handleCancelClass(target); break;
                case 'delete-class': this.handleDeleteClass(target); break;
                case 'join-class': this.handleJoin(target, trigger); break;
                default: break;
            }
        });
    }

    /* ------------------------------------------------------------------- data */

    async loadFormData() {
        try {
            this.formData = await classesApi.getFormData();
            this.populateReferenceFilters();
        } catch (err) {
            console.error('Failed to load reference data:', err);
        }
    }

    populateReferenceFilters() {
        if (!this.formData) return;

        const courseSelect = this.container.querySelector('#filter-course');
        if (courseSelect && this.formData.courses) {
            courseSelect.innerHTML = `<option value="">All courses</option>` +
                this.formData.courses.map((c) =>
                    `<option value="${c.id}" ${String(this.filters.course_id) === String(c.id) ? 'selected' : ''}>${c.title}</option>`
                ).join('');
        }

        const teacherSelect = this.container.querySelector('#filter-teacher');
        if (teacherSelect && this.formData.teachers) {
            teacherSelect.innerHTML = `<option value="">All instructors</option>` +
                this.formData.teachers.map((t) =>
                    `<option value="${t.id}" ${String(this.filters.teacher_id) === String(t.id) ? 'selected' : ''}>${t.name}</option>`
                ).join('');
        }
    }

    async fetchClasses() {
        const area = this.container.querySelector('#classes-display-area');
        if (area) {
            area.innerHTML = this.viewMode === 'grid' ? getCardsSkeletonHTML(6) : getTableSkeletonHTML(5);
        }

        writeQuery({ ...this.filters, view: this.viewMode });

        try {
            const response = await classesApi.getAdminClasses(this.filters);
            if (!response) return; // superseded by a newer request

            this.classes = response.data || [];
            this.meta = response.meta || {};
            this.renderClassesContent();
        } catch (err) {
            if (area) {
                area.innerHTML = `
                    <div class="p-8 text-center bg-rose-950/20 border border-rose-500/30 rounded-2xl">
                        <p class="text-xs text-rose-300 mb-4">Unable to load classes. ${err.message}</p>
                        <button id="classes-retry" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700">
                            Try again
                        </button>
                    </div>
                `;
                area.querySelector('#classes-retry')?.addEventListener('click', () => this.fetchClasses());
            }
        }
    }

    renderClassesContent() {
        const area = this.container.querySelector('#classes-display-area');
        const pager = this.container.querySelector('#classes-pagination');
        if (!area) return;

        if (this.classes.length === 0) {
            area.innerHTML = getEmptyStateHTML({
                title: 'No classes found',
                description: 'No online classes match your current filters.',
                actionButtonText: 'Schedule Class',
                actionButtonId: 'empty-btn-create',
            });
            area.querySelector('#empty-btn-create')?.addEventListener('click', () => this.openCreateDrawer());
            if (pager) pager.innerHTML = '';
            return;
        }

        if (this.viewMode === 'grid') {
            area.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    ${this.classes.map((c) => renderClassCard(c)).join('')}
                </div>
            `;
        } else if (this.viewMode === 'calendar') {
            area.innerHTML = renderClassCalendar(this.classes);
        } else {
            area.innerHTML = renderClassTable(this.classes);
        }

        if (pager) {
            pager.innerHTML = renderPagination(this.meta);
            bindPagination(pager, (page) => {
                this.filters.page = page;
                this.fetchClasses();
            });
        }
    }

    /**
     * Deep-link fallback: ?edit=<id> for a class that is not on this page.
     */
    async loadAndEdit(classId) {
        try {
            const res = await classesApi.getClass(classId);
            const cls = res?.data?.class;
            if (cls?.id) this.openEditDrawer(cls);
            else window.toast.error('That class could not be found.');
        } catch (err) {
            window.toast.error(err.message || 'That class could not be found.');
        }
    }

    /* ---------------------------------------------------------------- drawers */

    openDetailsDrawer(cls) {
        const drawer = new Drawer({
            title: cls.title,
            subtitle: `${cls.course?.title || 'Course'} • ${formatDate(cls.class_date)}`,
            size: 'md',
        });

        drawer.contentHTML = `
            <div class="space-y-6">
                <div class="flex items-center justify-between p-4 bg-slate-950 rounded-xl border border-slate-800">
                    <div>
                        <div class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Status</div>
                        <div class="mt-1">${getStatusBadgeHTML(cls.status)}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Provider</div>
                        <div class="mt-1">${getProviderBadgeHTML(cls.meeting_provider)}</div>
                    </div>
                </div>

                <dl class="grid grid-cols-2 gap-4 text-xs">
                    ${[
                        ['Instructor', cls.teacher?.name],
                        ['Batch', cls.batch?.batch_name || cls.batch?.name],
                        ['Module', cls.module?.name],
                        ['Start time', formatTime(cls.start_time)],
                        ['End time', formatTime(cls.end_time)],
                        ['Attendance', cls.is_attendance_required ? 'Required' : 'Not tracked'],
                    ].filter(([, v]) => v).map(([label, value]) => `
                        <div>
                            <dt class="text-slate-500">${label}</dt>
                            <dd class="font-semibold text-slate-200 mt-0.5">${value}</dd>
                        </div>
                    `).join('')}
                </dl>

                ${cls.description ? `
                    <div class="p-4 bg-slate-950/60 rounded-xl border border-slate-800/80">
                        <span class="text-[11px] font-semibold text-slate-400 block mb-1">Description</span>
                        <p class="text-xs text-slate-300 leading-relaxed">${cls.description}</p>
                    </div>
                ` : ''}

                ${cls.status === 'cancelled' && cls.cancelled_reason ? `
                    <div class="p-4 bg-rose-950/30 border border-rose-500/30 rounded-xl text-xs text-rose-200">
                        <span class="font-semibold">Cancelled:</span> ${cls.cancelled_reason}
                    </div>
                ` : ''}

                ${cls.meeting_url ? `
                    <div class="p-4 bg-indigo-950/20 border border-indigo-500/30 rounded-xl space-y-2">
                        <span class="text-xs font-bold text-indigo-300 block">Meeting room</span>
                        <div class="flex items-center gap-2">
                            <label for="drawer-meeting-url" class="sr-only">Meeting link</label>
                            <input type="text" id="drawer-meeting-url" readonly value="${cls.meeting_url}"
                                class="flex-1 bg-slate-900 border border-slate-700 text-xs px-3 py-1.5 rounded-lg text-slate-200 font-mono">
                            <a href="${cls.meeting_url}" target="_blank" rel="noopener noreferrer"
                               class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition-colors">Open</a>
                        </div>
                    </div>
                ` : ''}

                <div class="pt-4 border-t border-slate-800 grid grid-cols-2 gap-2">
                    <a href="${path('classes', cls.id)}" data-spa-link
                       class="col-span-2 py-2.5 text-center rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition-colors">
                        Open full detail
                    </a>
                    <button type="button" data-drawer-action="edit"
                        class="py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition-colors">Edit</button>
                    <button type="button" data-drawer-action="reschedule"
                        class="py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-300 text-xs font-semibold border border-slate-700 transition-colors">Reschedule</button>
                    ${cls.status !== 'cancelled' ? `
                        <button type="button" data-drawer-action="cancel"
                            class="col-span-2 py-2.5 rounded-xl bg-rose-950/40 hover:bg-rose-900/50 text-rose-300 text-xs font-semibold border border-rose-500/30 transition-colors">
                            Cancel class
                        </button>
                    ` : ''}
                </div>
            </div>
        `;

        drawer.render();

        drawer.element.querySelectorAll('[data-drawer-action]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const action = btn.dataset.drawerAction;
                drawer.close();
                if (action === 'edit') this.openEditDrawer(cls);
                if (action === 'reschedule') this.openRescheduleDrawer(cls);
                if (action === 'cancel') this.handleCancelClass(cls);
            });
        });
    }

    openCreateDrawer() {
        if (!this.formData) {
            window.toast.warning('Still loading courses and instructors — try again in a moment.');
            return;
        }

        const drawer = new Drawer({
            title: 'Schedule Online Class',
            subtitle: 'Conflicts are checked against the instructor and batch before saving.',
            size: 'lg',
        });

        drawer.contentHTML = this.classFormHTML(null);
        drawer.render();
        this.wireClassForm(drawer, null);
    }

    openEditDrawer(cls) {
        if (!this.formData) {
            window.toast.warning('Still loading courses and instructors — try again in a moment.');
            return;
        }

        const drawer = new Drawer({
            title: 'Edit Class',
            subtitle: `${cls.title} • #${cls.id}`,
            size: 'lg',
        });

        drawer.contentHTML = this.classFormHTML(cls);
        drawer.render();
        this.wireClassForm(drawer, cls);
    }

    /**
     * One form for create and edit so the two can never drift apart.
     */
    classFormHTML(cls) {
        const value = (key, fallback = '') => {
            if (!cls) return fallback;
            const v = cls[key];
            return v === null || v === undefined ? fallback : v;
        };

        const dateValue = cls?.class_date ? String(cls.class_date).substring(0, 10) : '';
        const timeValue = (t) => (t ? String(t).substring(0, 5) : '');
        const selectedProvider = value('meeting_provider', 'google_meet');

        return `
            <form id="class-form" class="space-y-5 text-xs" novalidate>
                <fieldset class="space-y-4">
                    <legend class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Basic information</legend>

                    <div class="space-y-1">
                        <label for="f-title" class="font-semibold text-slate-300">Class title *</label>
                        <input type="text" id="f-title" name="title" required value="${value('title')}"
                            placeholder="e.g. AutoCAD 2D Drafting — Session 4"
                            class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500">
                        <p class="field-error hidden text-[11px] text-rose-400" data-for="title"></p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="f-course" class="font-semibold text-slate-300">Course *</label>
                            <select id="f-course" name="course_id" required
                                class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500">
                                <option value="">Select course</option>
                                ${this.formData.courses.map((c) =>
                                    `<option value="${c.id}" ${String(value('course_id')) === String(c.id) ? 'selected' : ''}>${c.title}</option>`
                                ).join('')}
                            </select>
                            <p class="field-error hidden text-[11px] text-rose-400" data-for="course_id"></p>
                        </div>

                        <div class="space-y-1">
                            <label for="f-batch" class="font-semibold text-slate-300">Batch *</label>
                            <select id="f-batch" name="batch_id" required
                                class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500">
                                <option value="">Select batch</option>
                                ${this.formData.batches.map((b) =>
                                    `<option value="${b.id}" ${String(value('batch_id')) === String(b.id) ? 'selected' : ''}>${b.batch_name || b.name}</option>`
                                ).join('')}
                            </select>
                            <p class="field-error hidden text-[11px] text-rose-400" data-for="batch_id"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="f-teacher" class="font-semibold text-slate-300">Instructor *</label>
                            <select id="f-teacher" name="teacher_id" required
                                class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500">
                                <option value="">Select instructor</option>
                                ${this.formData.teachers.map((t) =>
                                    `<option value="${t.id}" ${String(value('teacher_id')) === String(t.id) ? 'selected' : ''}>${t.name}</option>`
                                ).join('')}
                            </select>
                            <p class="field-error hidden text-[11px] text-rose-400" data-for="teacher_id"></p>
                        </div>

                        <div class="space-y-1">
                            <label for="f-module" class="font-semibold text-slate-300">Module</label>
                            <select id="f-module" name="module_id"
                                class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500">
                                <option value="">No specific module</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label for="f-description" class="font-semibold text-slate-300">Description</label>
                        <textarea id="f-description" name="description" rows="3"
                            class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500 resize-y"
                            placeholder="What will this session cover?">${value('description')}</textarea>
                    </div>
                </fieldset>

                <fieldset class="space-y-4 pt-4 border-t border-slate-800">
                    <legend class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Schedule</legend>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="space-y-1">
                            <label for="f-date" class="font-semibold text-slate-300">Date *</label>
                            <input type="date" id="f-date" name="class_date" required value="${dateValue}"
                                class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2 outline-none focus:border-indigo-500">
                            <p class="field-error hidden text-[11px] text-rose-400" data-for="class_date"></p>
                        </div>
                        <div class="space-y-1">
                            <label for="f-start" class="font-semibold text-slate-300">Start *</label>
                            <input type="time" id="f-start" name="start_time" required value="${timeValue(value('start_time'))}"
                                class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2 outline-none focus:border-indigo-500">
                            <p class="field-error hidden text-[11px] text-rose-400" data-for="start_time"></p>
                        </div>
                        <div class="space-y-1">
                            <label for="f-end" class="font-semibold text-slate-300">End *</label>
                            <input type="time" id="f-end" name="end_time" required value="${timeValue(value('end_time'))}"
                                class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2 outline-none focus:border-indigo-500">
                            <p class="field-error hidden text-[11px] text-rose-400" data-for="end_time"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="f-status" class="font-semibold text-slate-300">Status *</label>
                            <select id="f-status" name="status" required
                                class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500">
                                ${STATUSES.map((s) =>
                                    `<option value="${s}" ${String(value('status', 'scheduled')) === s ? 'selected' : ''}>${titleCase(s)}</option>`
                                ).join('')}
                            </select>
                        </div>

                        <div class="flex items-end pb-1">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="f-attendance" name="is_attendance_required"
                                    ${cls ? (cls.is_attendance_required ? 'checked' : '') : 'checked'}
                                    class="w-4 h-4 rounded border-slate-700 bg-slate-950 text-indigo-600 focus:ring-indigo-500">
                                <span class="font-semibold text-slate-300">Track attendance</span>
                            </label>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="space-y-4 pt-4 border-t border-slate-800">
                    <legend class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Meeting</legend>

                    <div class="space-y-1">
                        <label for="f-provider" class="font-semibold text-slate-300">Provider *</label>
                        <select id="f-provider" name="meeting_provider" required
                            class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500">
                            ${PROVIDERS.map((p) =>
                                `<option value="${p.value}" ${selectedProvider === p.value ? 'selected' : ''}>${p.label}</option>`
                            ).join('')}
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label for="f-url" class="font-semibold text-slate-300">
                            <span id="url-label">Meeting room URL</span>
                        </label>
                        <input type="url" id="f-url" name="meeting_url" value="${value('meeting_url')}"
                            placeholder="https://meet.google.com/xyz-abc-def"
                            class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500">
                        <p class="field-error hidden text-[11px] text-rose-400" data-for="meeting_url"></p>
                    </div>

                    <!-- Only hosted providers issue an ID / passcode (§16) -->
                    <div id="hosted-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="f-meeting-id" class="font-semibold text-slate-300">Meeting ID</label>
                            <input type="text" id="f-meeting-id" name="meeting_id" value="${value('meeting_id')}"
                                class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500">
                        </div>
                        <div class="space-y-1">
                            <label for="f-meeting-password" class="font-semibold text-slate-300">Passcode</label>
                            <input type="text" id="f-meeting-password" name="meeting_password" value="${value('meeting_password')}"
                                class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500">
                        </div>
                    </div>
                </fieldset>

                <div id="form-error-alert" class="hidden p-3 bg-rose-950/40 border border-rose-500/40 rounded-xl text-rose-200"></div>

                <div class="pt-4 border-t border-slate-800">
                    <button type="submit" id="form-submit"
                        class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all">
                        ${cls ? 'Save changes' : 'Schedule online class'}
                    </button>
                </div>
            </form>
        `;
    }

    wireClassForm(drawer, cls) {
        const form = drawer.element.querySelector('#class-form');
        const alertBox = form.querySelector('#form-error-alert');
        const submitBtn = form.querySelector('#form-submit');
        const providerSelect = form.querySelector('#f-provider');
        const hostedFields = form.querySelector('#hosted-fields');
        const urlLabel = form.querySelector('#url-label');
        const courseSelect = form.querySelector('#f-course');
        const moduleSelect = form.querySelector('#f-module');

        // Smart fields: an external link has no meeting id or passcode.
        const syncProviderFields = () => {
            const external = providerSelect.value === 'other';
            hostedFields.classList.toggle('hidden', external);
            urlLabel.textContent = external ? 'External meeting link *' : 'Meeting room URL';
        };
        providerSelect.addEventListener('change', syncProviderFields);
        syncProviderFields();

        // Modules follow the chosen course, so an unrelated module cannot be picked.
        const syncModules = () => {
            const courseId = courseSelect.value;
            const modules = (this.formData.modules || []).filter((m) => String(m.course_id) === String(courseId));
            const selected = cls?.module_id;

            moduleSelect.innerHTML = `<option value="">No specific module</option>` +
                modules.map((m) => `<option value="${m.id}" ${String(selected) === String(m.id) ? 'selected' : ''}>${m.name}</option>`).join('');
            moduleSelect.disabled = modules.length === 0;
        };
        courseSelect.addEventListener('change', syncModules);
        syncModules();

        const showFieldError = (field, message) => {
            const el = form.querySelector(`.field-error[data-for="${field}"]`);
            if (!el) return;
            el.textContent = message || '';
            el.classList.toggle('hidden', !message);
        };
        const clearErrors = () => {
            form.querySelectorAll('.field-error').forEach((el) => {
                el.textContent = '';
                el.classList.add('hidden');
            });
            alertBox.classList.add('hidden');
        };

        // Unsaved-changes guard (§18).
        const snapshot = new FormData(form);
        const isDirty = () => {
            const now = new FormData(form);
            for (const [key, val] of now.entries()) {
                if (String(snapshot.get(key) ?? '') !== String(val)) return true;
            }
            return false;
        };

        const originalClose = drawer.close.bind(drawer);
        drawer.close = () => {
            if (isDirty() && !window.confirm('Unsaved changes will be lost. Close this form?')) return;
            originalClose();
        };

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const data = Object.fromEntries(new FormData(form));
            data.is_attendance_required = form.querySelector('#f-attendance').checked ? 1 : 0;

            // Cheap client checks for feel; Laravel still validates (§17).
            if (data.start_time && data.end_time && data.end_time <= data.start_time) {
                showFieldError('end_time', 'The end time must be after the start time.');
                form.querySelector('#f-end').focus();
                return;
            }
            if (providerSelect.value === 'other' && !data.meeting_url) {
                showFieldError('meeting_url', 'An external link needs a meeting URL.');
                form.querySelector('#f-url').focus();
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving…';

            try {
                const res = cls
                    ? await classesApi.updateClass(cls.id, data)
                    : await classesApi.createClass(data);

                window.toast.success(res.message || (cls ? 'Class updated.' : 'Class scheduled.'));

                drawer.close = originalClose;
                drawer.close();
                this.fetchClasses();
            } catch (err) {
                Object.entries(err.errors || {}).forEach(([field, messages]) => {
                    showFieldError(field, Array.isArray(messages) ? messages[0] : messages);
                });

                // A conflict comes back as a message, not a field error.
                if (Object.keys(err.errors || {}).length === 0) {
                    alertBox.textContent = err.message || 'Unable to save the class.';
                    alertBox.classList.remove('hidden');
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = cls ? 'Save changes' : 'Schedule online class';
            }
        });
    }

    /**
     * Reschedule is its own drawer (§20): it shows the current slot beside the
     * new one, and the server re-runs conflict detection before committing.
     */
    openRescheduleDrawer(cls) {
        const drawer = new Drawer({
            title: 'Reschedule Class',
            subtitle: cls.title,
            size: 'md',
        });

        const dateValue = cls.class_date ? String(cls.class_date).substring(0, 10) : '';
        const timeValue = (t) => (t ? String(t).substring(0, 5) : '');

        drawer.contentHTML = `
            <form id="reschedule-form" class="space-y-5 text-xs" novalidate>
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-4 bg-slate-950 border border-slate-800 rounded-xl">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Current</div>
                        <p class="font-bold text-slate-200">${formatDate(cls.class_date)}</p>
                        <p class="text-[11px] text-slate-400">${formatTime(cls.start_time)} – ${formatTime(cls.end_time)}</p>
                    </div>
                    <div class="p-4 bg-indigo-950/25 border border-indigo-500/30 rounded-xl">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-400 mb-1">New</div>
                        <p class="font-bold text-slate-200" id="preview-date">—</p>
                        <p class="text-[11px] text-slate-400" id="preview-time">—</p>
                    </div>
                </div>

                <div class="space-y-1">
                    <label for="r-date" class="font-semibold text-slate-300">New date *</label>
                    <input type="date" id="r-date" name="class_date" required value="${dateValue}"
                        class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500">
                    <p class="field-error hidden text-[11px] text-rose-400" data-for="class_date"></p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label for="r-start" class="font-semibold text-slate-300">Start *</label>
                        <input type="time" id="r-start" name="start_time" required value="${timeValue(cls.start_time)}"
                            class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 outline-none focus:border-indigo-500">
                        <p class="field-error hidden text-[11px] text-rose-400" data-for="start_time"></p>
                    </div>
                    <div class="space-y-1">
                        <label for="r-end" class="font-semibold text-slate-300">End *</label>
                        <input type="time" id="r-end" name="end_time" required value="${timeValue(cls.end_time)}"
                            class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 outline-none focus:border-indigo-500">
                        <p class="field-error hidden text-[11px] text-rose-400" data-for="end_time"></p>
                    </div>
                </div>

                <div class="space-y-1">
                    <label for="r-reason" class="font-semibold text-slate-300">Reason (sent to students)</label>
                    <textarea id="r-reason" name="reason" rows="3"
                        class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500 resize-y"
                        placeholder="Why is this session moving?"></textarea>
                </div>

                <div id="conflict-alert" class="hidden p-3 bg-amber-950/40 border border-amber-500/40 rounded-xl text-amber-200 space-y-1"></div>

                <div class="pt-4 border-t border-slate-800">
                    <button type="submit" id="reschedule-submit"
                        class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all">
                        Check conflicts &amp; reschedule
                    </button>
                </div>
            </form>
        `;

        drawer.render();

        const form = drawer.element.querySelector('#reschedule-form');
        const dateInput = form.querySelector('#r-date');
        const startInput = form.querySelector('#r-start');
        const endInput = form.querySelector('#r-end');
        const conflictBox = form.querySelector('#conflict-alert');
        const submitBtn = form.querySelector('#reschedule-submit');

        const updatePreview = () => {
            form.querySelector('#preview-date').textContent = dateInput.value ? formatDate(dateInput.value) : '—';
            form.querySelector('#preview-time').textContent =
                startInput.value && endInput.value ? `${formatTime(startInput.value)} – ${formatTime(endInput.value)}` : '—';
        };
        [dateInput, startInput, endInput].forEach((el) => el.addEventListener('change', updatePreview));
        updatePreview();

        const showFieldError = (field, message) => {
            const el = form.querySelector(`.field-error[data-for="${field}"]`);
            if (!el) return;
            el.textContent = message || '';
            el.classList.toggle('hidden', !message);
        };

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            conflictBox.classList.add('hidden');
            form.querySelectorAll('.field-error').forEach((el) => el.classList.add('hidden'));

            if (endInput.value <= startInput.value) {
                showFieldError('end_time', 'The end time must be after the start time.');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Checking…';

            try {
                const res = await classesApi.rescheduleClass(cls.id, {
                    class_date: dateInput.value,
                    start_time: startInput.value,
                    end_time: endInput.value,
                    reason: form.querySelector('#r-reason').value || null,
                });

                window.toast.success(res.message || 'Class rescheduled.');
                drawer.close();
                this.fetchClasses();
            } catch (err) {
                // The API returns 422 with a `conflicts` array when the slot clashes.
                const conflicts = err.conflicts || [];
                if (conflicts.length > 0) {
                    conflictBox.innerHTML = `
                        <p class="font-bold">Scheduling conflict</p>
                        ${conflicts.map((c) => `<p>• ${c.message || c}</p>`).join('')}
                    `;
                    conflictBox.classList.remove('hidden');
                } else {
                    Object.entries(err.errors || {}).forEach(([field, messages]) => {
                        showFieldError(field, Array.isArray(messages) ? messages[0] : messages);
                    });

                    if (Object.keys(err.errors || {}).length === 0) {
                        conflictBox.innerHTML = `<p>${err.message || 'Unable to reschedule this class.'}</p>`;
                        conflictBox.classList.remove('hidden');
                    }
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Check conflicts & reschedule';
            }
        });
    }

    /**
     * Cancelling asks for a reason, which is what students are notified with (§19).
     */
    handleCancelClass(cls) {
        const drawer = new Drawer({
            title: 'Cancel Class',
            subtitle: cls.title,
            size: 'sm',
        });

        drawer.contentHTML = `
            <form id="cancel-form" class="space-y-5 text-xs">
                <p class="text-slate-300 leading-relaxed">
                    Students in <span class="font-semibold text-slate-100">${cls.batch?.batch_name || cls.batch?.name || 'this batch'}</span>
                    will be notified that this session is cancelled.
                </p>

                <div class="space-y-1">
                    <label for="c-reason" class="font-semibold text-slate-300">Reason *</label>
                    <textarea id="c-reason" name="reason" rows="4" required
                        class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500 resize-y"
                        placeholder="e.g. Instructor unavailable — will be rescheduled this week."></textarea>
                    <p class="field-error hidden text-[11px] text-rose-400" data-for="reason"></p>
                </div>

                <div class="flex items-center gap-3 pt-4 border-t border-slate-800">
                    <button type="button" id="keep-class"
                        class="flex-1 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold transition-colors">
                        Keep class
                    </button>
                    <button type="submit" id="confirm-cancel"
                        class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold shadow-lg shadow-rose-600/25 transition-all">
                        Cancel class
                    </button>
                </div>
            </form>
        `;

        drawer.render();

        const form = drawer.element.querySelector('#cancel-form');
        const reason = form.querySelector('#c-reason');
        const errorEl = form.querySelector('.field-error[data-for="reason"]');
        const submitBtn = form.querySelector('#confirm-cancel');

        form.querySelector('#keep-class').addEventListener('click', () => drawer.close());

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorEl.classList.add('hidden');

            if (!reason.value.trim()) {
                errorEl.textContent = 'Give a reason so students understand why.';
                errorEl.classList.remove('hidden');
                reason.focus();
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Cancelling…';

            try {
                const res = await classesApi.cancelClass(cls.id, reason.value.trim());
                window.toast.success(res.message || 'Class cancelled.');
                drawer.close();
                this.fetchClasses();
            } catch (err) {
                errorEl.textContent = err.message || 'Unable to cancel this class.';
                errorEl.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Cancel class';
            }
        });
    }

    async handleDeleteClass(cls) {
        const confirmed = await confirmAction({
            title: 'Delete this class?',
            message: `"${cls.title}" and its attendance records will be removed permanently. To keep the record but call it off, cancel the class instead.`,
            confirmText: 'Delete permanently',
            isDangerous: true,
        });

        if (!confirmed) return;

        try {
            await classesApi.deleteClass(cls.id);
            window.toast.success('Class deleted.');
            this.fetchClasses();
        } catch (err) {
            window.toast.error(err.message || 'Unable to delete the class.');
        }
    }

    async handleJoin(cls, btn) {
        btn.disabled = true;

        try {
            const res = await classesApi.joinClass(cls.id);
            if (res?.meeting_url) {
                window.open(res.meeting_url, '_blank', 'noopener');
            } else {
                window.toast.warning(res?.message || 'No meeting link is set for this class.');
            }
        } catch (err) {
            window.toast.error(err.message || 'Unable to open this class.');
        } finally {
            btn.disabled = false;
        }
    }
}
