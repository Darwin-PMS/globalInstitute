import { escapeHtml, formatTime, toast } from '../lib/ui.js';
import { withButtonLoading } from '../../../ui/loading.js';

/**
 * The stage: what the room looks like right now and the one action that matters.
 *
 * Rendered from the state the shell already holds, so switching to this panel
 * costs no request. Re-renders whenever room state changes.
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

    /** Called by the shell when state changes, so the stage never goes stale. */
    onStateChange() {
        this.paint();
    }

    paint() {
        const { room, klass, me } = this.shell.snapshot();
        const schedule = room.schedule || {};

        this.stopCountdown();

        this.mount.innerHTML = `
            <div class="space-y-5">
                <section class="rounded-2xl border p-6 sm:p-8 ${this.tone(room.state)}">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="space-y-3 min-w-0">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-300">
                                ${escapeHtml(klass.context || 'Live class')}
                            </p>
                            <h2 class="text-xl sm:text-2xl font-black text-white">${escapeHtml(klass.title)}</h2>

                            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-300">
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

                            ${room.cancellation_reason ? `
                                <div class="mt-2 p-3 rounded-xl bg-rose-950/40 border border-rose-500/30 text-xs text-rose-200">
                                    <span class="font-semibold">Cancelled:</span> ${escapeHtml(room.cancellation_reason)}
                                </div>
                            ` : ''}
                        </div>

                        <div class="lg:w-72 flex-shrink-0 space-y-3">
                            <div id="stage-countdown" class="text-xs font-semibold lg:text-right"></div>
                            ${this.actionButton(room, me)}
                            <p class="text-[11px] leading-relaxed lg:text-right ${me.can_enter ? 'text-emerald-300' : 'text-slate-400'}">
                                ${escapeHtml(me.entry_message || (me.can_enter ? 'You can enter the room now.' : ''))}
                            </p>
                        </div>
                    </div>
                </section>

                ${klass.description ? `
                    <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">About this class</h3>
                        <p class="text-xs text-slate-300 leading-relaxed whitespace-pre-line">${escapeHtml(klass.description)}</p>
                    </section>
                ` : ''}

                ${me.can_control_room ? this.hostControls(room) : ''}
            </div>
        `;

        this.bind(room, me);
        this.startCountdown(schedule, room);
    }

    tone(state) {
        return {
            live: 'bg-gradient-to-br from-emerald-950/60 via-slate-900 to-slate-950 border-emerald-500/30',
            open: 'bg-gradient-to-br from-amber-950/40 via-slate-900 to-slate-950 border-amber-500/25',
            cancelled: 'bg-gradient-to-br from-rose-950/40 via-slate-900 to-slate-950 border-rose-500/30',
            ended: 'bg-gradient-to-br from-slate-900 to-slate-950 border-slate-800',
            archived: 'bg-gradient-to-br from-slate-900 to-slate-950 border-slate-800',
        }[state] || 'bg-gradient-to-br from-indigo-950/50 via-slate-900 to-slate-950 border-indigo-500/25';
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
                class="w-full py-3 rounded-xl text-sm font-bold transition-all ${enabled
                    ? 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white shadow-lg shadow-emerald-600/30 hover:scale-[1.02] active:scale-[0.98]'
                    : 'bg-slate-800 text-slate-500 border border-slate-700 cursor-not-allowed'}">
                <span class="inline-flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg" aria-hidden="true">${room.is_live ? 'sensors' : 'videocam'}</span>
                    ${room.is_live ? 'Join live class' : 'Enter room'}
                </span>
            </button>
        `;
    }

    hostControls(room) {
        return `
            <section class="bg-slate-900 border border-indigo-500/25 rounded-2xl p-5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-300 mb-3">Host controls</h3>
                <div class="flex flex-wrap gap-2">
                    ${!room.is_live && !room.is_finished ? `
                        <button type="button" id="stage-start"
                            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition-colors">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">play_arrow</span> Start class
                            </span>
                        </button>
                    ` : ''}

                    ${room.is_live ? `
                        <button type="button" id="stage-end"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-rose-300 text-xs font-bold border border-slate-700 transition-colors">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">stop_circle</span> End class
                            </span>
                        </button>
                    ` : ''}

                    <button type="button" id="stage-settings"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition-colors">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">tune</span> Room settings
                        </span>
                    </button>
                </div>
            </section>
        `;
    }

    bind(room, me) {
        this.mount.querySelector('#stage-enter')?.addEventListener('click', (e) => this.enter(e.currentTarget));
        this.mount.querySelector('#stage-start')?.addEventListener('click', () => this.shell.startClass());
        this.mount.querySelector('#stage-end')?.addEventListener('click', () => this.shell.endClass());
        this.mount.querySelector('#stage-settings')?.addEventListener('click', () => this.shell.openSettings());
    }

    enter(btn) {
        // withButtonLoading returns immediately if the button is already busy,
        // which is the duplicate-click guard for joining.
        return withButtonLoading(btn, () => this.performEnter(), 'Authorising…');
    }

    async performEnter() {
        try {
            // The meeting link is only issued by this call, after the server
            // re-checks membership and the join window.
            const res = await this.api.enter();

            if (res?.data?.meeting_url) {
                window.open(res.data.meeting_url, '_blank', 'noopener');
                toast('Opening the meeting room…', 'success');
            } else {
                toast('No meeting link has been set for this class yet.', 'warning');
            }

            if (res?.data?.room) this.shell.applyRoomState(res.data.room);
            this.shell.startHeartbeat();
        } catch (err) {
            toast(err.message || 'Unable to enter the room.', 'error');
        }
    }

    /* -------------------------------------------------------- countdown */

    startCountdown(schedule, room) {
        const el = this.mount.querySelector('#stage-countdown');
        if (!el || room.is_finished || room.state === 'cancelled') return;

        const target = new Date(room.is_live ? schedule.ends_at : schedule.starts_at);
        if (Number.isNaN(target.getTime())) return;

        const tick = () => {
            const diff = target.getTime() - Date.now();

            if (room.is_live) {
                el.className = 'text-xs font-semibold lg:text-right text-emerald-300';
                el.textContent = diff > 0 ? `Ends in ${this.human(diff)}` : 'Running over time';
                return;
            }

            if (diff <= 0) {
                el.className = 'text-xs font-semibold lg:text-right text-amber-300';
                el.textContent = 'Starting now';
                return;
            }

            el.className = 'text-xs font-semibold lg:text-right text-indigo-300';
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
