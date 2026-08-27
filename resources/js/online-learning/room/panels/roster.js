import { skeletonRoster, emptyState, errorState, escapeHtml, formatTime, formatDuration, panelHeading } from '../lib/ui.js';

/**
 * Participants panel (host only).
 *
 * Shows who is in the room now, who was expected, and how long each person has
 * actually been present — the raw signal the attendance panel proposes from.
 */
export default class RosterPanel {
    constructor(ctx) {
        this.api = ctx.api;
        this.channel = ctx.channel;
        this.mount = ctx.mount;
        this.unsubscribe = null;
    }

    async render() {
        this.mount.innerHTML = skeletonRoster(5);

        let res;
        try {
            res = await this.api.roster();
        } catch (err) {
            this.mount.innerHTML = errorState(err.message || 'Unable to load participants.', 'roster-retry');
            this.mount.querySelector('#roster-retry')?.addEventListener('click', () => this.render());
            return;
        }
        if (!res) return;

        this.paint(res.data || {});

        // Live presence updates arrive through the channel, so the list stays
        // current without this panel owning a timer.
        this.unsubscribe = this.channel.on('presence', (payload) => this.applyPresence(payload));
        this.channel.start('presence');
    }

    paint(data) {
        const participants = data.participants || [];
        const expected = data.expected || [];
        const onlineIds = new Set((data.online || []).map((p) => p.user_id));

        // Expected but never turned up.
        const seenIds = new Set(participants.map((p) => p.user_id));
        const absent = expected.filter((e) => !seenIds.has(e.user_id));

        this.mount.innerHTML = `
            <div class="space-y-6">
                <div>
                    ${panelHeading('groups', 'In the room',
                        `<span id="roster-online-count" class="text-[11px] font-bold text-emerald-400">${onlineIds.size} online</span>`)}
                    <div id="roster-present" class="space-y-2">
                        ${participants.length
                            ? participants.map((p) => this.row(p, onlineIds.has(p.user_id))).join('')
                            : emptyState({ icon: 'person_off', title: 'Nobody has joined yet', description: 'Participants appear here as they enter the room.' })}
                    </div>
                </div>

                ${absent.length ? `
                    <div>
                        ${panelHeading('person_off', 'Not yet joined',
                            `<span class="text-[11px] text-slate-500">${absent.length}</span>`)}
                        <div class="space-y-2">
                            ${absent.map((e) => `
                                <div class="flex items-center gap-3 p-3 bg-slate-900/50 border border-slate-800 rounded-xl opacity-70">
                                    <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-500 text-xs font-bold flex-shrink-0">
                                        ${escapeHtml((e.name || '?').charAt(0).toUpperCase())}
                                    </div>
                                    <span class="text-xs text-slate-400 flex-1 truncate">${escapeHtml(e.name)}</span>
                                    <span class="text-[10px] text-slate-600">Expected</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    row(p, isOnline) {
        return `
            <div class="flex items-center gap-3 p-3 bg-slate-900 border border-slate-800 rounded-xl" data-participant="${p.user_id}">
                <div class="relative flex-shrink-0">
                    <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-indigo-400 text-xs font-bold">
                        ${escapeHtml((p.name || '?').charAt(0).toUpperCase())}
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-slate-900 ${isOnline ? 'bg-emerald-500' : 'bg-slate-600'}"
                          aria-label="${isOnline ? 'Online' : 'Offline'}"></span>
                </div>

                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-slate-100 truncate">${escapeHtml(p.name)}</p>
                    <p class="text-[10px] text-slate-500">
                        ${escapeHtml(p.role_label)} · joined ${formatTime(p.first_joined_at)}
                        ${p.join_count > 1 ? ` · ${p.join_count} sessions` : ''}
                    </p>
                </div>

                <span class="flex-shrink-0 text-[11px] font-bold text-slate-300 tabular-nums">
                    ${formatDuration(p.attended_seconds)}
                </span>
            </div>
        `;
    }

    /** Flip the online dots without refetching the whole roster. */
    applyPresence(payload) {
        const countEl = this.mount.querySelector('#roster-online-count');
        if (countEl) countEl.textContent = `${payload.online_count ?? 0} online`;

        if (!payload.online) return;

        const onlineIds = new Set(payload.online.map((p) => p.user_id));
        this.mount.querySelectorAll('[data-participant]').forEach((row) => {
            const id = Number(row.dataset.participant);
            const dot = row.querySelector('span[aria-label]');
            if (!dot) return;

            const isOnline = onlineIds.has(id);
            dot.className = `absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-slate-900 ${isOnline ? 'bg-emerald-500' : 'bg-slate-600'}`;
            dot.setAttribute('aria-label', isOnline ? 'Online' : 'Offline');
        });
    }

    destroy() {
        this.unsubscribe?.();
        this.channel.stop('presence');
    }
}
