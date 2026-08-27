import { progressApi } from '../../api/progress.js';
import { getEmptyStateHTML } from '../../components/EmptyState.js';
import { renderProgressBar, renderProgressRing, toneFor } from '../../components/ProgressBar.js';
import { path } from '../../utils/permissions.js';

/**
 * Student progress: overall roll-up plus a per-course breakdown of lesson
 * completion and attendance. All figures come from the server.
 */
export class StudentProgressPage {
    constructor() {
        this.container = document.getElementById('learning-content');
    }

    async render() {
        if (!this.container) return;

        this.container.innerHTML = this.skeleton();

        try {
            const res = await progressApi.forStudent();
            if (!res) return;

            const data = res.data || {};
            this.paint(data.overall || {}, data.courses || []);
        } catch (err) {
            this.container.innerHTML = `
                <div class="max-w-lg mx-auto mt-10 p-8 text-center bg-rose-950/20 border border-rose-500/30 rounded-2xl">
                    <p class="text-xs text-rose-300 mb-4">Unable to load your progress. ${err.message}</p>
                    <button id="prog-retry" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700">
                        Try again
                    </button>
                </div>
            `;
            this.container.querySelector('#prog-retry')?.addEventListener('click', () => this.render());
        }
    }

    paint(overall, courses) {
        this.container.innerHTML = `
            <div class="space-y-6">
                <header class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                        <span class="material-symbols-outlined text-xl">trending_up</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-100">My Progress</h2>
                        <p class="text-xs text-slate-400">Lesson completion and attendance across your courses.</p>
                    </div>
                </header>

                <!-- Overall roll-up -->
                <section class="bg-gradient-to-br from-indigo-950/50 via-slate-900 to-slate-950 border border-indigo-500/20 rounded-2xl p-6 shadow-xl">
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <div class="flex-shrink-0">
                            ${renderProgressRing(overall.progress_percent || 0, { size: 104, label: 'Overall completion' })}
                        </div>

                        <div class="flex-1 grid grid-cols-2 lg:grid-cols-4 gap-4 w-full text-center sm:text-left">
                            ${this.stat('Courses', overall.courses_count ?? 0, 'auto_stories')}
                            ${this.stat('Lessons done', `${overall.completed_lessons ?? 0}/${overall.total_lessons ?? 0}`, 'task_alt')}
                            ${this.stat('Avg. attendance', `${overall.average_attendance ?? 0}%`, 'how_to_reg')}
                            ${this.stat('Overall', `${overall.progress_percent ?? 0}%`, 'trending_up')}
                        </div>
                    </div>
                </section>

                <!-- Per-course breakdown -->
                <section class="space-y-4">
                    <h3 class="text-sm font-bold text-slate-100">Course breakdown</h3>

                    ${courses.length === 0
                        ? getEmptyStateHTML({
                            icon: 'school',
                            title: 'No active enrollments',
                            description: 'Once you are enrolled in a course, your progress will be tracked here.',
                        })
                        : `<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                                ${courses.map((c) => this.courseCard(c)).join('')}
                           </div>`}
                </section>
            </div>
        `;
    }

    stat(label, value, icon) {
        return `
            <div class="space-y-1">
                <div class="flex items-center justify-center sm:justify-start gap-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">${icon}</span> ${label}
                </div>
                <div class="text-xl font-black text-slate-100 tabular-nums">${value}</div>
            </div>
        `;
    }

    courseCard(course) {
        const percent = course.progress_percent || 0;
        const attendance = course.attendance_rate || 0;

        return `
            <article class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4 hover:border-slate-700 transition-colors">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h4 class="text-sm font-bold text-slate-100 truncate">${course.course_title}</h4>
                        ${course.batch ? `<p class="text-[11px] text-slate-400 mt-0.5">${course.batch}</p>` : ''}
                    </div>
                    <a href="${path('courses', course.course_id)}" data-spa-link
                       class="flex-shrink-0 text-[11px] font-semibold text-indigo-400 hover:text-indigo-300 transition-colors">
                        Open →
                    </a>
                </div>

                ${renderProgressBar(percent, { label: 'Lesson completion' })}
                ${renderProgressBar(attendance, { label: 'Attendance', tone: toneFor(attendance) })}

                <div class="flex items-center gap-4 pt-3 border-t border-slate-800 text-[11px] text-slate-400">
                    <span class="inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">task_alt</span>
                        ${course.completed_lessons}/${course.total_lessons} lessons
                    </span>
                </div>
            </article>
        `;
    }

    skeleton() {
        return `
            <div class="space-y-6 animate-pulse">
                <div class="h-10 w-56 bg-slate-800 rounded"></div>
                <div class="h-40 bg-slate-900/80 border border-slate-800 rounded-2xl"></div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    ${Array(4).fill(0).map(() => '<div class="h-48 bg-slate-900/80 border border-slate-800 rounded-2xl"></div>').join('')}
                </div>
            </div>
        `;
    }
}
