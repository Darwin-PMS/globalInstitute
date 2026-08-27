import { formatDate, formatTime } from '../utils/date.js';
import { getStatusBadgeHTML } from '../utils/formatter.js';

export function renderClassCalendar(classes = []) {
    // Group classes by date
    const groupedByDate = {};
    classes.forEach(cls => {
        const d = cls.class_date ? cls.class_date.substring(0, 10) : 'unscheduled';
        if (!groupedByDate[d]) groupedByDate[d] = [];
        groupedByDate[d].push(cls);
    });

    const sortedDates = Object.keys(groupedByDate).sort();

    if (sortedDates.length === 0) {
        return `
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center text-slate-400 text-xs">
                No classes scheduled on the calendar.
            </div>
        `;
    }

    return `
        <div class="space-y-6">
            ${sortedDates.map(dateStr => `
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
                    <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-800">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 font-bold">
                            <span class="material-symbols-outlined text-xl">event</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-100">${formatDate(dateStr, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })}</h3>
                            <span class="text-[11px] text-slate-400">${groupedByDate[dateStr].length} classes scheduled</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        ${groupedByDate[dateStr].map(cls => `
                            <div data-action="view-details" data-id="${cls.id}" class="p-4 bg-slate-950/70 border border-slate-800/80 rounded-xl hover:border-indigo-500/40 transition-all cursor-pointer group">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-400">${cls.course?.title || 'Course'}</span>
                                    ${getStatusBadgeHTML(cls.status)}
                                </div>
                                <h4 class="text-xs font-bold text-slate-100 group-hover:text-indigo-300 transition-colors line-clamp-1 mb-2">${cls.title}</h4>
                                <div class="flex items-center gap-2 text-[11px] text-slate-400">
                                    <span class="material-symbols-outlined text-sm text-slate-500">schedule</span>
                                    <span>${formatTime(cls.start_time)} - ${formatTime(cls.end_time)}</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}
