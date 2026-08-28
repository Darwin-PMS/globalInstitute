import { escapeHtml, formatTime, formatDuration } from '../lib/ui.js';

/**
 * Participants Panel — Sidebar participant list with search and filters.
 *
 * Shows instructor separately, students with online status, mic/camera state,
 * and host-only moderation actions.
 */
export default class RosterPanel {
    constructor(ctx) {
        this.api = ctx.api;
        this.channel = ctx.channel;
        this.caps = ctx.capabilities;
        this.mount = ctx.mount;
        this.shell = ctx.shell;
        this.unsubscribe = null;
        this.data = { participants: [], expected: [], online: [] };
        this.filter = 'all';
        this.search = '';
    }

    async render() {
        let res;
        try {
            res = await this.api.roster();
        } catch (err) {
            this.mount.innerHTML = this.renderError(err.message || 'Unable to load participants.');
            this.mount.querySelector('#roster-retry')?.addEventListener('click', () => this.render());
            return;
        }
        if (!res) return;

        this.data = res.data || {};
        this.paint();

        this.unsubscribe = this.channel.on('presence', (payload) => this.applyPresence(payload));
        this.channel.start('presence');
    }

    paint() {
        const { participants = [], expected = [], online = [] } = this.data;
        const onlineIds = new Set(online.map((p) => p.user_id));
        const seenIds = new Set(participants.map((p) => p.user_id));
        const absent = expected.filter((e) => !seenIds.has(e.user_id));

        // Separate instructor from students
        const instructor = participants.find((p) => p.role_label?.toLowerCase().includes('instructor') || p.role === 'host');
        const students = participants.filter((p) => p !== instructor);

        // Apply filters
        let filtered = students;
        if (this.filter === 'speaking') {
            // Would need live speaking data; for now show all
        } else if (this.filter === 'hand_raised') {
            filtered = students.filter((p) => p.is_hand_raised);
        } else if (this.filter === 'camera_on') {
            filtered = students.filter((p) => p.has_camera);
        } else if (this.filter === 'mic_on') {
            filtered = students.filter((p) => p.has_mic);
        }

        // Apply search
        if (this.search) {
            const q = this.search.toLowerCase();
            filtered = filtered.filter((p) => p.name?.toLowerCase().includes(q));
        }

        this.mount.innerHTML = `
            <div class="cr-panel" style="height:100%;display:flex;flex-direction:column">
                <div class="cr-panel-header">
                    <h2 class="cr-panel-title">
                        <span class="material-symbols-outlined" aria-hidden="true">groups</span>
                        Participants
                    </h2>
                    <span class="cr-panel-count">${participants.length}</span>
                </div>

                <div class="cr-search">
                    <span class="material-symbols-outlined" aria-hidden="true">search</span>
                    <input type="text" id="roster-search" placeholder="Search participants…" value="${escapeHtml(this.search)}" aria-label="Search participants">
                </div>

                <div class="cr-filters" role="group" aria-label="Filter participants">
                    <button class="cr-filter-chip ${this.filter === 'all' ? 'active' : ''}" data-filter="all">
                        All <span class="count">${participants.length}</span>
                    </button>
                    <button class="cr-filter-chip ${this.filter === 'hand_raised' ? 'active' : ''}" data-filter="hand_raised">
                        <span class="material-symbols-outlined" style="font-size:12px">pan_tool</span>
                        Raised <span class="count">${students.filter((p) => p.is_hand_raised).length}</span>
                    </button>
                    <button class="cr-filter-chip ${this.filter === 'mic_on' ? 'active' : ''}" data-filter="mic_on">
                        Mic On
                    </button>
                    <button class="cr-filter-chip ${this.filter === 'camera_on' ? 'active' : ''}" data-filter="camera_on">
                        Camera On
                    </button>
                </div>

                <div id="roster-list" class="flex-1 overflow-y-auto" style="margin:0 calc(var(--cr-space-3) * -1);padding:0 var(--cr-space-3)">
                    ${instructor ? this.renderInstructor(instructor, onlineIds) : ''}
                    ${filtered.length ? `
                        <div class="cr-section-label">Students (${filtered.length})</div>
                        ${filtered.map((p) => this.renderStudent(p, onlineIds)).join('')}
                    ` : ''}
                    ${absent.length ? `
                        <div class="cr-section-label" style="margin-top:var(--cr-space-3)">Not Yet Joined (${absent.length})</div>
                        ${absent.map((e) => this.renderAbsent(e)).join('')}
                    ` : ''}
                    ${!instructor && !filtered.length && !absent.length ? this.renderEmpty() : ''}
                </div>
            </div>
        `;

        this.bindEvents();
    }

    renderInstructor(p, onlineIds) {
        const isOnline = onlineIds.has(p.user_id);
        return `
            <div class="cr-section-label">Instructor</div>
            <div class="cr-participant" data-participant="${p.user_id}">
                <div class="cr-participant-avatar">
                    <div class="cr-avatar-sm">${this.getInitials(p.name)}</div>
                    <span class="cr-online-dot ${isOnline ? 'online' : 'offline'}" aria-label="${isOnline ? 'Online' : 'Offline'}"></span>
                </div>
                <div class="cr-participant-info">
                    <div class="cr-participant-name">
                        ${escapeHtml(p.name)}
                        <span class="cr-host-badge">Host</span>
                    </div>
                    <div class="cr-participant-role">${escapeHtml(p.role_label || 'Instructor')}</div>
                </div>
                <div class="cr-participant-meta">
                    <div class="cr-participant-icons">
                        <span class="material-symbols-outlined ${p.has_mic ? 'on' : 'off'}" aria-label="Microphone">${p.has_mic ? 'mic' : 'mic_off'}</span>
                        <span class="material-symbols-outlined ${p.has_camera ? 'on' : 'off'}" aria-label="Camera">${p.has_camera ? 'videocam' : 'videocam_off'}</span>
                    </div>
                </div>
            </div>
        `;
    }

    renderStudent(p, onlineIds) {
        const isOnline = onlineIds.has(p.user_id);
        const isHost = this.caps.can_control_room;

        return `
            <div class="cr-participant" data-participant="${p.user_id}">
                <div class="cr-participant-avatar">
                    <div class="cr-avatar-sm">${this.getInitials(p.name)}</div>
                    <span class="cr-online-dot ${isOnline ? 'online' : 'offline'}" aria-label="${isOnline ? 'Online' : 'Offline'}"></span>
                </div>
                <div class="cr-participant-info">
                    <div class="cr-participant-name">
                        ${escapeHtml(p.name)}
                        ${p.is_hand_raised ? '<span class="cr-hand-icon material-symbols-outlined" aria-label="Hand raised">pan_tool</span>' : ''}
                    </div>
                    <div class="cr-participant-role">
                        ${escapeHtml(p.role_label || 'Student')}
                        ${p.join_count > 1 ? ` · ${p.join_count} sessions` : ''}
                    </div>
                </div>
                <div class="cr-participant-meta">
                    <div class="cr-participant-icons">
                        <span class="material-symbols-outlined ${p.has_mic ? 'on' : 'off'}" aria-label="Microphone">${p.has_mic ? 'mic' : 'mic_off'}</span>
                        <span class="material-symbols-outlined ${p.has_camera ? 'on' : 'off'}" aria-label="Camera">${p.has_camera ? 'videocam' : 'videocam_off'}</span>
                    </div>
                    ${isHost ? `
                        <div class="cr-participant-actions">
                            <button class="cr-participant-action" data-action="mute" data-user="${p.user_id}" title="Mute" aria-label="Mute ${escapeHtml(p.name)}">
                                <span class="material-symbols-outlined text-[14px]">mic_off</span>
                            </button>
                            <button class="cr-participant-action danger" data-action="remove" data-user="${p.user_id}" title="Remove" aria-label="Remove ${escapeHtml(p.name)}">
                                <span class="material-symbols-outlined text-[14px]">person_remove</span>
                            </button>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    renderAbsent(e) {
        return `
            <div class="cr-participant" style="opacity:0.5">
                <div class="cr-participant-avatar">
                    <div class="cr-avatar-sm">${this.getInitials(e.name)}</div>
                </div>
                <div class="cr-participant-info">
                    <div class="cr-participant-name">${escapeHtml(e.name)}</div>
                    <div class="cr-participant-role">Expected</div>
                </div>
            </div>
        `;
    }

    renderEmpty() {
        return `
            <div class="cr-empty">
                <div class="cr-empty-icon">
                    <span class="material-symbols-outlined">person_off</span>
                </div>
                <p class="cr-empty-title">No participants yet</p>
                <p class="cr-empty-desc">Participants will appear here as they join.</p>
            </div>
        `;
    }

    renderError(message) {
        return `
            <div class="cr-error">
                <div class="cr-error-icon">
                    <span class="material-symbols-outlined text-[22px] text-red-400">error</span>
                </div>
                <p class="text-xs text-red-200 mt-2 mb-4">${escapeHtml(message)}</p>
                <button type="button" id="roster-retry" class="cr-modal-btn secondary">Try again</button>
            </div>
        `;
    }

    getInitials(name) {
        if (!name) return '?';
        return name.split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase();
    }

    bindEvents() {
        // Search
        this.mount.querySelector('#roster-search')?.addEventListener('input', (e) => {
            this.search = e.target.value;
            this.paint();
        });

        // Filters
        this.mount.querySelectorAll('[data-filter]').forEach((btn) => {
            btn.addEventListener('click', () => {
                this.filter = btn.dataset.filter;
                this.paint();
            });
        });

        // Host actions
        this.mount.querySelectorAll('[data-action="mute"]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const userId = btn.dataset.user;
                try {
                    const onlineClassId = this.shell.state.klass?.id;
                    await fetch(`${this.api.liveClassesBase}/${onlineClassId}/participants/${userId}/mute`, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.api.csrfToken,
                        },
                        credentials: 'same-origin',
                    });
                    toast('Participant muted.', 'success');
                } catch (err) {
                    toast('Unable to mute participant.', 'error');
                }
            });
        });

        this.mount.querySelectorAll('[data-action="remove"]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const userId = btn.dataset.user;
                if (!confirm('Remove this participant from the class?')) return;
                try {
                    const onlineClassId = this.shell.state.klass?.id;
                    await fetch(`${this.api.liveClassesBase}/${onlineClassId}/participants/${userId}/remove`, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.api.csrfToken,
                        },
                        credentials: 'same-origin',
                    });
                    toast('Participant removed.', 'success');
                } catch (err) {
                    toast('Unable to remove participant.', 'error');
                }
            });
        });
    }

    applyPresence(payload) {
        const countEl = this.mount.querySelector('.cr-panel-count');
        if (countEl) countEl.textContent = payload.online_count ?? this.data.participants?.length ?? 0;

        if (!payload.online) return;

        const onlineIds = new Set(payload.online.map((p) => p.user_id));
        this.mount.querySelectorAll('[data-participant]').forEach((row) => {
            const id = Number(row.dataset.participant);
            const dot = row.querySelector('.cr-online-dot');
            if (!dot) return;

            const isOnline = onlineIds.has(id);
            dot.className = `cr-online-dot ${isOnline ? 'online' : 'offline'}`;
            dot.setAttribute('aria-label', isOnline ? 'Online' : 'Offline');
        });
    }

    destroy() {
        this.unsubscribe?.();
        this.channel.stop('presence');
    }
}
