import { coursesApi } from '../../api/courses.js';
import { classesApi } from '../../api/classes.js';
import { Countdown } from '../../components/Countdown.js';
import { getDashboardSkeletonHTML } from '../../components/LoadingState.js';
import { renderClassCard } from '../../components/ClassCard.js';
import { getEmptyStateHTML } from '../../components/EmptyState.js';
import { formatDate, formatTime, greeting } from '../../utils/date.js';

export class StudentDashboardPage {
    constructor() {
        this.container = document.getElementById('learning-content');
        this.countdownInstance = null;
    }

    async render() {
        if (!this.container) return;

        this.container.innerHTML = getDashboardSkeletonHTML();

        try {
            const res = await coursesApi.getStudentDashboard();
            const data = res.data || {};
            const student = data.student || {};
            const nextClass = data.next_class || null;
            const upcomingClasses = data.upcoming_classes || [];
            const stats = data.stats || {};

            this.container.innerHTML = `
                <div class="space-y-8">
                    <!-- Hero Welcome Banner -->
                    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-950 border border-indigo-500/20 rounded-2xl p-6 sm:p-8 flex flex-col lg:flex-row lg:items-center justify-between gap-6 shadow-2xl relative overflow-hidden">
                        <div class="space-y-3 max-w-xl">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                Student Portal
                            </span>
                            <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">${greeting()}, ${student.name || 'Student'}</h2>
                            <p class="text-xs text-slate-300 leading-relaxed">Continue your CAD CAM & technical learning journey. Access live classes and course resources seamlessly.</p>
                        </div>

                        <!-- Next Class Countdown Feature Box -->
                        ${nextClass ? `
                            <div class="bg-slate-900/90 border border-slate-700/80 rounded-2xl p-5 w-full lg:w-80 shadow-2xl flex flex-col justify-between space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-400">Next Scheduled Class</span>
                                    <div id="hero-countdown-mount"></div>
                                </div>

                                <div>
                                    <h4 class="text-sm font-bold text-slate-100 line-clamp-1">${nextClass.title}</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">${nextClass.course?.title || 'Course'} • ${formatTime(nextClass.start_time)}</p>
                                </div>

                                <div class="space-y-2">
                                    <button data-action="student-join" data-id="${nextClass.id}" class="w-full py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-emerald-600/25 transition-all text-center">
                                        <span class="material-symbols-outlined text-sm align-middle mr-1">videocam</span> Join Class
                                    </button>
                                    <a href="/student/learning/classes/${nextClass.id}" data-spa-link class="block w-full py-2 text-center rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-semibold border border-slate-700 transition-colors">
                                        Class details, materials &amp; notes
                                    </a>
                                </div>
                            </div>
                        ` : `
                            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 w-full lg:w-80 text-center space-y-3">
                                <p class="text-xs text-slate-400">No upcoming classes scheduled.</p>
                                <p class="text-[11px] text-slate-500">Your next live session will appear here as soon as it is scheduled.</p>
                                <a href="/student/learning/courses" data-spa-link class="inline-block px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-semibold border border-slate-700 transition-colors">
                                    Browse my courses
                                </a>
                            </div>
                        `}
                    </div>

                    <!-- LMS Metrics Stats Grid -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
                            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Enrolled Courses</div>
                            <div class="text-2xl font-black text-slate-100 mt-1">${stats.total_courses || 0}</div>
                        </div>
                        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
                            <div class="text-[11px] font-semibold text-indigo-400 uppercase tracking-wider">Course Progress</div>
                            <div class="text-2xl font-black text-indigo-400 mt-1">${stats.progress_percent || 0}%</div>
                        </div>
                        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
                            <div class="text-[11px] font-semibold text-amber-400 uppercase tracking-wider">Pending Assignments</div>
                            <div class="text-2xl font-black text-amber-400 mt-1">${stats.pending_assignments_count || 0}</div>
                        </div>
                        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
                            <div class="text-[11px] font-semibold text-emerald-400 uppercase tracking-wider">Completed Lessons</div>
                            <div class="text-2xl font-black text-emerald-400 mt-1">${stats.completed_lessons || 0}</div>
                        </div>
                    </div>

                    <!-- Upcoming Classes Schedule -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-100 flex items-center gap-2">
                                <span class="material-symbols-outlined text-indigo-400">live_tv</span> Upcoming Live Classes
                            </h3>
                            <a href="/student/learning/classes" data-spa-link class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">View Timeline &rarr;</a>
                        </div>

                        ${upcomingClasses.length > 0 ? `
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                ${upcomingClasses.map(c => renderClassCard(c)).join('')}
                            </div>
                        ` : getEmptyStateHTML({
                            title: 'No upcoming classes',
                            description: 'You are all caught up for now! Check back later for new scheduled sessions.'
                        })}
                    </div>
                </div>
            `;

            // Start live countdown if nextClass exists
            if (nextClass) {
                const mount = this.container.querySelector('#hero-countdown-mount');
                if (mount) {
                    this.countdownInstance = new Countdown(nextClass.class_date, nextClass.start_time, nextClass.status);
                    this.countdownInstance.startTicking(mount);
                }
            }

            this.bindEvents();
        } catch (err) {
            console.error('Failed to load student dashboard:', err);
        }
    }

    bindEvents() {
        this.container.addEventListener('click', async (e) => {
            const trigger = e.target.closest('[data-action="student-join"], [data-action="join-class"]');
            if (!trigger) return;

            const classId = parseInt(trigger.dataset.id, 10);
            try {
                const res = await classesApi.joinClass(classId);
                if (res.meeting_url) {
                    window.toast.success('Joining class room...');
                    window.open(res.meeting_url, '_blank');
                }
            } catch (err) {
                window.toast.error(err.message || 'Unable to join class at this time.');
            }
        });
    }
}
