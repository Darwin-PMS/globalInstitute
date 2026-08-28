import { escapeHtml, formatTime } from '../lib/ui.js';
import { withButtonLoading } from '../../../ui/loading.js';

/**
 * Legacy Stage Panel — Lobby / pre-LiveKit view.
 *
 * Shows class info, join button (opens external meeting), and host controls.
 * Used when the class is not using LiveKit.
 */
export default class StagePanel {
    constructor(ctx) {
        this.api = ctx.api;
        this.mount = ctx.mount;
        this.shell = ctx.shell;
        this.countdownTimer = null;
    }

    async render() {
        this.paint();
    }

    onStateChange() {
        this.paint();
    }

    paint() {
        const { room, klass, me } = this.shell.snapshot();
        const schedule = room.schedule || {};
        const isHost = me.role === 'host' || me.role === 'moderator';

        this.stopCountdown();

        this.mount.innerHTML = `
            <div class="cr-join-stage" style="align-items:stretch;padding:0">
                <div class="cr-join-bg"></div>
                <div style="position:relative;z-index:1;max-width:600px;margin:0 auto;padding:var(--cr-space-8) var(--cr-space-6)">
                    <div style="text-align:center;margin-bottom:var(--cr-space-6)">
                        <div class="cr-join-ring" style="margin:0 auto var(--cr-space-4)">
                            <span class="material-symbols-outlined text-[36px] text-indigo-400">${isHost ? 'school' : 'person'}</span>
                        </div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-300 mb-2">
                            ${escapeHtml(klass.context || 'Live class')}
                        </p>
                        <h2 class="text-xl font-black text-white">${escapeHtml(klass.title)}</h2>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-xs text-slate-300 mb-6">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base text-slate-400" aria-hidden="true">person</span>
                            ${escapeHtml(klass.host_name || 'Instructor')}
                            ${room.host_present ? '<span class="text-emerald-400 font-semibold">· present</span>' : ''}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base text-slate-400" aria-hidden="true">schedule</span>
                            ${formatTime(schedule.starts_at)} – ${formatTime(schedule.ends_at)}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base text-slate-400" aria-hidden="true">groups</span>
                            <span id="stage-online">${room.participant_count || 0}</span> in room
                        </span>
                    </div>

                    <div id="stage-countdown" class="text-xs font-semibold text-center mb-4"></div>

                    ${this.actionButton(room, me)}

                    <p class="text-[11px] leading-relaxed text-center mt-3 ${me.can_enter ? 'text-emerald-300' : 'text-slate-400'}">
                        ${escapeHtml(me.entry_message || (me.can_enter ? 'You can enter the room now.' : ''))}
                    </p>

                    ${room.cancellation_reason ? `
                        <div class="mt-4 p-3 rounded-xl bg-rose-950/40 border border-rose-500/30 text-xs text-rose-200 text-center">
                            <span class="font-semibold">Cancelled:</span> ${escapeHtml(room.cancellation_reason)}
                        </div>
                    ` : ''}

                    ${isHost ? this.hostControls(room) : ''}
                </div>
            </div>
        `;

        this.bind(room, me);
        this.startCountdown(schedule, room);
    }

    actionButton(room, me) {
        if (room.state === 'cancelled') {
            return `<div class="w-full py-3 rounded-xl bg-slate-800 text-slate-500 text-sm font-bold text-center border border-slate-700">Class cancelled</div>`;
        }
        if (room.is_finished) {
            return `<div class="w-full py-3 rounded-xl bg-slate-800 text-slate-400 text-sm font-bold text-center border border-slate-700">Class ended</div>`;
        }

        const enabled = me.can_enter;
        return `
            <button type="button" id="stage-enter" ${enabled ? '' : 'disabled'}
                class="cr-join-btn" style="width:100%;justify-content:center;${enabled ? '' : 'opacity:0.5;cursor:not-allowed'}">
                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">${room.is_live ? 'sensors' : 'videocam'}</span>
                ${room.is_live ? 'Join live class' : 'Enter room'}
            </button>
        `;
    }

    hostControls(room) {
        return `
            <div style="margin-top:var(--cr-space-6);padding:var(--cr-space-4);background:rgba(255,255,255,0.03);border:1px solid var(--cr-border);border-radius:var(--cr-radius-lg)">
                <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-300 mb-3">Host Controls</h3>
                <div class="flex flex-wrap gap-2">
                    ${!room.is_live && !room.is_finished ? `
                        <button type="button" id="stage-start" class="cr-modal-btn primary">
                            <span class="material-symbols-outlined text-[14px]" aria-hidden="true">play_arrow</span> Start class
                        </button>
                    ` : ''}
                    ${room.is_live ? `
                        <button type="button" id="stage-end" class="cr-modal-btn danger">
                            <span class="material-symbols-outlined text-[14px]" aria-hidden="true">stop_circle</span> End class
                        </button>
                    ` : ''}
                    <button type="button" id="stage-settings" class="cr-modal-btn secondary">
                        <span class="material-symbols-outlined text-[14px]" aria-hidden="true">tune</span> Settings
                    </button>
                </div>
            </div>
        `;
    }

    bind(room, me) {
        this.mount.querySelector('#stage-enter')?.addEventListener('click', (e) => this.enter(e.currentTarget));
        this.mount.querySelector('#stage-start')?.addEventListener('click', () => this.shell.startClass());
        this.mount.querySelector('#stage-end')?.addEventListener('click', () => this.shell.endClass());
        this.mount.querySelector('#stage-settings')?.addEventListener('click', () => this.shell.openSettings());
    }

    enter(btn) {
        return withButtonLoading(btn, () => this.performEnter(), 'Authorising…');
    }

    async performEnter() {
        try {
            const res = await this.api.enter();
            if (res?.data?.meeting_url) {
                window.open(res.data.meeting_url, '_blank', 'noopener');
            }
            if (res?.data?.room) this.shell.applyRoomState(res.data.room);
            this.shell.startHeartbeat();
        } catch (err) {
            const { toast } = await import('../lib/ui.js');
            toast(err.message || 'Unable to enter the room.', 'error');
        }
    }

    startCountdown(schedule, room) {
        const el = this.mount.querySelector('#stage-countdown');
        if (!el || room.is_finished || room.state === 'cancelled') return;

        const target = new Date(room.is_live ? schedule.ends_at : schedule.starts_at);
        if (Number.isNaN(target.getTime())) return;

        const tick = () => {
            const diff = target.getTime() - Date.now();
            if (room.is_live) {
                el.className = 'text-xs font-semibold text-center mb-4 text-emerald-300';
                el.textContent = diff > 0 ? `Ends in ${this.human(diff)}` : 'Running over time';
                return;
            }
            if (diff <= 0) {
                el.className = 'text-xs font-semibold text-center mb-4 text-amber-300';
                el.textContent = 'Starting now';
                return;
            }
            el.className = 'text-xs font-semibold text-center mb-4 text-indigo-300';
            el.textContent = `Starts in ${this.human(diff)}`;
        };

        tick();
        this.countdownTimer = setInterval(tick, 1000);
    }

    human(ms) {
        const total = Math.floor(ms / 1000);
        const d = Math.floor(total / 86400);
        const h = Math.floor((total % 86400) / 3600);
        const m = Math.floor((total % 3600) / 60);
        const s = total % 60;
        if (d > 0) return `${d}d ${h}h`;
        if (h > 0) return `${h}h ${m}m`;
        if (m > 0) return `${m}m ${String(s).padStart(2, '0')}s`;
        return `${s}s`;
    }

    stopCountdown() {
        if (this.countdownTimer) {
            clearInterval(this.countdownTimer);
            this.countdownTimer = null;
        }
    }

    destroy() {
        this.stopCountdown();
    }
}
