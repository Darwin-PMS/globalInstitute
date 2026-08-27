import { skeletonRoster, emptyState, errorState, escapeHtml, formatDuration, panelHeading, toast } from '../lib/ui.js';
import { withButtonLoading } from '../../../ui/loading.js';

/**
 * Attendance panel (host only).
 *
 * The room measures presence, so this panel arrives pre-filled with a proposal
 * and asks the host to confirm rather than to type. Nothing is written until
 * they save, and the server re-derives the student mapping itself.
 */
export default class AttendancePanel {
    constructor(ctx) {
        this.api = ctx.api;
        this.mount = ctx.mount;
        this.rows = [];
    }

    async render() {
        this.mount.innerHTML = skeletonRoster(6);

        let res;
        try {
            res = await this.api.attendance();
        } catch (err) {
            this.mount.innerHTML = errorState(err.message || 'Unable to load attendance.', 'att-retry');
            this.mount.querySelector('#att-retry')?.addEventListener('click', () => this.render());
            return;
        }
        if (!res) return;

        const data = res.data || {};

        if (!data.tracking_enabled) {
            this.mount.innerHTML = panelHeading('how_to_reg', 'Attendance') + emptyState({
                icon: 'toggle_off',
                title: 'Attendance is not tracked',
                description: 'Turn attendance tracking on in room settings to record it for this class.',
            });
            return;
        }

        // Merge the saved roster with what presence suggests, so a host sees both
        // what is recorded and what actually happened.
        const proposalByUser = new Map((data.proposal || []).map((p) => [p.user_id, p]));

        this.rows = (data.roster || []).map((r) => {
            const measured = proposalByUser.get(r.user_id);
            return {
                user_id: r.user_id,
                name: r.name,
                code: r.code,
                saved_status: r.status,
                notes: r.notes || '',
                attended_seconds: measured?.attended_seconds ?? 0,
                attended_percent: measured?.attended_percent ?? 0,
                suggested: measured?.suggested_status ?? r.status,
            };
        }).filter((r) => r.user_id !== null);

        this.paint(data);
    }

    paint(data) {
        const stats = data.stats || {};

        if (!this.rows.length) {
            this.mount.innerHTML = panelHeading('how_to_reg', 'Attendance') + emptyState({
                icon: 'group_off',
                title: 'No students to mark',
                description: 'Nobody holds an active enrollment for this course and batch.',
            });
            return;
        }

        this.mount.innerHTML = `
            ${panelHeading('how_to_reg', 'Attendance',
                `<button type="button" id="att-apply-suggested"
                    class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-indigo-300 text-[11px] font-semibold border border-slate-700 transition-colors">
                    Apply measured
                </button>`)}

            <div class="grid grid-cols-4 gap-3 p-4 mb-4 bg-slate-950/70 border border-slate-800 rounded-xl text-center">
                ${this.stat('Marked', stats.total ?? 0, 'text-slate-100')}
                ${this.stat('Present', stats.present ?? 0, 'text-emerald-400')}
                ${this.stat('Late', stats.late ?? 0, 'text-amber-400')}
                ${this.stat('Absent', stats.absent ?? 0, 'text-rose-400')}
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl divide-y divide-slate-800/70 overflow-hidden">
                ${this.rows.map((r) => this.row(r)).join('')}
            </div>

            <div class="mt-5">
                <button type="button" id="att-save"
                    class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/25 transition-all">
                    Save attendance
                </button>
            </div>
        `;

        this.bind();
    }

    stat(label, value, tone) {
        return `
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">${label}</div>
                <div class="text-base font-black mt-0.5 tabular-nums ${tone}">${value}</div>
            </div>
        `;
    }

    row(r) {
        const statuses = ['present', 'late', 'partial', 'absent'];
        const differs = r.suggested !== r.saved_status;

        return `
            <div class="p-3.5 flex flex-col sm:flex-row sm:items-center gap-3" data-att-row="${r.user_id}">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-indigo-400 text-xs font-bold flex-shrink-0">
                        ${escapeHtml((r.name || '?').charAt(0).toUpperCase())}
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-slate-100 truncate">${escapeHtml(r.name)}</p>
                        <p class="text-[10px] text-slate-500">
                            ${r.code ? escapeHtml(r.code) + ' · ' : ''}in room ${formatDuration(r.attended_seconds)} (${r.attended_percent}%)
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    ${differs ? `<span class="text-[10px] text-amber-400 font-semibold" title="Measured presence differs from the saved mark">measured: ${escapeHtml(r.suggested)}</span>` : ''}

                    <label class="sr-only" for="att-status-${r.user_id}">Status for ${escapeHtml(r.name)}</label>
                    <select id="att-status-${r.user_id}" class="att-status bg-slate-950 border border-slate-700 text-slate-200 rounded-lg text-xs px-2.5 py-1.5 outline-none focus:border-indigo-500">
                        ${statuses.map((s) => `<option value="${s}" ${r.saved_status === s ? 'selected' : ''}>${s.charAt(0).toUpperCase() + s.slice(1)}</option>`).join('')}
                    </select>

                    <label class="sr-only" for="att-notes-${r.user_id}">Notes for ${escapeHtml(r.name)}</label>
                    <input type="text" id="att-notes-${r.user_id}" class="att-notes bg-slate-950 border border-slate-700 text-slate-200 rounded-lg text-xs px-2.5 py-1.5 w-28 sm:w-36 outline-none focus:border-indigo-500"
                           placeholder="Notes" value="${escapeHtml(r.notes)}">
                </div>
            </div>
        `;
    }

    bind() {
        this.mount.querySelector('#att-apply-suggested')?.addEventListener('click', () => {
            this.rows.forEach((r) => {
                const select = this.mount.querySelector(`#att-status-${r.user_id}`);
                if (select) select.value = r.suggested;
            });
            toast('Applied the measured attendance. Review, then save.', 'info');
        });

        const saveBtn = this.mount.querySelector('#att-save');
        saveBtn?.addEventListener('click', () => withButtonLoading(saveBtn, async () => {
            const records = [...this.mount.querySelectorAll('[data-att-row]')].map((row) => ({
                user_id: Number(row.dataset.attRow),
                status: row.querySelector('.att-status').value,
                notes: row.querySelector('.att-notes').value || null,
            }));

            try {
                const res = await this.api.saveAttendance(records);
                toast(res?.message || 'Attendance saved.', 'success');
                await this.render();
            } catch (err) {
                toast(err.message || 'Unable to save attendance.', 'error');
            }
        }, 'Saving…'));
    }

    destroy() {}
}
