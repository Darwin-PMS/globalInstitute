import { RoomApi } from './lib/api.js';
import { RoomChannel } from './lib/channel.js';
import { escapeHtml, errorState, emptyState, formatTime, roomStateBadge, toast } from './lib/ui.js';
import { skeletons, setButtonLoading, withButtonLoading, showPageLoading, hidePageLoading } from '../../ui/loading.js';

/**
 * Live Class Room — Application Shell
 *
 * Orchestrates the classroom layout: header, video stage, sidebar panels,
 * bottom control bar, and mobile bottom sheet. Panels are lazy-loaded on
 * demand via dynamic import().
 *
 * Layout:
 *   Header
 *   ├── Stage (video/content) + Sidebar (tabbed panels)
 *   └── Control Bar (persistent bottom)
 */
export class RoomApp {
    constructor(config) {
        this.config = config;
        this.api = new RoomApi(config.roomKey, config.csrfToken, config.baseUrl || '');
        this.channel = null;

        this.state = { room: null, klass: null, me: null, panels: [] };
        this.stagePanel = null;
        this.sidebarPanel = null;
        this.activeKey = null;
        this.panelCache = new Map();
        this.heartbeatTimer = null;
        this.timerInterval = null;
        this.recordingTimerInterval = null;
        this.recordingStartTime = null;
        this.sidebarCollapsed = false;
        this.mobileSheetOpen = false;
        this._listeners = {};

        this.els = {
            header: document.getElementById('room-header'),
            stage: document.getElementById('room-stage'),
            sidebar: document.getElementById('room-sidebar'),
            sidebarTabs: document.getElementById('sidebar-tabs'),
            sidebarPanel: document.getElementById('sidebar-panel'),
            controls: document.getElementById('room-controls'),
            ctrlButtons: document.getElementById('ctrl-buttons'),
            mobileSheet: document.getElementById('mobile-sheet'),
            mobileSheetTitle: document.getElementById('mobile-sheet-title'),
            mobileSheetBody: document.getElementById('mobile-sheet-body'),
            modals: document.getElementById('room-modals'),
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

    /** Sidebar tab definitions (order matters). */
    static SIDEBAR_TABS = [
        { key: 'roster', icon: 'groups', label: 'Participants', roles: ['host', 'moderator'] },
        { key: 'chat', icon: 'chat', label: 'Chat' },
        { key: 'attendance', icon: 'how_to_reg', label: 'Attendance', roles: ['host', 'moderator'] },
        { key: 'activity', icon: 'history', label: 'Activity', roles: ['host', 'moderator'] },
        { key: 'materials', icon: 'folder_open', label: 'Resources' },
    ];

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
        this.renderSidebarTabs();
        this.renderControlBar();
        this.setControlsEnabled(false);
        this.bindGlobalEvents();

        // Open the stage (video) as the default view
        await this.openPanel('stage');

        // Start heartbeat for presence
        if (this.state.me.can_enter) this.startHeartbeat();

        this.bindLifecycle();
    }

    /* ═══════════════════════════════════════════════════════════ state */

    snapshot() {
        return { room: this.state.room, klass: this.state.klass, me: this.state.me };
    }

    applyRoomState(room, onlineCount = null) {
        if (!room) return;

        const previous = this.state.room || {};
        this.state.room = room;

        if (onlineCount !== null) {
            const el = document.getElementById('header-online-count');
            if (el) el.textContent = onlineCount;
            this.state.room.participant_count = onlineCount;
        }

        const stateMoved = previous.state !== room.state;
        const hostMoved = previous.host_present !== room.host_present;

        if (stateMoved || hostMoved) {
            this.renderHeader();
            this.renderControlBar();
            this.stagePanel?.onStateChange?.();
            this.sidebarPanel?.onStateChange?.();

            if (stateMoved && room.state === 'live') {
                toast('The class is now live.', 'success');
                this.startSessionTimer();
            }
            if (stateMoved && room.state === 'ended') {
                toast('This class has ended.', 'info');
                this.stopHeartbeat();
                this.stopSessionTimer();
            }
        }
    }

    /* ═══════════════════════════════════════════════════════════ header */

    renderHeader() {
        const { room, klass, me } = this.state;
        if (!room || !klass) return;

        const isHost = me.role === 'host' || me.role === 'moderator';
        const isLive = room.state === 'live';

        // State badge
        const badgeEl = document.getElementById('header-state-badge');
        if (badgeEl) badgeEl.innerHTML = roomStateBadge(room.state, room.state_label);

        // Context (course name)
        const ctxEl = document.getElementById('header-context');
        if (ctxEl) ctxEl.textContent = klass.context || '';

        // Title
        const titleEl = document.getElementById('header-title');
        if (titleEl) titleEl.textContent = klass.title || '';

        // Live badge
        const liveBadge = document.getElementById('header-live-badge');
        if (liveBadge) liveBadge.style.display = isLive ? 'inline-flex' : 'none';

        // Online count
        const countEl = document.getElementById('header-online-count');
        if (countEl) countEl.textContent = room.participant_count || 0;

        // End class button (host only, when live)
        const endBtn = document.getElementById('header-end-btn');
        if (endBtn) {
            endBtn.style.display = isHost && isLive ? 'inline-block' : 'none';
        }

        // Start session timer if live
        if (isLive) this.startSessionTimer();
    }

    startSessionTimer() {
        if (this.timerInterval) return;

        const timerEl = document.getElementById('header-timer');
        if (!timerEl) return;

        // Use server start time if available, otherwise use now
        const room = this.state.room;
        const startedAt = room.started_at ? new Date(room.started_at).getTime() : Date.now();

        const tick = () => {
            const elapsed = Math.max(0, Math.floor((Date.now() - startedAt) / 1000));
            const h = Math.floor(elapsed / 3600);
            const m = Math.floor((elapsed % 3600) / 60);
            const s = elapsed % 60;
            timerEl.textContent = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        };

        tick();
        this.timerInterval = setInterval(tick, 1000);
    }

    stopSessionTimer() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
    }

    /* ═══════════════════════════════════════════════════════ sidebar */

    renderSidebarTabs() {
        const { me } = this.state;
        const isHost = me.role === 'host' || me.role === 'moderator';

        const tabs = RoomApp.SIDEBAR_TABS.filter((tab) => {
            if (tab.roles && !isHost) return false;
            // Check if the panel is available from the server
            return this.state.panels.some((p) => p.key === tab.key);
        });

        this.els.sidebarTabs.innerHTML = tabs.map((tab) => `
            <button type="button" role="tab" data-sidebar-panel="${tab.key}"
                    aria-selected="false" class="cr-sidebar-tab"
                    aria-label="${tab.label}">
                <span class="material-symbols-outlined" aria-hidden="true">${tab.icon}</span>
                <span class="cr-sidebar-tab-label">${escapeHtml(tab.label)}</span>
                ${tab.key === 'chat' ? '<span id="chat-unread-badge" class="cr-tab-badge" style="display:none"></span>' : ''}
            </button>
        `).join('');

        this.els.sidebarTabs.querySelectorAll('[data-sidebar-panel]').forEach((btn) => {
            btn.addEventListener('click', () => this.toggleSidebarPanel(btn.dataset.sidebarPanel));
        });
    }

    markActiveSidebarTab(key) {
        this.els.sidebarTabs.querySelectorAll('[data-sidebar-panel]').forEach((btn) => {
            const active = btn.dataset.sidebarPanel === key;
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }

    toggleSidebarPanel(key) {
        // If clicking the already active panel, collapse sidebar
        if (this.activeKey === key && !this.isMobile()) {
            this.collapseSidebar();
            return;
        }
        this.openSidebarPanel(key);
    }

    async openSidebarPanel(key) {
        if (this.activeKey === key) return;

        const loader = RoomApp.PANEL_LOADERS[key];
        if (!loader) return;

        // Destroy previous sidebar panel only
        this.sidebarPanel?.destroy?.();
        this.sidebarPanel = null;

        this.markActiveSidebarTab(key);
        this.activeKey = key;

        // Expand sidebar on desktop
        if (!this.isMobile()) {
            this.expandSidebar();
        }

        const mount = this.isMobile() ? this.els.mobileSheetBody : this.els.sidebarPanel;

        if (this.isMobile()) {
            const tab = RoomApp.SIDEBAR_TABS.find((t) => t.key === key);
            this.openMobileSheet(tab?.label || key);
        }

        mount.innerHTML = this.loadingBlock(key);

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

        this.sidebarPanel = panel;

        try {
            await panel.render();
        } catch (err) {
            mount.innerHTML = errorState(err.message || 'This section could not be displayed.');
        }
    }

    collapseSidebar() {
        this.els.sidebar.classList.add('collapsed');
        this.sidebarCollapsed = true;
        this.sidebarPanel?.destroy?.();
        this.sidebarPanel = null;
        this.activeKey = null;
        this.markActiveSidebarTab(null);
    }

    expandSidebar() {
        this.els.sidebar.classList.remove('collapsed');
        this.sidebarCollapsed = false;
    }

    loadingBlock(key = null) {
        const byPanel = {
            chat: () => skeletons.chat?.({ rows: 5 }) || '<div class="cr-panel"><div class="cr-skeleton" style="height:200px"></div></div>',
            materials: () => skeletons.cards?.({ count: 3, media: false }) || '<div class="cr-panel"><div class="cr-skeleton" style="height:200px"></div></div>',
            recordings: () => skeletons.cards?.({ count: 3, media: false }) || '<div class="cr-panel"><div class="cr-skeleton" style="height:200px"></div></div>',
            assignments: () => skeletons.cards?.({ count: 3, media: false }) || '<div class="cr-panel"><div class="cr-skeleton" style="height:200px"></div></div>',
            roster: () => skeletons.roster?.({ rows: 6 }) || '<div class="cr-panel"><div class="cr-skeleton" style="height:200px"></div></div>',
            attendance: () => skeletons.roster?.({ rows: 6 }) || '<div class="cr-panel"><div class="cr-skeleton" style="height:200px"></div></div>',
            activity: () => skeletons.list?.({ rows: 5 }) || '<div class="cr-panel"><div class="cr-skeleton" style="height:200px"></div></div>',
        };

        return (byPanel[key] || (() => '<div class="cr-panel"><div class="cr-skeleton" style="height:200px"></div></div>'))();
    }

    /* ═══════════════════════════════════════════════════════════ stage (legacy panel loader) */

    async openPanel(key) {
        if (key === 'stage' && this.stagePanel) return;

        const loader = RoomApp.PANEL_LOADERS[key];
        if (!loader) return;

        if (key === 'stage') {
            this.stagePanel?.destroy?.();
            this.stagePanel = null;
        }

        const mount = this.els.stage;

        let PanelClass = this.panelCache.get(key);
        if (!PanelClass) {
            try {
                PanelClass = await loader();
                this.panelCache.set(key, PanelClass);
            } catch (err) {
                mount.innerHTML = errorState('The classroom failed to load. Check your connection and try again.');
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

        if (key === 'stage') {
            this.stagePanel = panel;
        }

        try {
            await panel.render();
        } catch (err) {
            mount.innerHTML = errorState(err.message || 'The classroom could not be displayed.');
        }
    }

    /* ═════════════════════════════════════════════════════ control bar */

    renderControlBar() {
        const { me } = this.state;
        const isHost = me.role === 'host' || me.role === 'moderator';

        const buttons = [];

        // Mic
        buttons.push({ id: 'ctrl-mic', icon: 'mic', iconOff: 'mic_off', tooltip: 'Toggle Microphone', shortcut: 'M' });

        // Camera
        buttons.push({ id: 'ctrl-cam', icon: 'videocam', iconOff: 'videocam_off', tooltip: 'Toggle Camera', shortcut: 'V' });

        // Screen Share
        buttons.push({ id: 'ctrl-screen', icon: 'screen_share', iconOff: 'stop_screen_share', tooltip: 'Share Screen' });

        // Separator
        buttons.push({ separator: true });

        // Raise Hand
        buttons.push({ id: 'ctrl-hand', icon: 'pan_tool', iconOff: 'back_hand', tooltip: 'Raise Hand', shortcut: 'H' });

        if (isHost) {
            // Recording
            buttons.push({ id: 'ctrl-record', icon: 'radio_button_checked', tooltip: 'Record', shortcut: 'R' });
        }

        // Separator before Leave
        buttons.push({ separator: true });

        // Leave / End
        if (isHost) {
            // Host has Chat quick access
            buttons.push({ id: 'ctrl-chat-quick', icon: 'chat', tooltip: 'Chat', shortcut: 'C' });
        }

        buttons.push({
            id: 'ctrl-leave',
            icon: 'call_end',
            label: isHost ? 'End Class' : 'Leave',
            danger: true,
            shortcut: 'L',
        });

        this.els.ctrlButtons.innerHTML = buttons.map((btn) => {
            if (btn.separator) return '<div class="cr-ctrl-sep"></div>';

            const classes = ['cr-ctrl-btn'];
            if (btn.danger) classes.push('danger');

            return `<button id="${btn.id}" class="${classes.join(' ')}"
                        data-tooltip="${btn.tooltip}${btn.shortcut ? ` (${btn.shortcut})` : ''}"
                        aria-label="${btn.tooltip}">
                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">${btn.icon}</span>
                ${btn.label ? `<span class="hidden sm:inline">${btn.label}</span>` : ''}
            </button>`;
        }).join('');

        this.bindControlEvents();
    }

    bindControlEvents() {
        const $ = (sel) => this.els.ctrlButtons.querySelector(sel);

        $('#ctrl-mic')?.addEventListener('click', () => this.emit('control:microphone'));
        $('#ctrl-cam')?.addEventListener('click', () => this.emit('control:camera'));
        $('#ctrl-screen')?.addEventListener('click', () => this.emit('control:screenShare'));
        $('#ctrl-hand')?.addEventListener('click', () => this.emit('control:handRaise'));
        $('#ctrl-record')?.addEventListener('click', () => this.emit('control:recording'));
        $('#ctrl-leave')?.addEventListener('click', () => this.leaveClassroom());
        $('#ctrl-chat-quick')?.addEventListener('click', () => this.toggleSidebarPanel('chat'));
        $('#ctrl-toggle-sidebar')?.addEventListener('click', () => this.toggleSidebar());
    }

    /** Simple event emitter for stage ↔ controls communication. */

    on(event, handler) {
        if (!this._listeners[event]) this._listeners[event] = new Set();
        this._listeners[event].add(handler);
        return () => this._listeners[event]?.delete(handler);
    }

    emit(event, data) {
        this._listeners[event]?.forEach((fn) => {
            try { fn(data); } catch (e) { console.error(`[RoomApp] Event handler error: ${event}`, e); }
        });
    }

    updateControlBtn(btnId, { active, muted, disabled, icon } = {}) {
        const btn = this.els.ctrlButtons.querySelector(`#${btnId}`);
        if (!btn) return;

        const span = btn.querySelector('.material-symbols-outlined');
        if (span && icon) span.textContent = icon;

        btn.classList.toggle('active', !!active);
        btn.classList.toggle('muted', !!muted);
        btn.disabled = !!disabled;
    }

    setControlsEnabled(enabled) {
        const skipIds = ['ctrl-leave', 'ctrl-toggle-sidebar'];
        this.els.ctrlButtons.querySelectorAll('.cr-ctrl-btn').forEach((btn) => {
            if (skipIds.includes(btn.id)) return;
            btn.disabled = !enabled;
            btn.style.opacity = enabled ? '' : '0.4';
            btn.style.pointerEvents = enabled ? '' : 'none';
        });
    }

    toggleSidebar() {
        if (this.sidebarCollapsed) {
            this.openSidebarPanel(this.activeKey || 'chat');
        } else {
            this.collapseSidebar();
        }
    }

    /* ═══════════════════════════════════════════════════════ mobile */

    isMobile() {
        return window.innerWidth < 768;
    }

    openMobileSheet(title) {
        this.els.mobileSheet.style.display = 'block';
        this.els.mobileSheetTitle.textContent = title;
        this.mobileSheetOpen = true;
        document.body.style.overflow = 'hidden';
    }

    closeMobileSheet() {
        this.els.mobileSheet.style.display = 'none';
        this.mobileSheetOpen = false;
        document.body.style.overflow = '';
        this.sidebarPanel?.destroy?.();
        this.sidebarPanel = null;
        this.activeKey = null;
    }

    /* ═════════════════════════════════════════════════════ host lifecycle */

    async startClass() {
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
        this.showModal({
            title: 'End this class?',
            body: 'All participants will be disconnected. This action cannot be undone.',
            confirmLabel: 'End Class',
            confirmClass: 'danger',
            onConfirm: async () => {
                try {
                    const res = await this.api.end();
                    if (res?.data?.room) this.applyRoomState(res.data.room);
                    toast(res?.message || 'The class has ended.', 'info');
                } catch (err) {
                    toast(err.message || 'Unable to end the class.', 'error');
                }
            },
        });
    }

    async leaveClassroom() {
        this.showModal({
            title: 'Leave classroom?',
            body: 'You can rejoin the class as long as it is still in session.',
            confirmLabel: 'Leave',
            confirmClass: 'danger',
            onConfirm: () => this.exit(),
        });
    }

    async exit() {
        this.stopHeartbeat();
        this.stopSessionTimer();
        this.channel?.destroy();

        // Disconnect LiveKit room
        try {
            this.stagePanel?.leaveRoom?.();
            this.stagePanel?.destroy?.();
            this.sidebarPanel?.destroy?.();
        } catch { /* best-effort */ }

        try {
            await this.api.leave();
        } catch { /* best-effort */ }

        window.location.href = this.config.exitUrl;
    }

    /* ═════════════════════════════════════════════════════ settings */

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

        const html = `
            <div class="cr-settings">
                <div class="cr-settings-group">
                    <div class="cr-settings-label">Room Settings</div>
                    ${toggles.map(([key, label]) => `
                        <div class="cr-settings-toggle">
                            <span class="cr-settings-toggle-label">${escapeHtml(label)}</span>
                            <div class="cr-switch ${settings[key] ? 'on' : ''}" data-setting="${key}" role="switch"
                                 aria-checked="${settings[key] ? 'true' : 'false'}" tabindex="0"></div>
                        </div>
                    `).join('')}
                </div>

                <div class="cr-settings-group">
                    <div class="cr-settings-label">Lobby</div>
                    <div class="cr-settings-toggle">
                        <span class="cr-settings-toggle-label">Opens (minutes before)</span>
                        <input type="number" min="0" max="180" data-setting="lobby_opens_minutes_before"
                               value="${settings.lobby_opens_minutes_before ?? 15}"
                               class="w-20 bg-white/[0.04] border border-white/[0.06] text-white text-xs rounded-lg px-2.5 py-1.5 outline-none focus:border-indigo-500/50">
                    </div>
                </div>

                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid var(--cr-border-subtle)">
                    <button type="button" id="settings-cancel" class="cr-modal-btn secondary">Cancel</button>
                    <button type="button" id="settings-save" class="cr-modal-btn primary">Save settings</button>
                </div>
            </div>
        `;

        // Use sidebar panel for settings on desktop, or mobile sheet
        if (this.isMobile()) {
            this.openMobileSheet('Settings');
            this.els.mobileSheetBody.innerHTML = html;
        } else {
            this.expandSidebar();
            this.els.sidebarPanel.innerHTML = html;
        }

        // Bind toggle switches
        this.els.sidebarPanel.querySelectorAll('.cr-switch[data-setting]').forEach((sw) => {
            sw.addEventListener('click', () => {
                const isOn = sw.classList.toggle('on');
                sw.setAttribute('aria-checked', isOn);
            });
            sw.addEventListener('keydown', (e) => {
                if (e.key === ' ' || e.key === 'Enter') {
                    e.preventDefault();
                    sw.click();
                }
            });
        });

        document.getElementById('settings-cancel')?.addEventListener('click', () => {
            if (this.isMobile()) {
                this.closeMobileSheet();
            } else {
                this.activeKey = null;
                this.openSidebarPanel('chat');
            }
        });

        document.getElementById('settings-save')?.addEventListener('click', async () => {
            const payload = {};
            this.els.sidebarPanel.querySelectorAll('[data-setting]').forEach((el) => {
                if (el.classList.contains('cr-switch')) {
                    payload[el.dataset.setting] = el.classList.contains('on');
                } else {
                    payload[el.dataset.setting] = el.type === 'checkbox' ? el.checked : Number(el.value);
                }
            });

            try {
                const res = await this.api.updateSettings(payload);
                if (res?.data?.room) this.state.room = res.data.room;
                if (res?.data?.me) this.state.me = { ...this.state.me, ...res.data.me };
                toast(res?.message || 'Settings saved.', 'success');
                this.renderSidebarTabs();
                this.activeKey = null;
                if (this.isMobile()) this.closeMobileSheet();
            } catch (err) {
                toast(err.message || 'Unable to save settings.', 'error');
            }
        });
    }

    /* ═══════════════════════════════════════════════════ modals */

    showModal({ title, body, confirmLabel = 'Confirm', confirmClass = 'primary', onConfirm }) {
        const id = 'modal-' + Date.now();
        this.els.modals.innerHTML = `
            <div class="cr-modal-backdrop" data-modal="${id}">
                <div class="cr-modal" role="alertdialog" aria-modal="true">
                    <h3 class="cr-modal-title">${escapeHtml(title)}</h3>
                    <p class="cr-modal-body">${escapeHtml(body)}</p>
                    <div class="cr-modal-actions">
                        <button class="cr-modal-btn secondary" data-modal-cancel="${id}">Cancel</button>
                        <button class="cr-modal-btn ${confirmClass}" data-modal-confirm="${id}">${escapeHtml(confirmLabel)}</button>
                    </div>
                </div>
            </div>
        `;

        const backdrop = this.els.modals.querySelector(`[data-modal="${id}"]`);
        const cleanup = () => backdrop?.remove();

        backdrop.querySelector(`[data-modal-cancel="${id}"]`)?.addEventListener('click', cleanup);
        backdrop.querySelector(`[data-modal-confirm="${id}"]`)?.addEventListener('click', async () => {
            cleanup();
            await onConfirm?.();
        });
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) cleanup();
        });
    }

    /* ═══════════════════════════════════════════════════ presence */

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

    /* ═══════════════════════════════════════════════ lifecycle */

    bindLifecycle() {
        // Warn before leaving during live class
        window.addEventListener('beforeunload', (e) => {
            if (this.state.room?.state === 'live') {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Send beacon on page hide
        window.addEventListener('pagehide', () => {
            if (navigator.sendBeacon) {
                const url = `${this.config.baseUrl || ''}/api/online-learning/rooms/${this.config.roomKey}/leave`;
                const form = new FormData();
                form.append('_token', this.config.csrfToken);
                navigator.sendBeacon(url, form);
            }
        });
    }

    bindGlobalEvents() {
        // Mobile sheet close
        this.els.mobileSheet.querySelectorAll('[data-mobile-close]').forEach((el) => {
            el.addEventListener('click', () => this.closeMobileSheet());
        });

        // Header buttons
        document.getElementById('header-settings-btn')?.addEventListener('click', () => this.openSettings());
        document.getElementById('header-end-btn')?.addEventListener('click', () => this.endClass());
        document.getElementById('header-more-btn')?.addEventListener('click', () => this.showMoreMenu());

        // Back button
        this.els.header.querySelector('[data-room-exit]')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.leaveClassroom();
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => this.handleKeyboard(e));

        // Responsive: collapse sidebar on resize to mobile
        window.addEventListener('resize', () => {
            if (this.isMobile() && !this.sidebarCollapsed) {
                // On mobile, sidebar is hidden, panels open in bottom sheet
            }
        });
    }

    handleKeyboard(e) {
        // Ignore if typing in an input
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) return;

        // Escape closes mobile sheet or sidebar
        if (e.key === 'Escape') {
            if (this.mobileSheetOpen) {
                this.closeMobileSheet();
                return;
            }
            if (!this.sidebarCollapsed && this.activeKey) {
                this.collapseSidebar();
                return;
            }
        }

        // Don't process shortcuts if modifier keys are held (except shift for ?)
        if (e.ctrlKey || e.metaKey || e.altKey) return;

        switch (e.key.toLowerCase()) {
            case 'm': this.emit('control:microphone'); break;
            case 'v': this.emit('control:camera'); break;
            case 'h': this.emit('control:handRaise'); break;
            case 'c':
                e.preventDefault();
                this.toggleSidebarPanel('chat');
                break;
            case 'l':
                this.leaveClassroom();
                break;
            case 'f':
                this.emit('control:fullscreen');
                break;
            case '?':
                this.showKeyboardShortcuts();
                break;
        }
    }

    showMoreMenu() {
        // For now, open settings. Can be expanded to a dropdown.
        this.openSettings();
    }

    showKeyboardShortcuts() {
        this.showModal({
            title: 'Keyboard Shortcuts',
            body: [
                ['M', 'Toggle microphone'],
                ['V', 'Toggle camera'],
                ['H', 'Raise/lower hand'],
                ['C', 'Open chat'],
                ['F', 'Fullscreen'],
                ['L', 'Leave class'],
                ['Esc', 'Close panel'],
            ].map(([key, desc]) => `<span class="cr-kbd">${key}</span> ${desc}`).join('<br>'),
            confirmLabel: 'Got it',
            confirmClass: 'primary',
        });
    }

    handleSessionExpired() {
        this.stopHeartbeat();
        this.stopSessionTimer();
        toast('Your session has expired. Redirecting to sign in…', 'warning');
        setTimeout(() => { window.location.href = '/login'; }, 1500);
    }

    renderFatal(err) {
        const forbidden = err.status === 403;
        const missing = err.status === 404;

        this.els.header.innerHTML = '';
        this.els.stage.innerHTML = '';
        this.els.sidebar.innerHTML = '';
        this.els.controls.innerHTML = '';

        this.els.stage.innerHTML = `
            <div class="cr-waiting">
                <div class="cr-waiting-icon" style="animation:none">
                    <span class="material-symbols-outlined text-[36px] text-slate-400">
                        ${forbidden ? 'lock' : missing ? 'meeting_room' : 'error'}
                    </span>
                </div>
                <h2 class="text-lg font-bold text-white">
                    ${forbidden ? 'You are not in this class' : missing ? 'This room no longer exists' : 'The room could not be opened'}
                </h2>
                <p class="text-sm text-slate-400">${escapeHtml(err.message || '')}</p>
                <a href="${escapeHtml(this.config.exitUrl)}"
                   class="cr-join-btn" style="margin-top:16px;text-decoration:none">
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_back</span>
                    Back to my classes
                </a>
            </div>
        `;
    }
}
