import { formatDate, formatTime } from '../utils/date.js';
import { getStatusBadgeHTML, getProviderBadgeHTML } from '../utils/formatter.js';

export function renderClassCard(cls, options = {}) {
    const isStudent = window.OnlineLearningConfig?.role === 'student';
    const isTeacher = window.OnlineLearningConfig?.role === 'teacher';
    const isAdmin = window.OnlineLearningConfig?.role === 'admin';

    const canJoin = cls.status === 'live' || cls.status === 'starting_soon' || cls.status === 'scheduled';

    return `
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 transition-all duration-200 flex flex-col justify-between shadow-xl group relative overflow-hidden">
            ${cls.status === 'live' ? `<div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-400"></div>` : ''}

            <!-- Card Header -->
            <div>
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div>
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-400 block mb-1">
                            ${cls.course?.title || 'General Course'}
                        </span>
                        <h3 class="text-sm font-bold text-slate-100 group-hover:text-indigo-300 transition-colors line-clamp-2">
                            ${cls.title}
                        </h3>
                    </div>
                    ${getStatusBadgeHTML(cls.status)}
                </div>

                ${cls.description ? `<p class="text-xs text-slate-400 line-clamp-2 mb-4 leading-relaxed">${cls.description}</p>` : ''}

                <!-- Class Meta Details -->
                <div class="space-y-2 pt-2 border-t border-slate-800/80 text-xs text-slate-300">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-base text-slate-400">calendar_today</span>
                        <span>${formatDate(cls.class_date)}</span>
                        <span class="text-slate-600">•</span>
                        <span class="material-symbols-outlined text-base text-slate-400">schedule</span>
                        <span>${formatTime(cls.start_time)} - ${formatTime(cls.end_time)}</span>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-base text-slate-400">person</span>
                            <span class="truncate">${cls.teacher?.name || 'Assigned Instructor'}</span>
                        </div>
                        ${getProviderBadgeHTML(cls.meeting_provider)}
                    </div>
                </div>
            </div>

            <!-- Card Footer Actions -->
            <div class="pt-4 mt-4 border-t border-slate-800 flex items-center justify-between gap-2">
                <button data-action="view-details" data-id="${cls.id}" class="text-xs text-slate-400 hover:text-slate-200 font-medium px-2 py-1 rounded hover:bg-slate-800 transition-colors">
                    Details
                </button>

                <div class="flex items-center gap-2">
                    ${isAdmin ? `
                        <button data-action="edit-class" data-id="${cls.id}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium border border-slate-700 transition-colors">
                            Edit
                        </button>
                    ` : ''}

                    ${isTeacher ? `
                        <button data-action="mark-attendance" data-id="${cls.id}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-indigo-300 text-xs font-medium border border-slate-700 transition-colors">
                            Attendance
                        </button>
                    ` : ''}

                    ${canJoin ? `
                        <button data-action="join-class" data-id="${cls.id}" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/20 transition-all hover:scale-105 active:scale-95">
                            <span class="material-symbols-outlined text-sm">videocam</span> Join Class
                        </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}
