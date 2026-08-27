export function renderAttendanceTable(students = [], stats = {}) {
    return `
        <div class="space-y-4">
            <!-- Attendance Stats Header -->
            <div class="grid grid-cols-4 gap-3 p-4 bg-slate-950/80 rounded-xl border border-slate-800 text-center">
                <div>
                    <div class="text-[10px] uppercase tracking-wider font-semibold text-slate-400">Total</div>
                    <div class="text-base font-bold text-slate-100">${stats.total || students.length}</div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider font-semibold text-emerald-400">Present</div>
                    <div class="text-base font-bold text-emerald-400">${stats.present || 0}</div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider font-semibold text-amber-400">Late</div>
                    <div class="text-base font-bold text-amber-400">${stats.late || 0}</div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider font-semibold text-rose-400">Absent</div>
                    <div class="text-base font-bold text-rose-400">${stats.absent || 0}</div>
                </div>
            </div>

            <!-- Quick Bulk Tools -->
            <div class="flex items-center justify-between gap-2 py-2">
                <span class="text-xs font-semibold text-slate-300">Mark Student Status:</span>
                <div class="flex items-center gap-2">
                    <button type="button" id="mark-all-present" class="px-2.5 py-1 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 rounded-lg text-xs font-medium transition-colors">
                        Mark All Present
                    </button>
                    <button type="button" id="mark-all-absent" class="px-2.5 py-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 rounded-lg text-xs font-medium transition-colors">
                        Mark All Absent
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-slate-950/60 border border-slate-800 rounded-xl overflow-hidden">
                <div class="divide-y divide-slate-800/80">
                    ${students.map(s => `
                        <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs attendance-row" data-student-id="${s.student_id}">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-indigo-400 text-xs">
                                    ${s.name ? s.name.charAt(0) : 'S'}
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-200">${s.name}</div>
                                    <div class="text-[10px] text-slate-500 font-mono">${s.student_code || 'ID: ' + s.student_id}</div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <select class="status-select bg-slate-900 border border-slate-700 text-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:border-indigo-500 outline-none">
                                    <option value="present" ${s.status === 'present' ? 'selected' : ''}>Present</option>
                                    <option value="late" ${s.status === 'late' ? 'selected' : ''}>Late</option>
                                    <option value="absent" ${s.status === 'absent' ? 'selected' : ''}>Absent</option>
                                    <option value="partial" ${s.status === 'partial' ? 'selected' : ''}>Partial</option>
                                </select>

                                <input type="text" placeholder="Notes (optional)" value="${s.notes || ''}" class="notes-input bg-slate-900 border border-slate-700 text-slate-200 rounded-lg text-xs px-2.5 py-1.5 w-32 sm:w-40 focus:border-indigo-500 outline-none">
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
}
