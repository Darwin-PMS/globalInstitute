import { RoomApi } from './lib/api.js';
import { RoomChannel } from './lib/channel.js';
import { escapeHtml, errorState, emptyState, formatTime, roomStateBadge, toast } from './lib/ui.js';
import { skeletons, setButtonLoading, withButtonLoading, showPageLoading, hidePageLoading } from '../../ui/loading.js';

/**
 * The Live Class Room shell.
 *
 * Owns the header, the tab strip and the room's state. Panels are loaded on
 * demand with dynamic import(), so opening a room downloads the stage and
 * nothing else; each other panel arrives the first time its tab is used.
 */
export class RoomApp {
    constructor(config) {
        this.config = config;
        this.api = new RoomApi(config.roomKey, config.csrfToken, config.baseUrl || '');
        this.channel = null;

        this.state = { room: null, klass: null, me: null, panels: [] };
        this.activePanel = null;
        this.activeKey = null;
        this.panelCache = new Map();
        this.heartbeatTimer = null;

        this.els = {
            header: document.getElementById('room-header'),
            stage: document.getElementById('room-stage'),
            tabs: document.getElementById('room-tabs'),
            panel: document.getElementById('room-panel'),
        };
    }

    /** Panel key → dynamic import. Vite splits each into its own chunk. */
    static PANEL_LOADERS = {
        stage: () => import('./panels/livekit-stage.js').then((m) => m.default),
        'stage-legacy': () => import('./panels/stage.js').then((m) => m.default),
        chat: () => import('./panels/chat.js').then((m) => m.default),
        materials: () => import('./panels/resources.js').then((m) => m.MaterialsPanel),
        recordings: () => import('./panels/resources.js').then((m) => m.RecordingsPanel),
        assignments: () => import('./panels/resources.js').then((m) => m.AssignmentsPanel),
        roster: () => import('./panels/roster.js').then((m) => m.default),
        attendance: () => import('./panels/attendance.js').then((m) => m.default),
        activity: () => import('./panels/activity.js').then((m) => m.default),
    };

    async boot() {
        let res;
        try {
            res = await this.api.bootstrap();
        } catch (err) {
            this.renderFatal(err);
            return;
        }
        if (!res) return;

        const data = res.data || {};
        this.state.room = data.room;
        this.state.klass = data.class;
        this.state.me = data.me;
        this.state.panels = data.panels || [];

        this.channel = new RoomChannel(this.api, data.poll_intervals || {});
        this.channel.on('state', (payload) => this.applyRoomState(payload.room, payload.online_count));
        this.channel.on('session-expired', () => this.handleSessionExpired());
        this.channel.start('state');

        this.renderHeader();
        this.renderTabs();

        // The stage is the landing panel and needs no extra request.
        await this.openPanel('stage');

        // Anyone already inside keeps their presence alive.
        if (this.state.me.can_enter) this.startHeartbeat();

        this.bindLifecycle();
    }

    /* --------------------------------------------------------------- state */

    snapshot() {
        return { room: this.state.room, klass: this.state.klass, me: this.state.me };
    }

    applyRoomState(room, onlineCount = null) {
        if (!room) return;

        const previous = this.state.room || {};
        this.state.room = room;

        if (onlineCount !== null) {
            const el = document.getElementById('stage-online');
            if (el) el.textContent = onlineCount + ' online';
            this.state.room.participant_count = onlineCount;
        }

        const stateMoved = previous.state !== room.state;
        const hostMoved = previous.host_present !== room.host_present;

        if (stateMoved || hostMoved) {
            this.renderHeader();
            this.activePanel?.onStateChange?.();

            if (stateMoved && room.state === 'live') {
                toast('The class is now live.', 'success');
            }
            if (stateMoved && room.state === 'ended') {
                toast('This class has ended.', 'info');
                this.stopHeartbeat();
            }
        }
    }

    /* -------------------------------------------------------------- header */

    renderHeader() {
        const { room, klass, me } = this.state;

        this.els.header.innerHTML = `
            <div class="max-w-[1400px] mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between gap-4">
                <div class="min-w-0 flex items-center gap-3.5">
                    <a href="${escapeHtml(this.config.exitUrl)}" data-room-exit
                       class="flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/[0.06] transition-all duration-200"
                       aria-label="Leave the room">
                        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">arrow_back</span>
                    </a>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            ${roomStateBadge(room.state, room.state_label)}
                            <span class="text-[11px] text-slate-500 truncate max-w-[180px]">${escapeHtml(klass.context || '')}</span>
                        </div>
                        <h1 class="text-[15px] font-bold text-white truncate mt-0.5 tracking-[-0.01em]">
                            ${escapeHtml(klass.title)}
                        </h1>
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-shrink-0">
                    <span class="hidden sm:inline-flex items-center gap-1.5 text-[11px] text-slate-500 font-medium">
                        <span class="material-symbols-outlined text-[15px]" aria-hidden="true">groups</span>
                        <span data-room-online>${room.participant_count || 0} online</span>
                    </span>
                    <span class="hidden md:inline-flex px-2.5 py-1 rounded-lg bg-white/[0.04] border border-white/[0.06] text-[11px] font-semibold text-slate-300 tracking-wide uppercase">
                        ${escapeHtml(me.role_label)}
                    </span>
                </div>
            </div>
        `;

        this.els.header.querySelector('[data-room-exit]')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.exit();
        });
    }

    /* ---------------------------------------------------------------- tabs */

    renderTabs() {
        this.els.tabs.innerHTML = this.state.panels.map((p) => `
            <button type="button" role="tab" data-panel="${p.key}" aria-selected="false"
                class="tab-btn flex-shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all duration-200
                       bg-white/[0.02] border border-white/[0.06] text-slate-400 hover:text-slate-200 hover:bg-white/[0.05] hover:border-white/[0.1]">
                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">${p.icon}</span>
                ${escapeHtml(p.label)}
            </button>
        `).join('');

        this.els.tabs.querySelectorAll('[data-panel]').forEach((btn) => {
            btn.addEventListener('click', () => this.openPanel(btn.dataset.panel));
        });
    }

    markActiveTab(key) {
        this.els.tabs.querySelectorAll('[data-panel]').forEach((btn) => {
            const active = btn.dataset.panel === key;
            btn.setAttribute('aria-selected', active ? 'true' : 'false');

            if (active) {
                btn.className = `tab-btn flex-shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all duration-200
                    bg-indigo-500/15 border border-indigo-500/30 text-indigo-300 shadow-[0_0_12px_-4px_rgba(99,102,241,0.3)]`;
            } else {
                btn.className = `tab-btn flex-shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all duration-200
                    bg-white/[0.02] border border-white/[0.06] text-slate-400 hover:text-slate-200 hover:bg-white/[0.05] hover:border-white/[0.1]`;
            }
        });
    }

    /* -------------------------------------------------------------- panels */

    async openPanel(key) {
        if (this.activeKey === key) return;

        const loader = RoomApp.PANEL_LOADERS[key];
        if (!loader) return;

        this.activePanel?.destroy?.();
        this.activePanel = null;

        this.markActiveTab(key);
        this.activeKey = key;

        const mount = key === 'stage' ? this.els.stage : this.els.panel;

        if (key !== 'stage') {
            this.els.panel.innerHTML = this.loadingBlock(key);
            this.els.panel.classList.add('panel-enter');
            setTimeout(() => this.els.panel.classList.remove('panel-enter'), 500);
        }

        let PanelClass = this.panelCache.get(key);
        if (!PanelClass) {
            try {
                PanelClass = await loader();
                this.panelCache.set(key, PanelClass);
            } catch (err) {
                mount.innerHTML = errorState('This section failed to load. Check your connection and try again.');
                return;
            }
        }

        const panel = new PanelClass({
            api: this.api,
            channel: this.channel,
            capabilities: this.state.me,
            userId: this.config.user.id,
            mount,
            shell: this,
        });

        this.activePanel = panel;

        try {
            await panel.render();
        } catch (err) {
            mount.innerHTML = errorState(err.message || 'This section could not be displayed.');
        }

        if (key !== 'stage') this.els.panel.focus({ preventScroll: true });
    }

    loadingBlock(key = null) {
        const byPanel = {
            chat: () => skeletons.chat({ rows: 5 }),
            materials: () => skeletons.cards({ count: 3, media: false }),
            recordings: () => skeletons.cards({ count: 3, media: false }),
            assignments: () => skeletons.cards({ count: 3, media: false }),
            roster: () => skeletons.roster({ rows: 6 }),
            attendance: () => skeletons.roster({ rows: 6 }),
            activity: () => skeletons.list({ rows: 5 }),
        };

        return (byPanel[key] || (() => skeletons.list({ rows: 4 })))();
    }

    /* ----------------------------------------------------- host lifecycle */

    async startClass() {
        const btn = document.getElementById('stage-start');
        if (btn) return withButtonLoading(btn, () => this.performStart(), 'Starting…');
        return this.performStart();
    }

    async performStart() {
        try {
            const res = await this.api.start();
            if (res?.data?.room) this.applyRoomState(res.data.room);
            toast(res?.message || 'The class is live.', 'success');
            this.startHeartbeat();
        } catch (err) {
            toast(err.message || 'Unable to start the class.', 'error');
        }
    }

    async endClass() {
        if (!window.confirm('End this class for everyone?')) return;

        const btn = document.getElementById('stage-end');
        if (btn) return withButtonLoading(btn, () => this.performEnd(), 'Ending…');
        return this.performEnd();
    }

    async performEnd() {
        try {
            const res = await this.api.end();
            if (res?.data?.room) this.applyRoomState(res.data.room);
            toast(res?.message || 'The class has ended.', 'info');
        } catch (err) {
            toast(err.message || 'Unable to end the class.', 'error');
        }
    }

    openSettings() {
        const settings = this.state.room.settings || {};

        const toggles = [
            ['chat_enabled', 'Chat'],
            ['announcements_enabled', 'Announcements'],
            ['materials_visible', 'Show materials'],
            ['recordings_visible', 'Show recordings'],
            ['assignments_visible', 'Show assignments'],
            ['attendance_tracking', 'Track attendance'],
            ['allow_late_join', 'Allow late joining'],
        ];

        this.els.panel.innerHTML = `
            <div class="max-w-xl bg-[#0c1018] border border-white/[0.06] rounded-2xl p-6 space-y-5 shadow-2xl">
                <div>
                    <h2 class="text-sm font-bold text-white">Room settings</h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">These apply to this class only.</p>
                </div>

                <div class="space-y-3">
                    ${toggles.map(([key, label]) => `
                        <label class="flex items-center justify-between gap-4 cursor-pointer group">
                            <span class="text-xs text-slate-300 group-hover:text-white transition-colors">${label}</span>
                            <input type="checkbox" data-setting="${key}" ${settings[key] ? 'checked' : ''}
                                   class="w-4 h-4 rounded border-slate-700 bg-slate-950 text-indigo-500 focus:ring-indigo-500/30 focus:ring-offset-0">
                        </label>
                    `).join('')}

                    <label class="flex items-center justify-between gap-4">
                        <span class="text-xs text-slate-300">Lobby opens (minutes before)</span>
                        <input type="number" min="0" max="180" data-setting="lobby_opens_minutes_before"
                               value="${settings.lobby_opens_minutes_before ?? 15}"
                               class="w-20 bg-slate-950 border border-white/[0.06] text-slate-200 text-xs rounded-xl px-2.5 py-1.5 outline-none focus:border-indigo-500/50 transition-colors">
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-white/[0.06]">
                    <button type="button" id="settings-cancel"
                        class="px-4 py-2 rounded-xl bg-white/[0.04] hover:bg-white/[0.08] text-slate-300 text-xs font-semibold transition-all border border-white/[0.06]">Cancel</button>
                    <button type="button" id="settings-save"
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-all shadow-lg shadow-indigo-500/20">Save settings</button>
                </div>
            </div>
        `;

        this.activePanel?.destroy?.();
        this.activePanel = null;
        this.activeKey = 'settings';
        this.markActiveTab('settings');

        this.els.panel.querySelector('#settings-cancel').addEventListener('click', () => {
            this.activeKey = null;
            this.openPanel('activity');
        });

        this.els.panel.querySelector('#settings-save').addEventListener('click', async (e) => {
            const btn = e.currentTarget;
            const payload = {};

            this.els.panel.querySelectorAll('[data-setting]').forEach((input) => {
                payload[input.dataset.setting] = input.type === 'checkbox'
                    ? input.checked
                    : Number(input.value);
            });

            btn.disabled = true;
            btn.textContent = 'Saving…';

            try {
                const res = await this.api.updateSettings(payload);
                if (res?.data?.room) this.state.room = res.data.room;
                if (res?.data?.me) this.state.me = { ...this.state.me, ...res.data.me };

                toast(res?.message || 'Settings saved.', 'success');

                this.renderTabs();
                this.activeKey = null;
                await this.openPanel('stage');
            } catch (err) {
                toast(err.message || 'Unable to save settings.', 'error');
                btn.disabled = false;
                btn.textContent = 'Save settings';
            }
        });
    }

    /* ----------------------------------------------------------- presence */

    startHeartbeat() {
        if (this.heartbeatTimer) return;

        const beat = () => {
            if (document.hidden) return;
            this.api.heartbeat().catch(() => {});
        };

        beat();
        this.heartbeatTimer = setInterval(beat, 45000);
    }

    stopHeartbeat() {
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }
    }

    bindLifecycle() {
        window.addEventListener('pagehide', () => {
            if (navigator.sendBeacon) {
                const url = `${this.config.baseUrl || ''}/api/online-learning/rooms/${this.config.roomKey}/leave`;
                const form = new FormData();
                form.append('_token', this.config.csrfToken);
                navigator.sendBeacon(url, form);
            }
        });
    }

    async exit() {
        this.stopHeartbeat();
        this.channel?.destroy();

        try {
            await this.api.leave();
        } catch {
            // Leaving is best-effort
        }

        window.location.href = this.config.exitUrl;
    }

    handleSessionExpired() {
        this.stopHeartbeat();
        toast('Your session has expired. Redirecting to sign in…', 'warning');
        setTimeout(() => { window.location.href = '/login'; }, 1500);
    }

    renderFatal(err) {
        const forbidden = err.status === 403;
        const missing = err.status === 404;

        this.els.header.innerHTML = '';
        this.els.stage.innerHTML = '';
        this.els.tabs.innerHTML = '';

        this.els.panel.innerHTML = `
            <div class="max-w-md mx-auto mt-10">
                ${emptyState({
                    icon: forbidden ? 'lock' : missing ? 'meeting_room' : 'error',
                    title: forbidden
                        ? 'You are not in this class'
                        : missing ? 'This room no longer exists' : 'The room could not be opened',
                    description: err.message || '',
                    action: `<a href="${escapeHtml(this.config.exitUrl)}"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition-all shadow-lg shadow-indigo-500/20">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">arrow_back</span> Back to my classes</a>`,
                })}
            </div>
        `;
    }
}
