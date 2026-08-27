import { classDetailApi, roomApi } from '../../api/learning.js';
import { classesApi } from '../../api/classes.js';
import { Countdown } from '../../components/Countdown.js';
import { getEmptyStateHTML } from '../../components/EmptyState.js';
import { formatDate, formatTime } from '../../utils/date.js';
import { getStatusBadgeHTML, getProviderBadgeHTML } from '../../utils/formatter.js';
import { path } from '../../utils/permissions.js';

/**
 * The live class screen (§26) - the single most important student view.
 *
 * It has three faces driven by class status, and re-reads the server after a
 * join so a stale "can_join" never strands the student:
 *   before  -> countdown + what to expect
 *   during  -> LIVE banner + join + resources
 *   after   -> recording, materials, assignment, attendance
 */
export class StudentClassDetailPage {
    constructor() {
        this.container = document.getElementById('learning-content');
        this.countdown = null;
        this.classId = null;
        this.payload = null;
    }

    destroy() {
        if (this.countdown) {
            this.countdown.stop();
            this.countdown = null;
        }
    }

    async render(classId) {
        if (!this.container) return;
        this.classId = classId;

        this.container.innerHTML = this.skeleton();

        try {
            const res = await classDetailApi.get(classId);
            if (!res) return; // aborted by a newer navigation

            this.payload = res.data || {};
            this.paint();
        } catch (err) {
            this.container.innerHTML = this.errorState(err);
            const retry = this.container.querySelector('#retry-class');
            if (retry) retry.addEventListener('click', () => this.render(classId));
        }
    }

    paint() {
        const cls = this.payload.class || {};
        const canJoin = !!this.payload.can_join;
        const joinMessage = this.payload.join_message || '';
        const recordings = this.payload.recordings || [];
        const materials = this.payload.materials || [];
        const assignments = this.payload.assignments || [];
        const attendance = this.payload.my_attendance;

        const isLive = cls.status === 'live';
        const isOver = cls.status === 'completed';
        const isCancelled = cls.status === 'cancelled';

        this.container.innerHTML = `
            <div class="space-y-6 max-w-5xl mx-auto pb-8">
                <a href="${path('classes')}" data-spa-link
                   class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400 hover:text-indigo-400 transition-colors">
                    <span class="material-symbols-outlined text-base">arrow_back</span> All classes
                </a>

                <!-- Stage banner: changes with the class lifecycle -->
                <section class="relative overflow-hidden rounded-2xl border shadow-2xl p-6 sm:p-8 ${this.bannerTone(cls.status)}">
                    ${isLive ? '<div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-400 via-teal-400 to-emerald-400"></div>' : ''}

                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="space-y-3 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                ${getStatusBadgeHTML(cls.status)}
                                <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-300">
                                    ${cls.course?.title || 'Course'}
                                </span>
                                ${cls.module?.name ? `<span class="text-[11px] text-slate-400">• ${cls.module.name}</span>` : ''}
                            </div>

                            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">${cls.title || 'Class'}</h1>

                            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-300">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-base text-slate-400">person</span>
                                    ${cls.teacher?.name || 'Instructor'}
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-base text-slate-400">calendar_today</span>
                                    ${formatDate(cls.class_date)}
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-base text-slate-400">schedule</span>
                                    ${formatTime(cls.start_time)} – ${formatTime(cls.end_time)}
                                </span>
                                ${cls.meeting_provider ? getProviderBadgeHTML(cls.meeting_provider) : ''}
                            </div>

                            ${isCancelled && cls.cancelled_reason ? `
                                <div class="mt-2 p-3 rounded-xl bg-rose-950/40 border border-rose-500/30 text-xs text-rose-200">
                                    <span class="font-semibold">Cancelled:</span> ${cls.cancelled_reason}
                                </div>
                            ` : ''}
                        </div>

                        <!-- Join column -->
                        <div class="lg:w-64 flex-shrink-0 space-y-3">
                            <div id="class-countdown" class="flex lg:justify-end"></div>

                            <button id="btn-join" type="button" ${canJoin ? '' : 'disabled'}
                                class="w-full py-3 rounded-xl text-sm font-bold transition-all ${canJoin
                                    ? 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white shadow-lg shadow-emerald-600/30 hover:scale-[1.02] active:scale-[0.98]'
                                    : 'bg-slate-800 text-slate-500 border border-slate-700 cursor-not-allowed'}">
                                <span class="inline-flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-lg">${isLive ? 'sensors' : 'videocam'}</span>
                                    ${isLive ? 'Join Live Class' : 'Join Class'}
                                </span>
                            </button>

                            <button id="btn-room" type="button"
                                class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition-colors">
                                <span class="inline-flex items-center justify-center gap-1.5">
                                    <span class="material-symbols-outlined text-base">meeting_room</span> Open class room
                                </span>
                            </button>

                            <p class="text-[11px] leading-relaxed ${canJoin ? 'text-emerald-300' : 'text-slate-400'} lg:text-right">
                                ${joinMessage}
                            </p>
                        </div>
                    </div>
                </section>

                ${cls.description ? `
                    <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">About this class</h2>
                        <p class="text-xs text-slate-300 leading-relaxed">${cls.description}</p>
                    </section>
                ` : ''}

                <!-- After-class + resource sections -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        ${this.recordingsSection(recordings, isOver)}
                        ${this.materialsSection(materials)}
                        ${this.assignmentsSection(assignments)}
                    </div>

                    <div class="space-y-6">
                        ${this.attendanceSection(attendance, cls)}
                        ${this.detailsSection(cls)}
                    </div>
                </div>
            </div>
        `;

        this.startCountdown(cls);
        this.bindJoin();
        this.bindRoom();
    }

    /* ---------- sections ---------- */

    recordingsSection(recordings, isOver) {
        if (recordings.length === 0) {
            return `
                <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                    ${this.sectionHeading('smart_display', 'Recording')}
                    <p class="text-xs text-slate-400 leading-relaxed">
                        ${isOver
                            ? 'The recording for this class has not been published yet. It will appear here once your instructor uploads it.'
                            : 'The recording will be available here after the class ends.'}
                    </p>
                </section>
            `;
        }

        return `
            <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-3">
                ${this.sectionHeading('smart_display', 'Recording')}
                ${recordings.map((rec) => `
                    <div class="flex items-center justify-between gap-3 p-3.5 bg-slate-950/70 border border-slate-800 rounded-xl">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-slate-100 truncate">${rec.title}</p>
                            <p class="text-[11px] text-slate-400">
                                ${rec.duration ? `${rec.duration} min` : 'Full session'} • ${rec.view_count || 0} views
                            </p>
                        </div>
                        <a href="${rec.recording_url}" target="_blank" rel="noopener noreferrer"
                           class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-bold transition-colors">
                            <span class="material-symbols-outlined text-sm">play_arrow</span> Watch
                        </a>
                    </div>
                `).join('')}
            </section>
        `;
    }

    materialsSection(materials) {
        return `
            <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-3">
                ${this.sectionHeading('folder_open', 'Class materials')}
                ${materials.length === 0
                    ? `<p class="text-xs text-slate-400">No materials have been shared for this class.</p>`
                    : materials.map((m) => `
                        <div class="flex items-center justify-between gap-3 p-3.5 bg-slate-950/70 border border-slate-800 rounded-xl">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="material-symbols-outlined text-lg text-indigo-400 flex-shrink-0">${this.fileIcon(m.file_type)}</span>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-100 truncate">${m.title}</p>
                                    <p class="text-[11px] text-slate-500 uppercase">${m.file_type || 'file'}</p>
                                </div>
                            </div>
                            <a href="${m.external_url || `/storage/${m.file_path}`}" target="_blank" rel="noopener noreferrer"
                               class="flex-shrink-0 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-semibold border border-slate-700 transition-colors">
                                Open
                            </a>
                        </div>
                    `).join('')}
            </section>
        `;
    }

    assignmentsSection(assignments) {
        return `
            <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-3">
                ${this.sectionHeading('assignment', 'Assignments from this class')}
                ${assignments.length === 0
                    ? `<p class="text-xs text-slate-400">No assignment was set for this class.</p>`
                    : assignments.map((a) => `
                        <a href="${path('assignments')}" data-spa-link
                           class="block p-3.5 bg-slate-950/70 border border-slate-800 rounded-xl hover:border-indigo-500/40 transition-colors">
                            <p class="text-xs font-semibold text-slate-100">${a.title}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                ${a.due_date ? `Due ${formatDate(a.due_date)}` : 'No due date'} • ${a.max_marks || 100} marks
                            </p>
                        </a>
                    `).join('')}
            </section>
        `;
    }

    attendanceSection(attendance, cls) {
        if (!cls.is_attendance_required) {
            return `
                <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                    ${this.sectionHeading('how_to_reg', 'Attendance')}
                    <p class="text-xs text-slate-400">Attendance is not tracked for this class.</p>
                </section>
            `;
        }

        const tone = {
            present: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
            late: 'text-amber-400 bg-amber-500/10 border-amber-500/20',
            partial: 'text-indigo-400 bg-indigo-500/10 border-indigo-500/20',
            absent: 'text-rose-400 bg-rose-500/10 border-rose-500/20',
        }[attendance?.status] || 'text-slate-400 bg-slate-800 border-slate-700';

        return `
            <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-3">
                ${this.sectionHeading('how_to_reg', 'Your attendance')}
                ${attendance
                    ? `
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold border capitalize ${tone}">
                            ${attendance.status}
                        </div>
                        ${attendance.joined_at ? `<p class="text-[11px] text-slate-400">Joined at ${formatTime(attendance.joined_at)}</p>` : ''}
                        ${attendance.notes ? `<p class="text-[11px] text-slate-400 italic">${attendance.notes}</p>` : ''}
                    `
                    : `<p class="text-xs text-slate-400">Not marked yet. Joining the class records your attendance automatically.</p>`}
            </section>
        `;
    }

    detailsSection(cls) {
        const rows = [
            ['Course', cls.course?.title],
            ['Module', cls.module?.name],
            ['Batch', cls.batch?.batch_name || cls.batch?.name],
            ['Instructor', cls.teacher?.name],
            ['Date', formatDate(cls.class_date)],
            ['Time', `${formatTime(cls.start_time)} – ${formatTime(cls.end_time)}`],
        ].filter(([, v]) => v);

        return `
            <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-3">
                ${this.sectionHeading('info', 'Class details')}
                <dl class="space-y-2 text-xs">
                    ${rows.map(([label, value]) => `
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">${label}</dt>
                            <dd class="font-semibold text-slate-200 text-right">${value}</dd>
                        </div>
                    `).join('')}
                </dl>
            </section>
        `;
    }

    /* ---------- behaviour ---------- */

    startCountdown(cls) {
        const mount = this.container.querySelector('#class-countdown');
        if (!mount) return;

        this.destroy();
        this.countdown = new Countdown(
            cls.class_date ? String(cls.class_date).substring(0, 10) : null,
            cls.start_time,
            cls.status
        );
        this.countdown.startTicking(mount);
    }

    /**
     * The class room is a separate screen with its own session state, chat,
     * materials and activity. Resolving gives us (and provisions) its key.
     */
    bindRoom() {
        const btn = this.container.querySelector('#btn-room');
        if (!btn) return;

        btn.addEventListener('click', async () => {
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
    }

    bindJoin() {
        const btn = this.container.querySelector('#btn-join');
        if (!btn || btn.disabled) return;

        btn.addEventListener('click', async () => {
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="text-xs">Authorising…</span>';

            try {
                // The server re-checks enrollment and the join window, and logs
                // the attempt; the URL is only returned if it authorises.
                const res = await classesApi.joinClass(this.classId);

                if (res?.success && res.meeting_url) {
                    window.toast.success(res.message || 'Opening the meeting room…');
                    window.open(res.meeting_url, '_blank', 'noopener');
                } else {
                    window.toast.warning(res?.message || 'The meeting link is not available.');
                }
            } catch (err) {
                window.toast.error(err.message || 'Unable to join this class.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = original;
                // Re-read: joining may have flipped status to live and written attendance.
                this.render(this.classId);
            }
        });
    }

    /* ---------- presentation helpers ---------- */

    sectionHeading(icon, label) {
        return `
            <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                <span class="material-symbols-outlined text-base text-indigo-400">${icon}</span> ${label}
            </h2>
        `;
    }

    bannerTone(status) {
        if (status === 'live') return 'bg-gradient-to-br from-emerald-950/60 via-slate-900 to-slate-950 border-emerald-500/30';
        if (status === 'cancelled') return 'bg-gradient-to-br from-rose-950/40 via-slate-900 to-slate-950 border-rose-500/30';
        if (status === 'completed') return 'bg-gradient-to-br from-slate-900 to-slate-950 border-slate-800';
        return 'bg-gradient-to-br from-indigo-950/60 via-slate-900 to-slate-950 border-indigo-500/25';
    }

    fileIcon(type) {
        return {
            pdf: 'picture_as_pdf',
            ppt: 'slideshow',
            doc: 'description',
            xls: 'table_chart',
            zip: 'folder_zip',
            image: 'image',
            link: 'link',
        }[type] || 'attach_file';
    }

    skeleton() {
        return `
            <div class="space-y-6 max-w-5xl mx-auto animate-pulse">
                <div class="h-4 w-24 bg-slate-800 rounded"></div>
                <div class="h-52 bg-slate-900/80 border border-slate-800 rounded-2xl"></div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="h-32 bg-slate-900/80 border border-slate-800 rounded-2xl"></div>
                        <div class="h-32 bg-slate-900/80 border border-slate-800 rounded-2xl"></div>
                    </div>
                    <div class="space-y-6">
                        <div class="h-28 bg-slate-900/80 border border-slate-800 rounded-2xl"></div>
                        <div class="h-48 bg-slate-900/80 border border-slate-800 rounded-2xl"></div>
                    </div>
                </div>
            </div>
        `;
    }

    errorState(err) {
        const forbidden = /permission|not enrolled|access/i.test(err.message || '');

        return `
            <div class="max-w-lg mx-auto mt-10">
                ${getEmptyStateHTML({
                    icon: forbidden ? 'lock' : 'error',
                    title: forbidden ? 'You cannot view this class' : 'Unable to load this class',
                    description: err.message || 'Something went wrong while loading the class.',
                    actionButtonText: 'Try again',
                    actionButtonId: 'retry-class',
                })}
                <div class="text-center mt-4">
                    <a href="${path('classes')}" data-spa-link class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">
                        Back to my classes
                    </a>
                </div>
            </div>
        `;
    }
}
