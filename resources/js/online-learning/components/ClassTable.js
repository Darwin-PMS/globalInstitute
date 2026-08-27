import { formatDate, formatTime } from '../utils/date.js';
import { getStatusBadgeHTML, getProviderBadgeHTML } from '../utils/formatter.js';

export function renderClassTable(classes = [], options = {}) {
    const role = window.OnlineLearningConfig?.role || 'student';

    if (classes.length === 0) {
        return ``;
    }

    return `
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs text-slate-300">
                    <thead>
                        <tr class="border-b border-slate-800 bg-slate-950/60 text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
                            <th class="py-3.5 px-4 w-12 text-center">#</th>
                            <th class="py-3.5 px-4">Class & Course</th>
                            <th class="py-3.5 px-4">Instructor</th>
                            <th class="py-3.5 px-4">Date & Time</th>
                            <th class="py-3.5 px-4">Provider</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-medium">
                        ${classes.map(cls => `
                            <tr class="hover:bg-slate-800/40 transition-colors group">
                                <td class="py-3.5 px-4 text-center font-mono text-slate-500">#${cls.id}</td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700/60 flex items-center justify-center text-indigo-400 flex-shrink-0">
                                            <span class="material-symbols-outlined text-lg">videocam</span>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-100 group-hover:text-indigo-300 transition-colors text-xs">${cls.title}</div>
                                            <div class="text-[11px] text-slate-400">${cls.course?.title || 'General'} ${cls.batch ? `• ${cls.batch.batch_name || cls.batch.name || ''}` : ''}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="text-slate-300">${cls.teacher?.name || 'N/A'}</span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div>${formatDate(cls.class_date)}</div>
                                    <div class="text-[11px] text-slate-400">${formatTime(cls.start_time)} - ${formatTime(cls.end_time)}</div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    ${getProviderBadgeHTML(cls.meeting_provider)}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    ${getStatusBadgeHTML(cls.status)}
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button data-action="view-details" data-id="${cls.id}" class="p-1.5 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-white transition-colors" title="Details">
                                            <span class="material-symbols-outlined text-base">visibility</span>
                                        </button>
                                        
                                        ${role === 'admin' ? `
                                            <button data-action="edit-class" data-id="${cls.id}" class="p-1.5 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-indigo-300 transition-colors" title="Edit">
                                                <span class="material-symbols-outlined text-base">edit</span>
                                            </button>
                                            <button data-action="reschedule-class" data-id="${cls.id}" class="p-1.5 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-amber-300 transition-colors" title="Reschedule">
                                                <span class="material-symbols-outlined text-base">edit_calendar</span>
                                            </button>
                                            ${cls.status !== 'cancelled' ? `
                                                <button data-action="cancel-class" data-id="${cls.id}" class="p-1.5 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-amber-400 transition-colors" title="Cancel class">
                                                    <span class="material-symbols-outlined text-base">event_busy</span>
                                                </button>
                                            ` : ''}
                                            <button data-action="delete-class" data-id="${cls.id}" class="p-1.5 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-rose-400 transition-colors" title="Delete">
                                                <span class="material-symbols-outlined text-base">delete</span>
                                            </button>
                                        ` : ''}

                                        ${role === 'teacher' ? `
                                            <button data-action="mark-attendance" data-id="${cls.id}" class="p-1.5 rounded-lg hover:bg-slate-800 text-indigo-400 hover:text-indigo-300 transition-colors" title="Mark Attendance">
                                                <span class="material-symbols-outlined text-base">how_to_reg</span>
                                            </button>
                                        ` : ''}

                                        ${['scheduled', 'starting_soon', 'live'].includes(cls.status) ? `
                                            <button data-action="join-class" data-id="${cls.id}" class="ml-1 px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] transition-all">
                                                Join
                                            </button>
                                        ` : ''}
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}
