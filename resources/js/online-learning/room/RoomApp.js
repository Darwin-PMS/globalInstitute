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
        stage: () => import('./panels/stage.js').then((m) => m.default),
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

    /**
     * Apply a fresh room state. Only re-renders when something visible moved, so
     * a poll that changes nothing does not churn the DOM.
     */
    applyRoomState(room, onlineCount = null) {
        if (!room) return;

        const previous = this.state.room || {};
        this.state.room = room;

        if (onlineCount !== null) {
            const el = document.getElementById('stage-online');
            if (el) el.textContent = onlineCount;
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
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-4">
                <div class="min-w-0 flex items-center gap-4">
                    <a href="${escapeHtml(this.config.exitUrl)}" data-room-exit
                       class="flex-shrink-0 p-2 -ml-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                       aria-label="Leave the room">
                        <span class="material-symbols-outlined text-xl" aria-hidden="true">arrow_back</span>
                    </a>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            ${roomStateBadge(room.state, room.state_label)}
                            <span class="text-[11px] text-slate-500 truncate">${escapeHtml(klass.context || '')}</span>
                        </div>
                        <h1 class="text-sm sm:text-base font-bold text-slate-100 truncate mt-0.5">
                            ${escapeHtml(klass.title)}
                        </h1>
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-shrink-0">
                    <span class="hidden sm:inline-flex items-center gap-1.5 text-[11px] text-slate-400">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">groups</span>
                        <span data-room-online>${room.participant_count || 0}</span>
                    </span>
                    <span class="hidden md:inline-flex px-2.5 py-1 rounded-full bg-slate-800 border border-slate-700 text-[11px] font-semibold text-slate-300">
                        ${escapeHtml(me.role_label)}
                    </span>
                </div>
            </div>
        `;

        // Leaving the room releases presence rather than going silent.
        this.els.header.querySelector('[data-room-exit]')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.exit();
        });
    }

    /* ---------------------------------------------------------------- tabs */

    renderTabs() {
        this.els.tabs.innerHTML = this.state.panels.map((p) => `
            <button type="button" role="tab" data-panel="${p.key}" aria-selected="false"
                class="flex-shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold border transition-colors
                       bg-slate-900 border-slate-800 text-slate-400 hover:text-slate-200 hover:border-slate-700">
                <span class="material-symbols-outlined text-base" aria-hidden="true">${p.icon}</span>
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
            btn.className = `flex-shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold border transition-colors ${active
                ? 'bg-indigo-600 border-indigo-500 text-white'
                : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-slate-200 hover:border-slate-700'}`;
        });
    }

    /* -------------------------------------------------------------- panels */

    /**
     * Open a panel, importing its module the first time it is needed.
     *
     * The stage renders into its own region above the tabs; every other panel
     * shares the panel area below them.
     */
    async openPanel(key) {
        if (this.activeKey === key) return;

        const loader = RoomApp.PANEL_LOADERS[key];
        if (!loader) return;

        // Tear the previous panel down so its subscriptions and timers stop.
        this.activePanel?.destroy?.();
        this.activePanel = null;

        this.markActiveTab(key);
        this.activeKey = key;

        const mount = key === 'stage' ? this.els.stage : this.els.panel;

        if (key !== 'stage') {
            this.els.panel.innerHTML = this.loadingBlock(key);
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

        // The stage stays visible; other panels take focus for keyboard users.
        if (key !== 'stage') this.els.panel.focus({ preventScroll: true });
    }

    /**
     * Shown while a panel's module and data are in flight. Uses the shape that
     * best matches the panel so the swap does not shift the layout.
     */
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

    /** Room settings, edited in place rather than on another screen. */
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
            <div class="max-w-xl bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
                <div>
                    <h2 class="text-sm font-bold text-slate-100">Room settings</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">These apply to this class only.</p>
                </div>

                <div class="space-y-3">
                    ${toggles.map(([key, label]) => `
                        <label class="flex items-center justify-between gap-4 cursor-pointer">
                            <span class="text-xs text-slate-300">${label}</span>
                            <input type="checkbox" data-setting="${key}" ${settings[key] ? 'checked' : ''}
                                   class="w-4 h-4 rounded border-slate-700 bg-slate-950 text-indigo-600">
                        </label>
                    `).join('')}

                    <label class="flex items-center justify-between gap-4">
                        <span class="text-xs text-slate-300">Lobby opens (minutes before)</span>
                        <input type="number" min="0" max="180" data-setting="lobby_opens_minutes_before"
                               value="${settings.lobby_opens_minutes_before ?? 15}"
                               class="w-20 bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-lg px-2.5 py-1.5 outline-none focus:border-indigo-500">
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-slate-800">
                    <button type="button" id="settings-cancel"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors">Cancel</button>
                    <button type="button" id="settings-save"
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-colors">Save settings</button>
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

                // Capabilities may have changed, so the tab strip is rebuilt.
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

    /** Release presence on the way out so the roster does not show ghosts. */
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
            // Leaving is best-effort; the stale-presence window covers the rest.
        }

        window.location.href = this.config.exitUrl;
    }

    handleSessionExpired() {
        this.stopHeartbeat();
        toast('Your session has expired. Redirecting to sign in…', 'warning');
        setTimeout(() => { window.location.href = '/login'; }, 1500);
    }

    /** Bootstrap failed — say why, and offer the way out. */
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
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition-colors">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">arrow_back</span> Back to my classes</a>`,
                })}
            </div>
        `;
    }
}
