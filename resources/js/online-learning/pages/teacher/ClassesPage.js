import { classesApi } from '../../api/classes.js';
import { attendanceApi } from '../../api/attendance.js';
import { getCardsSkeletonHTML } from '../../components/LoadingState.js';
import { getEmptyStateHTML } from '../../components/EmptyState.js';
import { renderPagination, bindPagination } from '../../components/Pagination.js';
import { renderAttendanceTable } from '../../components/AttendanceTable.js';
import { Drawer } from '../../components/Drawer.js';
import { debounce } from '../../utils/debounce.js';
import { formatDate, formatTime } from '../../utils/date.js';
import { getStatusBadgeHTML } from '../../utils/formatter.js';
import { hydrateFilters, writeQuery } from '../../utils/urlState.js';
import { path } from '../../utils/permissions.js';

/**
 * Teacher class list (§23): big obvious actions, no admin controls.
 * Join / Attendance / Details — nothing else competing for attention.
 */
export class TeacherClassesPage {
    constructor() {
        this.container = document.getElementById('learning-content');
        this.filters = hydrateFilters({ search: '', status: '', date: '', page: 1 });
        this.classes = [];
        this.meta = {};
    }

    async render() {
        if (!this.container) return;
        this.paintShell();
        await this.fetch();
    }

    paintShell() {
        this.container.innerHTML = `
            <div class="space-y-6">
                <header class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                            <span class="material-symbols-outlined text-xl">videocam</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-100">My Classes</h2>
                            <p class="text-xs text-slate-400">Every session you are hosting.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full lg:w-auto">
                        <div class="relative sm:w-48">
                            <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-base" aria-hidden="true">search</span>
                            <label for="tc-search" class="sr-only">Search classes</label>
                            <input type="search" id="tc-search" value="${this.filters.search}" placeholder="Search…"
                                class="w-full bg-slate-900 border border-slate-800 text-slate-200 text-xs rounded-xl pl-9 pr-3 py-2.5 outline-none focus:border-indigo-500">
                        </div>

                        <label for="tc-status" class="sr-only">Filter by status</label>
                        <select id="tc-status" class="bg-slate-900 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 outline-none focus:border-indigo-500">
                            ${['', 'scheduled', 'starting_soon', 'live', 'completed', 'cancelled', 'postponed'].map((s) => `
                                <option value="${s}" ${this.filters.status === s ? 'selected' : ''}>
                                    ${s === '' ? 'All statuses' : s.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())}
                                </option>
                            `).join('')}
                        </select>

                        <label for="tc-date" class="sr-only">Filter by date</label>
                        <input type="date" id="tc-date" value="${this.filters.date}"
                            class="bg-slate-900 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 outline-none focus:border-indigo-500">
                    </div>
                </header>

                <div id="tc-list">${getCardsSkeletonHTML(6)}</div>
                <div id="tc-pagination"></div>
            </div>
        `;

        this.container.querySelector('#tc-search').addEventListener('input', debounce((e) => {
            this.filters.search = e.target.value;
            this.filters.page = 1;
            this.fetch();
        }, 350));

        this.container.querySelector('#tc-status').addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.filters.page = 1;
            this.fetch();
        });

        this.container.querySelector('#tc-date').addEventListener('change', (e) => {
            this.filters.date = e.target.value;
            this.filters.page = 1;
            this.fetch();
        });
    }

    async fetch() {
        const list = this.container.querySelector('#tc-list');
        if (list) list.innerHTML = getCardsSkeletonHTML(6);

        writeQuery(this.filters);

        try {
            const res = await classesApi.getTeacherClasses(this.filters);
            if (!res) return;

            this.classes = res.data || [];
            this.meta = res.meta || {};
            this.paintList();
        } catch (err) {
            if (list) {
                list.innerHTML = `
                    <div class="p-8 text-center bg-rose-950/20 border border-rose-500/30 rounded-2xl">
                        <p class="text-xs text-rose-300 mb-4">Unable to load your classes. ${err.message}</p>
                        <button id="tc-retry" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700">Try again</button>
                    </div>
                `;
                list.querySelector('#tc-retry')?.addEventListener('click', () => this.fetch());
            }
        }
    }

    paintList() {
        const list = this.container.querySelector('#tc-list');
        const pager = this.container.querySelector('#tc-pagination');

        if (this.classes.length === 0) {
            list.innerHTML = getEmptyStateHTML({
                icon: 'videocam_off',
                title: 'No classes found',
                description: 'No sessions match these filters. Your scheduled classes will appear here.',
            });
            pager.innerHTML = '';
            return;
        }

        list.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                ${this.classes.map((c) => this.card(c)).join('')}
            </div>
        `;

        list.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-action]');
            if (!trigger) return;

            const id = parseInt(trigger.dataset.id, 10);
            const cls = this.classes.find((c) => c.id === id);
            if (!cls) return;

            if (trigger.dataset.action === 'join') this.handleJoin(cls, trigger);
            if (trigger.dataset.action === 'attendance') this.openAttendanceDrawer(cls);
        });

        pager.innerHTML = renderPagination(this.meta);
        bindPagination(pager, (page) => {
            this.filters.page = page;
            this.fetch();
        });
    }

    card(cls) {
        const joinable = ['scheduled', 'starting_soon', 'live'].includes(cls.status);

        return `
            <article class="bg-slate-900 border ${cls.status === 'live' ? 'border-emerald-500/40' : 'border-slate-800'} rounded-2xl p-5 flex flex-col justify-between gap-4 hover:border-slate-700 transition-colors shadow-lg">
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-400">
                            ${cls.course?.title || 'Course'}
                        </span>
                        ${getStatusBadgeHTML(cls.status)}
                    </div>

                    <h3 class="text-sm font-bold text-slate-100 leading-snug line-clamp-2">${cls.title}</h3>

                    <div class="space-y-1.5 text-[11px] text-slate-400">
                        <p class="inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">event</span>
                            ${formatDate(cls.class_date)} • ${formatTime(cls.start_time)} – ${formatTime(cls.end_time)}
                        </p>
                        ${cls.batch ? `
                            <p class="inline-flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">groups</span>
                                ${cls.batch.batch_name || cls.batch.name}
                            </p>
                        ` : ''}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-3 border-t border-slate-800">
                    ${joinable ? `
                        <button type="button" data-action="join" data-id="${cls.id}"
                            class="col-span-2 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/20 transition-all">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base">videocam</span> Join class
                            </span>
                        </button>
                    ` : ''}

                    <button type="button" data-action="attendance" data-id="${cls.id}"
                        class="py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-indigo-300 text-[11px] font-semibold border border-slate-700 transition-colors">
                        Attendance
                    </button>

                    <a href="${path('classes', cls.id)}" data-spa-link
                       class="py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-semibold border border-slate-700 transition-colors text-center">
                        Details
                    </a>
                </div>
            </article>
        `;
    }

    async handleJoin(cls, btn) {
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="text-xs">Opening…</span>';

        try {
            const res = await classesApi.joinClass(cls.id);
            if (res?.success && res.meeting_url) {
                window.toast.success(res.message || 'Opening the meeting room…');
                window.open(res.meeting_url, '_blank', 'noopener');
                this.fetch(); // joining as host can flip the class to live
            } else {
                window.toast.warning(res?.message || 'No meeting link is set for this class.');
            }
        } catch (err) {
            window.toast.error(err.message || 'Unable to open this class.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    }

    async openAttendanceDrawer(cls) {
        const drawer = new Drawer({
            title: 'Attendance',
            subtitle: `${cls.title} • ${formatDate(cls.class_date)}`,
            size: 'lg',
        });

        drawer.contentHTML = `<div class="py-12 text-center text-xs text-slate-400">Loading the roster…</div>`;
        drawer.render();

        try {
            const res = await attendanceApi.getClassAttendance(cls.id);
            const data = res.data || {};
            const students = data.students || [];

            if (students.length === 0) {
                drawer.setContent(getEmptyStateHTML({
                    icon: 'group_off',
                    title: 'No students to mark',
                    description: 'No active enrollments were found for this course and batch.',
                }));
                return;
            }

            drawer.setContent(`
                ${renderAttendanceTable(students, data.stats || {})}
                <div class="pt-5 mt-5 border-t border-slate-800">
                    <button type="button" id="save-attendance"
                        class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition-all">
                        Save attendance
                    </button>
                </div>
            `);

            const body = drawer.element;

            body.querySelector('#mark-all-present')?.addEventListener('click', () => {
                body.querySelectorAll('.status-select').forEach((s) => { s.value = 'present'; });
            });
            body.querySelector('#mark-all-absent')?.addEventListener('click', () => {
                body.querySelectorAll('.status-select').forEach((s) => { s.value = 'absent'; });
            });

            const saveBtn = body.querySelector('#save-attendance');
            saveBtn.addEventListener('click', async () => {
                const records = [...body.querySelectorAll('.attendance-row')].map((row) => ({
                    student_id: parseInt(row.dataset.studentId, 10),
                    status: row.querySelector('.status-select').value,
                    notes: row.querySelector('.notes-input').value || null,
                }));

                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving…';

                try {
                    const saved = await attendanceApi.markAttendance(cls.id, records);
                    window.toast.success(saved.message || 'Attendance saved.');
                    drawer.close();
                } catch (err) {
                    window.toast.error(err.message || 'Unable to save attendance.');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save attendance';
                }
            });
        } catch (err) {
            drawer.setContent(`
                <div class="p-6 text-center text-xs text-rose-300 bg-rose-950/20 border border-rose-500/30 rounded-xl">
                    ${err.message || 'Unable to load the roster.'}
                </div>
            `);
        }
    }
}
