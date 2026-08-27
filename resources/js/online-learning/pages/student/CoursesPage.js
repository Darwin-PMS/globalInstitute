import { coursesApi } from '../../api/courses.js';
import { getCardsSkeletonHTML } from '../../components/LoadingState.js';

export class StudentCoursesPage {
    constructor() {
        this.container = document.getElementById('learning-content');
        this.selectedCourseId = null;
    }

    async render(courseId = null) {
        if (!this.container) return;

        if (courseId) {
            await this.renderCourseDetail(courseId);
            return;
        }

        this.container.innerHTML = getCardsSkeletonHTML(4);

        try {
            const res = await coursesApi.getStudentDashboard();
            const enrollments = res.data?.enrollments || [];

            this.container.innerHTML = `
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-slate-100">Enrolled Courses</h2>
                            <p class="text-xs text-slate-400">Access course modules, lessons, learning resources, and assignments.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        ${enrollments.map(e => `
                            <div data-action="open-course" data-id="${e.course_id}" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 hover:border-indigo-500/40 transition-all cursor-pointer group flex flex-col justify-between space-y-4 shadow-xl">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-400 block mb-1">Active Enrollment</span>
                                    <h3 class="text-base font-bold text-slate-100 group-hover:text-indigo-300 transition-colors">${e.course?.title || 'Course'}</h3>
                                    <p class="text-xs text-slate-400 mt-1 line-clamp-2">${e.course?.description || 'No course description available.'}</p>
                                </div>

                                <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
                                    <span class="text-xs text-slate-400">${e.batch ? e.batch.batch_name || e.batch.name : 'Enrolled'}</span>
                                    <span class="text-xs font-bold text-indigo-400 group-hover:translate-x-1 transition-transform">View Course &rarr;</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;

            this.container.addEventListener('click', (evt) => {
                const card = evt.target.closest('[data-action="open-course"]');
                if (card) {
                    const cId = card.dataset.id;
                    window.history.pushState(null, '', `/student/learning/courses/${cId}`);
                    this.renderCourseDetail(cId);
                }
            });
        } catch (err) {
            console.error('Failed to load student courses:', err);
        }
    }

    async renderCourseDetail(courseId) {
        this.container.innerHTML = getCardsSkeletonHTML(3);

        try {
            const res = await coursesApi.getStudentCourseDetail(courseId);
            const data = res.data || {};
            const course = data.course || {};
            const modules = data.modules || [];
            const progress = data.progress || {};

            this.container.innerHTML = `
                <div class="space-y-6">
                    <div class="flex items-center gap-3">
                        <a href="/student/learning/courses" data-spa-link class="p-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 transition-colors">
                            <span class="material-symbols-outlined text-lg">arrow_back</span>
                        </a>
                        <div>
                            <h2 class="text-xl font-bold text-slate-100">${course.title}</h2>
                            <p class="text-xs text-slate-400">Course Syllabus & Lessons</p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-2">
                        <div class="flex justify-between text-xs font-semibold text-slate-300">
                            <span>Overall Progress</span>
                            <span class="text-indigo-400">${progress.percentage || 0}% Completed</span>
                        </div>
                        <div class="w-full bg-slate-950 h-3 rounded-full overflow-hidden border border-slate-800 p-0.5">
                            <div class="bg-gradient-to-r from-indigo-500 to-purple-500 h-full rounded-full transition-all duration-500" style="width: ${progress.percentage || 0}%"></div>
                        </div>
                    </div>

                    <!-- Modules list -->
                    <div class="space-y-4">
                        ${modules.map(mod => `
                            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg space-y-3">
                                <h3 class="text-sm font-bold text-slate-200 border-b border-slate-800 pb-2">${mod.name}</h3>
                                <div class="divide-y divide-slate-800/60">
                                    ${(mod.lessons || []).map(les => `
                                        <div class="py-2.5 flex items-center justify-between text-xs">
                                            <div class="flex items-center gap-2">
                                                <span class="material-symbols-outlined text-slate-500 text-base">play_circle</span>
                                                <span class="text-slate-300">${les.title}</span>
                                            </div>
                                            <span class="text-[11px] text-slate-500 font-mono">${les.duration ? les.duration + ' mins' : ''}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        } catch (err) {
            this.container.innerHTML = `<div class="p-8 text-center text-xs text-rose-400 bg-rose-950/20 border border-rose-500/30 rounded-2xl">Failed to load course details: ${err.message}</div>`;
        }
    }
}
