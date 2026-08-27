import { coursesApi } from '../../api/courses.js';
import { classesApi } from '../../api/classes.js';
import { attendanceApi } from '../../api/attendance.js';
import { getDashboardSkeletonHTML } from '../../components/LoadingState.js';
import { Drawer } from '../../components/Drawer.js';
import { renderAttendanceTable } from '../../components/AttendanceTable.js';
import { formatDate, formatTime } from '../../utils/date.js';
import { getStatusBadgeHTML } from '../../utils/formatter.js';

export class TeacherDashboardPage {
    constructor() {
        this.container = document.getElementById('learning-content');
    }

    async render() {
        if (!this.container) return;

        this.container.innerHTML = getDashboardSkeletonHTML();

        try {
            const res = await coursesApi.getTeacherDashboard();
            const data = res.data || {};
            const todayClasses = data.today_classes || [];
            const upcomingClasses = data.upcoming_classes || [];

            this.container.innerHTML = `
                <div class="space-y-8">
                    <!-- Hero Welcome Banner -->
                    <div class="bg-gradient-to-r from-purple-950 via-slate-900 to-indigo-950 border border-purple-500/20 rounded-2xl p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-2xl">
                        <div>
                            <span class="px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-purple-500/10 text-purple-300 border border-purple-500/20">
                                Instructor Workspace
                            </span>
                            <h2 class="text-2xl font-black text-white mt-2">Welcome back, ${data.teacher?.name || 'Instructor'}</h2>
                            <p class="text-xs text-slate-400 mt-1">You have ${todayClasses.length} class(es) scheduled for today.</p>
                        </div>
                    </div>

                    <!-- Today's Schedule Focus Cards -->
                    <div class="space-y-4">
                        <h3 class="text-base font-bold text-slate-100 flex items-center gap-2">
                            <span class="material-symbols-outlined text-purple-400">today</span> Today's Class Controls
                        </h3>

                        ${todayClasses.length > 0 ? `
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                ${todayClasses.map(cls => `
                                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-400 block">${cls.course?.title || 'Course'}</span>
                                                <h4 class="text-base font-bold text-slate-100 mt-1">${cls.title}</h4>
                                            </div>
                                            ${getStatusBadgeHTML(cls.status)}
                                        </div>

                                        <div class="flex items-center gap-4 text-xs text-slate-300 pt-2 border-t border-slate-800">
                                            <div class="flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-slate-400 text-sm">schedule</span>
                                                <span>${formatTime(cls.start_time)} - ${formatTime(cls.end_time)}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-slate-400 text-sm">groups</span>
                                                <span>${cls.batch?.name || cls.batch?.batch_name || 'Batch'}</span>
                                            </div>
                                        </div>

                                        <!-- Teacher Actions Panel -->
                                        <div class="pt-4 border-t border-slate-800 flex items-center justify-between gap-2 flex-wrap">
                                            <button data-action="teacher-join" data-id="${cls.id}" class="flex-1 py-2 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-emerald-600/20 transition-all text-center">
                                                Start / Join Class
                                            </button>

                                            <button data-action="teacher-attendance" data-id="${cls.id}" class="py-2 px-3 bg-slate-800 hover:bg-slate-700 text-indigo-300 rounded-xl text-xs font-medium border border-slate-700 transition-colors">
                                                Attendance
                                            </button>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        ` : `
                            <div class="p-8 bg-slate-900/60 border border-slate-800 rounded-2xl text-center text-xs text-slate-400">
                                No classes scheduled for today.
                            </div>
                        `}
                    </div>
                </div>
            `;

            this.bindEvents();
        } catch (err) {
            console.error('Failed to render teacher dashboard:', err);
        }
    }

    bindEvents() {
        this.container.addEventListener('click', async (e) => {
            const trigger = e.target.closest('[data-action]');
            if (!trigger) return;

            const action = trigger.dataset.action;
            const classId = parseInt(trigger.dataset.id, 10);

            if (action === 'teacher-join') {
                try {
                    const res = await classesApi.joinClass(classId);
                    if (res.meeting_url) {
                        window.open(res.meeting_url, '_blank');
                    }
                } catch (err) {
                    window.toast.error(err.message || 'Failed to join meeting.');
                }
            } else if (action === 'teacher-attendance') {
                this.openAttendanceDrawer(classId);
            }
        });
    }

    async openAttendanceDrawer(classId) {
        const drawer = new Drawer({
            title: 'Mark Class Attendance',
            subtitle: 'Select status for each student and save.',
            size: 'lg'
        });

        drawer.contentHTML = `<div class="p-8 text-center text-xs text-slate-400">Loading student attendance...</div>`;
        drawer.render();

        try {
            const res = await attendanceApi.getClassAttendance(classId);
            const data = res.data || {};
            const students = data.students || [];

            drawer.setContent(`
                <form id="attendance-form" class="space-y-4">
                    ${renderAttendanceTable(students, data.stats || {})}
                    <div class="pt-4 border-t border-slate-800 flex justify-end">
                        <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs shadow-lg transition-all">
                            Save Class Attendance
                        </button>
                    </div>
                </form>
            `);

            const form = drawer.element.querySelector('#attendance-form');
            form.addEventListener('submit', async (evt) => {
                evt.preventDefault();
                const rows = drawer.element.querySelectorAll('.attendance-row');
                const records = Array.from(rows).map(row => ({
                    student_id: parseInt(row.dataset.studentId, 10),
                    status: row.querySelector('.status-select').value,
                    notes: row.querySelector('.notes-input').value,
                }));

                try {
                    await attendanceApi.markAttendance(classId, records);
                    window.toast.success('Attendance saved successfully.');
                    drawer.close();
                } catch (err) {
                    window.toast.error(err.message || 'Failed to save attendance.');
                }
            });
        } catch (err) {
            drawer.setContent(`<div class="p-4 text-xs text-rose-400">Failed to load attendance: ${err.message}</div>`);
        }
    }
}
