import { classDetailApi, roomApi } from '../../api/learning.js';
import { classesApi } from '../../api/classes.js';
import { attendanceApi } from '../../api/attendance.js';
import { Drawer } from '../../components/Drawer.js';
import { renderAttendanceTable } from '../../components/AttendanceTable.js';
import { getEmptyStateHTML } from '../../components/EmptyState.js';
import { formatDate, formatTime } from '../../utils/date.js';
import { getStatusBadgeHTML, getProviderBadgeHTML } from '../../utils/formatter.js';
import { path, isAdmin, isTeacher } from '../../utils/permissions.js';

/**
 * Full-page class detail for staff (teacher and admin).
 *
 * Admins also reach this content through the drawer on the class list; this
 * page is what a deep link such as /admin/online-learning/classes/12 resolves
 * to, so both entry points show the same data (§14, §55).
 */
export class StaffClassDetailPage {
    constructor() {
        this.container = document.getElementById('learning-content');
        this.classId = null;
        this.payload = null;
    }

    async render(classId) {
        if (!this.container) return;
        this.classId = classId;

        this.container.innerHTML = this.skeleton();

        try {
            const res = await classDetailApi.get(classId);
            if (!res) return;

            this.payload = res.data || {};
            this.paint();
        } catch (err) {
            this.container.innerHTML = `
                <div class="max-w-lg mx-auto mt-10">
                    ${getEmptyStateHTML({
                        icon: /permission|access/i.test(err.message || '') ? 'lock' : 'error',
                        title: 'Unable to load this class',
                        description: err.message || 'Something went wrong.',
                        actionButtonText: 'Try again',
                        actionButtonId: 'staff-retry',
                    })}
                    <div class="text-center mt-4">
                        <a href="${path('classes')}" data-spa-link class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">
                            Back to classes
                        </a>
                    </div>
                </div>
            `;
            this.container.querySelector('#staff-retry')?.addEventListener('click', () => this.render(classId));
        }
    }

    paint() {
        const cls = this.payload.class || {};
        const stats = this.payload.attendance_stats || {};
        const recordings = this.payload.recordings || [];
        const materials = this.payload.materials || [];
        const assignments = this.payload.assignments || [];
        const meetingUrl = this.payload.meeting_url;

        this.container.innerHTML = `
            <div class="space-y-6 max-w-6xl mx-auto pb-8">
                <a href="${path('classes')}" data-spa-link
                   class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400 hover:text-indigo-400 transition-colors">
                    <span class="material-symbols-outlined text-base">arrow_back</span> All classes
                </a>

                <header class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-5">
                        <div class="space-y-2 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                ${getStatusBadgeHTML(cls.status)}
                                ${cls.meeting_provider ? getProviderBadgeHTML(cls.meeting_provider) : ''}
                                <span class="text-[11px] font-mono text-slate-500">#${cls.id}</span>
                            </div>
                            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">${cls.title || 'Class'}</h1>
                            <p class="text-xs text-slate-400">
                                ${cls.course?.title || 'Course'}
                                ${cls.module?.name ? ` • ${cls.module.name}` : ''}
                                ${cls.batch ? ` • ${cls.batch.batch_name || cls.batch.name}` : ''}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" id="btn-open-room" ${meetingUrl ? '' : 'disabled'}
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold transition-all ${meetingUrl
                                    ? 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white shadow-lg shadow-emerald-600/20'
                                    : 'bg-slate-800 text-slate-500 border border-slate-700 cursor-not-allowed'}">
                                <span class="material-symbols-outlined text-base">videocam</span> Open room
                            </button>

                            <button type="button" id="btn-room"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/20 transition-all">
                                <span class="material-symbols-outlined text-base">meeting_room</span> Class room
                            </button>

                            <button type="button" id="btn-attendance"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-indigo-300 text-xs font-semibold border border-slate-700 transition-colors">
                                <span class="material-symbols-outlined text-base">how_to_reg</span> Attendance
                            </button>

                            ${isTeacher() && cls.status !== 'completed' && cls.status !== 'cancelled' ? `
                                <button type="button" id="btn-complete"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition-colors">
                                    <span class="material-symbols-outlined text-base">task_alt</span> Mark completed
                                </button>
                            ` : ''}

                            ${isAdmin() ? `
                                <a href="${path('classes')}?edit=${cls.id}" data-spa-link
                                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition-colors">
                                    <span class="material-symbols-outlined text-base">edit</span> Edit
                                </a>
                            ` : ''}
                        </div>
                    </div>

                    ${cls.status === 'cancelled' && cls.cancelled_reason ? `
                        <div class="p-3 rounded-xl bg-rose-950/40 border border-rose-500/30 text-xs text-rose-200">
                            <span class="font-semibold">Cancelled:</span> ${cls.cancelled_reason}
                        </div>
                    ` : ''}
                </header>

                <!-- Attendance roll-up -->
                <section class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    ${this.statTile('Roster marked', stats.total ?? 0, 'groups', 'text-slate-100')}
                    ${this.statTile('Present', stats.present ?? 0, 'check_circle', 'text-emerald-400')}
                    ${this.statTile('Late', stats.late ?? 0, 'schedule', 'text-amber-400')}
                    ${this.statTile('Absent', stats.absent ?? 0, 'cancel', 'text-rose-400')}
                </section>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        ${cls.description ? `
                            <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Description</h2>
                                <p class="text-xs text-slate-300 leading-relaxed">${cls.description}</p>
                            </section>
                        ` : ''}

                        ${this.listSection('smart_display', 'Recordings', recordings, (r) => `
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-100 truncate">${r.title}</p>
                                    <p class="text-[11px] text-slate-400">
                                        ${r.is_published ? 'Published' : 'Draft'} • ${r.view_count || 0} views
                                    </p>
                                </div>
                                <a href="${r.recording_url}" target="_blank" rel="noopener noreferrer"
                                   class="flex-shrink-0 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-semibold border border-slate-700">Open</a>
                            </div>
                        `, 'No recording has been attached to this class.')}

                        ${this.listSection('folder_open', 'Materials', materials, (m) => `
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-100 truncate">${m.title}</p>
                                    <p class="text-[11px] text-slate-400 uppercase">${m.file_type || 'file'} • ${m.is_published ? 'Published' : 'Draft'}</p>
                                </div>
                                <a href="${m.external_url || `/storage/${m.file_path}`}" target="_blank" rel="noopener noreferrer"
                                   class="flex-shrink-0 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-semibold border border-slate-700">Open</a>
                            </div>
                        `, 'No materials attached.')}

                        ${this.listSection('assignment', 'Assignments', assignments, (a) => `
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-100 truncate">${a.title}</p>
                                    <p class="text-[11px] text-slate-400">
                                        ${a.due_date ? `Due ${formatDate(a.due_date)}` : 'No due date'} • ${a.max_marks || 100} marks • ${a.status}
                                    </p>
                                </div>
                            </div>
                        `, 'No assignment set for this class.')}
                    </div>

                    <div class="space-y-6">
                        <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-3">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Schedule</h2>
                            <dl class="space-y-2 text-xs">
                                ${[
                                    ['Date', formatDate(cls.class_date)],
                                    ['Start', formatTime(cls.start_time)],
                                    ['End', formatTime(cls.end_time)],
                                    ['Instructor', cls.teacher?.name],
                                    ['Batch', cls.batch?.batch_name || cls.batch?.name],
                                    ['Attendance', cls.is_attendance_required ? 'Required' : 'Not tracked'],
                                ].filter(([, v]) => v).map(([label, value]) => `
                                    <div class="flex items-start justify-between gap-3">
                                        <dt class="text-slate-500">${label}</dt>
                                        <dd class="font-semibold text-slate-200 text-right">${value}</dd>
                                    </div>
                                `).join('')}
                            </dl>
                        </section>

                        ${meetingUrl ? `
                            <section class="bg-indigo-950/20 border border-indigo-500/30 rounded-2xl p-5 space-y-2">
                                <h2 class="text-xs font-bold uppercase tracking-wider text-indigo-300">Meeting room</h2>
                                <label for="meeting-url" class="sr-only">Meeting link</label>
                                <input type="text" id="meeting-url" readonly value="${meetingUrl}"
                                    class="w-full bg-slate-950 border border-slate-800 text-[11px] px-3 py-2 rounded-lg text-slate-200 font-mono">
                                ${this.payload.meeting_id ? `<p class="text-[11px] text-slate-400">ID: <span class="font-mono">${this.payload.meeting_id}</span></p>` : ''}
                                ${this.payload.meeting_password ? `<p class="text-[11px] text-slate-400">Passcode: <span class="font-mono">${this.payload.meeting_password}</span></p>` : ''}
                                <button type="button" id="btn-copy-url"
                                    class="w-full mt-1 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-semibold border border-slate-700 transition-colors">
                                    Copy link
                                </button>
                            </section>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;

        this.bind(cls, meetingUrl);
    }

    bind(cls, meetingUrl) {
        const openBtn = this.container.querySelector('#btn-open-room');
        if (openBtn && meetingUrl) {
            openBtn.addEventListener('click', async () => {
                try {
                    // Route through the join endpoint so the attempt is recorded
                    // and a teacher opening the room flips it to live.
                    const res = await classesApi.joinClass(cls.id);
                    const url = res?.meeting_url || meetingUrl;
                    window.open(url, '_blank', 'noopener');
                    this.render(this.classId);
                } catch (err) {
                    window.toast.error(err.message || 'Unable to open the room.');
                }
            });
        }

        // The room is a full screen of its own: session state, chat, roster,
        // materials and the activity log for this one class.
        this.container.querySelector('#btn-room')?.addEventListener('click', async (e) => {
            const btn = e.currentTarget;
            btn.disabled = true;
            try {
                const res = await roomApi.resolveForClass(this.classId);
                if (res?.data?.url) {
                    window.location.href = res.data.url;
                    return;
                }
                window.toast.error('This class does not have a room yet.');
            } catch (err) {
                window.toast.error(err.message || 'Unable to open the class room.');
            } finally {
                btn.disabled = false;
            }
        });

        this.container.querySelector('#btn-attendance')
            ?.addEventListener('click', () => this.openAttendanceDrawer(cls));

        this.container.querySelector('#btn-complete')?.addEventListener('click', async (e) => {
            const btn = e.currentTarget;
            btn.disabled = true;
            try {
                const res = await classesApi.completeClass(cls.id);
                window.toast.success(res.message || 'Class marked completed.');
                this.render(this.classId);
            } catch (err) {
                window.toast.error(err.message || 'Unable to mark this class completed.');
                btn.disabled = false;
            }
        });

        this.container.querySelector('#btn-copy-url')?.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(meetingUrl);
                window.toast.success('Meeting link copied.');
            } catch {
                // Clipboard can be blocked; selecting the field is the fallback.
                this.container.querySelector('#meeting-url')?.select();
                window.toast.info('Press Ctrl+C to copy the selected link.');
            }
        });
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
                    this.render(this.classId);
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

    statTile(label, value, icon, tone) {
        return `
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">${label}</div>
                    <div class="text-xl font-black mt-1 tabular-nums ${tone}">${value}</div>
                </div>
                <span class="material-symbols-outlined text-xl ${tone} opacity-60" aria-hidden="true">${icon}</span>
            </div>
        `;
    }

    listSection(icon, title, items, rowFn, emptyText) {
        return `
            <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-3">
                <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <span class="material-symbols-outlined text-base text-indigo-400">${icon}</span> ${title}
                    ${items.length ? `<span class="text-slate-600">(${items.length})</span>` : ''}
                </h2>
                ${items.length === 0
                    ? `<p class="text-xs text-slate-400">${emptyText}</p>`
                    : items.map((item) => `
                        <div class="p-3.5 bg-slate-950/70 border border-slate-800 rounded-xl">${rowFn(item)}</div>
                    `).join('')}
            </section>
        `;
    }

    skeleton() {
        return `
            <div class="space-y-6 max-w-6xl mx-auto animate-pulse">
                <div class="h-4 w-24 bg-slate-800 rounded"></div>
                <div class="h-40 bg-slate-900/80 border border-slate-800 rounded-2xl"></div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    ${Array(4).fill(0).map(() => '<div class="h-20 bg-slate-900/80 border border-slate-800 rounded-2xl"></div>').join('')}
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 h-64 bg-slate-900/80 border border-slate-800 rounded-2xl"></div>
                    <div class="h-64 bg-slate-900/80 border border-slate-800 rounded-2xl"></div>
                </div>
            </div>
        `;
    }
}
