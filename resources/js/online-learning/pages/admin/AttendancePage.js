import { adminLearningApi } from '../../api/adminLearning.js';
import { attendanceApi } from '../../api/attendance.js';
import { classesApi } from '../../api/classes.js';
import { getTableSkeletonHTML } from '../../components/LoadingState.js';
import { getEmptyStateHTML } from '../../components/EmptyState.js';
import { renderPagination, bindPagination } from '../../components/Pagination.js';
import { renderAttendanceTable } from '../../components/AttendanceTable.js';
import { renderProgressBar } from '../../components/ProgressBar.js';
import { Drawer } from '../../components/Drawer.js';
import { debounce } from '../../utils/debounce.js';
import { formatDate, formatTime } from '../../utils/date.js';
import { getStatusBadgeHTML } from '../../utils/formatter.js';
import { hydrateFilters, writeQuery } from '../../utils/urlState.js';

/**
 * Admin attendance log: one row per class with its roll-up, so unmarked
 * classes are easy to spot. Marking happens in a drawer without leaving.
 */
export class AdminAttendancePage {
    constructor() {
        this.container = document.getElementById('learning-content');
        this.filters = hydrateFilters({
            search: '', course_id: '', teacher_id: '', marked: '', from: '', to: '', page: 1,
        });
        this.rows = [];
        this.meta = {};
        this.formData = null;
    }

    async render() {
        if (!this.container) return;

        this.paintShell();
        // Reference data and the list are independent - do not serialise them.
        await Promise.all([this.loadFormData(), this.fetch()]);
    }

    paintShell() {
        this.container.innerHTML = `
            <div class="space-y-6">
                <header class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                        <span class="material-symbols-outlined text-xl">how_to_reg</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-100">Attendance Log</h2>
                        <p class="text-xs text-slate-400">Attendance per class, and which sessions are still unmarked.</p>
                    </div>
                </header>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 bg-slate-900/60 p-4 rounded-xl border border-slate-800">
                    <div class="relative lg:col-span-2">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-base" aria-hidden="true">search</span>
                        <label for="att-search" class="sr-only">Search classes</label>
                        <input type="search" id="att-search" value="${this.filters.search}" placeholder="Search class title…"
                            class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl pl-9 pr-3 py-2 outline-none focus:border-indigo-500">
                    </div>

                    <label for="att-course" class="sr-only">Filter by course</label>
                    <select id="att-course" class="bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2 outline-none focus:border-indigo-500">
                        <option value="">All courses</option>
                    </select>

                    <label for="att-marked" class="sr-only">Filter by marked state</label>
                    <select id="att-marked" class="bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2 outline-none focus:border-indigo-500">
                        <option value="" ${this.filters.marked === '' ? 'selected' : ''}>Marked &amp; unmarked</option>
                        <option value="no" ${this.filters.marked === 'no' ? 'selected' : ''}>Unmarked only</option>
                        <option value="yes" ${this.filters.marked === 'yes' ? 'selected' : ''}>Marked only</option>
                    </select>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label for="att-from" class="sr-only">From date</label>
                            <input type="date" id="att-from" value="${this.filters.from}"
                                class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-2 py-2 outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label for="att-to" class="sr-only">To date</label>
                            <input type="date" id="att-to" value="${this.filters.to}"
                                class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-2 py-2 outline-none focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                <div id="att-list">${getTableSkeletonHTML(6)}</div>
                <div id="att-pagination"></div>
            </div>
        `;

        this.container.querySelector('#att-search').addEventListener('input', debounce((e) => {
            this.filters.search = e.target.value;
            this.filters.page = 1;
            this.fetch();
        }, 350));

        [['#att-course', 'course_id'], ['#att-marked', 'marked'], ['#att-from', 'from'], ['#att-to', 'to']]
            .forEach(([selector, key]) => {
                this.container.querySelector(selector).addEventListener('change', (e) => {
                    this.filters[key] = e.target.value;
                    this.filters.page = 1;
                    this.fetch();
                });
            });
    }

    async loadFormData() {
        try {
            const res = await classesApi.getFormData();
            this.formData = res;

            const select = this.container.querySelector('#att-course');
            if (select && res?.courses) {
                select.innerHTML = `<option value="">All courses</option>` +
                    res.courses.map((c) =>
                        `<option value="${c.id}" ${String(this.filters.course_id) === String(c.id) ? 'selected' : ''}>${c.title}</option>`
                    ).join('');
            }
        } catch {
            // The course filter simply stays empty; the list still works.
        }
    }

    async fetch() {
        const list = this.container.querySelector('#att-list');
        if (list) list.innerHTML = getTableSkeletonHTML(6);

        writeQuery(this.filters);

        try {
            const res = await adminLearningApi.attendanceLog(this.filters);
            if (!res) return;

            this.rows = res.data || [];
            this.meta = res.meta || {};
            this.paintList();
        } catch (err) {
            if (list) {
                list.innerHTML = `
                    <div class="p-8 text-center bg-rose-950/20 border border-rose-500/30 rounded-2xl">
                        <p class="text-xs text-rose-300 mb-4">Unable to load the attendance log. ${err.message}</p>
                        <button id="att-retry" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700">Try again</button>
                    </div>
                `;
                list.querySelector('#att-retry')?.addEventListener('click', () => this.fetch());
            }
        }
    }

    paintList() {
        const list = this.container.querySelector('#att-list');
        const pager = this.container.querySelector('#att-pagination');

        if (this.rows.length === 0) {
            list.innerHTML = getEmptyStateHTML({
                icon: 'fact_check',
                title: 'No classes found',
                description: 'No classes match these filters. Widen the date range or clear the search.',
            });
            pager.innerHTML = '';
            return;
        }

        list.innerHTML = `
            <!-- Desktop table -->
            <div class="hidden md:block bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead>
                            <tr class="border-b border-slate-800 bg-slate-950/60 text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
                                <th scope="col" class="py-3.5 px-4">Class</th>
                                <th scope="col" class="py-3.5 px-4">Date &amp; time</th>
                                <th scope="col" class="py-3.5 px-4">Instructor</th>
                                <th scope="col" class="py-3.5 px-4 w-48">Attendance</th>
                                <th scope="col" class="py-3.5 px-4">Status</th>
                                <th scope="col" class="py-3.5 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            ${this.rows.map((row) => this.tableRow(row)).join('')}
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile cards (§38: no wide tables on small screens) -->
            <div class="md:hidden space-y-3">
                ${this.rows.map((row) => this.mobileCard(row)).join('')}
            </div>
        `;

        list.querySelectorAll('[data-mark]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.mark, 10);
                const row = this.rows.find((r) => r.id === id);
                if (row) this.openMarkDrawer(row);
            });
        });

        pager.innerHTML = renderPagination(this.meta);
        bindPagination(pager, (page) => {
            this.filters.page = page;
            this.fetch();
        });
    }

    /**
     * Present + late over the number of rows actually marked.
     */
    rate(row) {
        const total = row.attendance_total || 0;
        if (total === 0) return null;
        return Math.round((((row.present_count || 0) + (row.late_count || 0)) / total) * 100);
    }

    tableRow(row) {
        const rate = this.rate(row);

        return `
            <tr class="hover:bg-slate-800/40 transition-colors">
                <td class="py-3.5 px-4">
                    <div class="font-bold text-slate-100">${row.title}</div>
                    <div class="text-[11px] text-slate-400">
                        ${row.course?.title || 'Course'}${row.batch ? ` • ${row.batch.batch_name || row.batch.name}` : ''}
                    </div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                    <div>${formatDate(row.class_date)}</div>
                    <div class="text-[11px] text-slate-400">${formatTime(row.start_time)} – ${formatTime(row.end_time)}</div>
                </td>
                <td class="py-3.5 px-4">${row.teacher?.name || '—'}</td>
                <td class="py-3.5 px-4">
                    ${rate === null
                        ? `<span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                               <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Not marked
                           </span>`
                        : `${renderProgressBar(rate, { size: 'sm', showValue: true, label: '' })}
                           <div class="mt-1 text-[10px] text-slate-500">
                               ${row.present_count || 0}P · ${row.late_count || 0}L · ${row.absent_count || 0}A
                           </div>`}
                </td>
                <td class="py-3.5 px-4">${getStatusBadgeHTML(row.status)}</td>
                <td class="py-3.5 px-4 text-right">
                    <button type="button" data-mark="${row.id}"
                        class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-indigo-300 text-[11px] font-semibold border border-slate-700 transition-colors">
                        ${rate === null ? 'Mark' : 'Review'}
                    </button>
                </td>
            </tr>
        `;
    }

    mobileCard(row) {
        const rate = this.rate(row);

        return `
            <article class="bg-slate-900 border border-slate-800 rounded-2xl p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-xs font-bold text-slate-100 truncate">${row.title}</h3>
                        <p class="text-[11px] text-slate-400">${row.course?.title || 'Course'}</p>
                    </div>
                    ${getStatusBadgeHTML(row.status)}
                </div>

                <p class="text-[11px] text-slate-400">
                    ${formatDate(row.class_date)} • ${formatTime(row.start_time)} • ${row.teacher?.name || '—'}
                </p>

                ${rate === null
                    ? `<p class="text-[11px] font-semibold text-amber-400">Attendance not marked</p>`
                    : renderProgressBar(rate, { size: 'sm', label: 'Attendance' })}

                <button type="button" data-mark="${row.id}"
                    class="w-full py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-indigo-300 text-[11px] font-semibold border border-slate-700 transition-colors">
                    ${rate === null ? 'Mark attendance' : 'Review attendance'}
                </button>
            </article>
        `;
    }

    async openMarkDrawer(row) {
        const drawer = new Drawer({
            title: 'Attendance',
            subtitle: `${row.title} • ${formatDate(row.class_date)}`,
            size: 'lg',
        });

        drawer.contentHTML = `<div class="py-12 text-center text-xs text-slate-400">Loading the roster…</div>`;
        drawer.render();

        try {
            const res = await attendanceApi.getClassAttendance(row.id);
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
                const records = [...body.querySelectorAll('.attendance-row')].map((r) => ({
                    student_id: parseInt(r.dataset.studentId, 10),
                    status: r.querySelector('.status-select').value,
                    notes: r.querySelector('.notes-input').value || null,
                }));

                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving…';

                try {
                    const saved = await attendanceApi.markAttendance(row.id, records);
                    window.toast.success(saved.message || 'Attendance saved.');
                    drawer.close();
                    this.fetch();
                } catch (err) {
                    window.toast.error(err.message || 'Unable to save attendance.');
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
