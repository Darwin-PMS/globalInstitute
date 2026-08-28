import { escapeHtml, formatDuration, toast } from '../lib/ui.js';
import { withButtonLoading } from '../../../ui/loading.js';

/**
 * Attendance Panel — Live attendance tracking in the sidebar.
 *
 * Host-only. Shows measured presence with proposal system.
 * Students see their own attendance status.
 */
export default class AttendancePanel {
    constructor(ctx) {
        this.api = ctx.api;
        this.caps = ctx.capabilities;
        this.mount = ctx.mount;
        this.shell = ctx.shell;
        this.rows = [];
    }

    async render() {
        let res;
        try {
            res = await this.api.attendance();
        } catch (err) {
            this.mount.innerHTML = this.renderError(err.message || 'Unable to load attendance.');
            this.mount.querySelector('#att-retry')?.addEventListener('click', () => this.render());
            return;
        }
        if (!res) return;

        const data = res.data || {};

        if (!data.tracking_enabled) {
            this.mount.innerHTML = `
                <div class="cr-panel" style="height:100%;display:flex;flex-direction:column">
                    <div class="cr-panel-header">
                        <h2 class="cr-panel-title">
                            <span class="material-symbols-outlined" aria-hidden="true">how_to_reg</span>
                            Attendance
                        </h2>
                    </div>
                    <div class="cr-empty">
                        <div class="cr-empty-icon">
                            <span class="material-symbols-outlined">toggle_off</span>
                        </div>
                        <p class="cr-empty-title">Attendance is not tracked</p>
                        <p class="cr-empty-desc">Enable attendance tracking in room settings.</p>
                    </div>
                </div>
            `;
            return;
        }

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
            this.mount.innerHTML = `
                <div class="cr-panel" style="height:100%;display:flex;flex-direction:column">
                    <div class="cr-panel-header">
                        <h2 class="cr-panel-title">
                            <span class="material-symbols-outlined" aria-hidden="true">how_to_reg</span>
                            Attendance
                        </h2>
                    </div>
                    <div class="cr-empty">
                        <div class="cr-empty-icon">
                            <span class="material-symbols-outlined">group_off</span>
                        </div>
                        <p class="cr-empty-title">No students to mark</p>
                        <p class="cr-empty-desc">No active enrollments for this class.</p>
                    </div>
                </div>
            `;
            return;
        }

        this.mount.innerHTML = `
            <div class="cr-panel" style="height:100%;display:flex;flex-direction:column">
                <div class="cr-panel-header">
                    <h2 class="cr-panel-title">
                        <span class="material-symbols-outlined" aria-hidden="true">how_to_reg</span>
                        Attendance
                    </h2>
                    <button type="button" id="att-apply-suggested"
                        class="cr-modal-btn secondary" style="font-size:10px;padding:4px 10px">
                        Apply measured
                    </button>
                </div>

                <div class="cr-att-stats">
                    <div>
                        <div class="cr-att-stat-label">Marked</div>
                        <div class="cr-att-stat-value" style="color:var(--cr-text-primary)">${stats.total ?? 0}</div>
                    </div>
                    <div>
                        <div class="cr-att-stat-label">Present</div>
                        <div class="cr-att-stat-value" style="color:#34d399">${stats.present ?? 0}</div>
                    </div>
                    <div>
                        <div class="cr-att-stat-label">Late</div>
                        <div class="cr-att-stat-value" style="color:#fbbf24">${stats.late ?? 0}</div>
                    </div>
                    <div>
                        <div class="cr-att-stat-label">Absent</div>
                        <div class="cr-att-stat-value" style="color:#f87171">${stats.absent ?? 0}</div>
                    </div>
                </div>

                <div id="att-list" class="flex-1 overflow-y-auto" style="margin:0 calc(var(--cr-space-3) * -1);padding:0 var(--cr-space-3)">
                    ${this.rows.map((r) => this.row(r)).join('')}
                </div>

                <div style="padding-top:var(--cr-space-3);border-top:1px solid var(--cr-border-subtle);margin-top:var(--cr-space-3)">
                    <button type="button" id="att-save" class="cr-modal-btn primary" style="width:100%">
                        Save attendance
                    </button>
                </div>
            </div>
        `;

        this.bind();
    }

    row(r) {
        const statuses = ['present', 'late', 'partial', 'absent'];
        const differs = r.suggested !== r.saved_status;

        return `
            <div class="cr-att-row" data-att-row="${r.user_id}">
                <div class="cr-participant-avatar">
                    <div class="cr-avatar-sm">${this.getInitials(r.name)}</div>
                </div>
                <div class="cr-att-row-info">
                    <p class="text-xs font-semibold text-slate-100 truncate">${escapeHtml(r.name)}</p>
                    <p class="text-[10px] text-slate-500">
                        ${r.code ? escapeHtml(r.code) + ' · ' : ''}in room ${formatDuration(r.attended_seconds)} (${r.attended_percent}%)
                        ${differs ? `<span class="text-amber-400" title="Measured differs from saved"> · measured: ${escapeHtml(r.suggested)}</span>` : ''}
                    </p>
                </div>
                <select class="cr-att-status att-status" data-user="${r.user_id}" aria-label="Status for ${escapeHtml(r.name)}">
                    ${statuses.map((s) => `<option value="${s}" ${r.saved_status === s ? 'selected' : ''}>${s.charAt(0).toUpperCase() + s.slice(1)}</option>`).join('')}
                </select>
            </div>
        `;
    }

    getInitials(name) {
        if (!name) return '?';
        return name.split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase();
    }

    bind() {
        this.mount.querySelector('#att-apply-suggested')?.addEventListener('click', () => {
            this.rows.forEach((r) => {
                const select = this.mount.querySelector(`.att-status[data-user="${r.user_id}"]`);
                if (select) select.value = r.suggested;
            });
            toast('Applied the measured attendance. Review, then save.', 'info');
        });

        const saveBtn = this.mount.querySelector('#att-save');
        saveBtn?.addEventListener('click', () => withButtonLoading(saveBtn, async () => {
            const records = [...this.mount.querySelectorAll('[data-att-row]')].map((row) => ({
                user_id: Number(row.dataset.attRow),
                status: row.querySelector('.att-status').value,
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

    renderError(message) {
        return `
            <div class="cr-panel" style="height:100%;display:flex;flex-direction:column">
                <div class="cr-panel-header">
                    <h2 class="cr-panel-title">
                        <span class="material-symbols-outlined" aria-hidden="true">how_to_reg</span>
                        Attendance
                    </h2>
                </div>
                <div class="cr-error">
                    <div class="cr-error-icon">
                        <span class="material-symbols-outlined text-[22px] text-red-400">error</span>
                    </div>
                    <p class="text-xs text-red-200 mt-2 mb-4">${escapeHtml(message)}</p>
                    <button type="button" id="att-retry" class="cr-modal-btn secondary">Try again</button>
                </div>
            </div>
        `;
    }

    destroy() {}
}
