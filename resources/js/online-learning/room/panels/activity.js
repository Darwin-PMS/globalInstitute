import { escapeHtml, formatTime } from '../lib/ui.js';

/**
 * Activity Panel — Timeline of room events in the sidebar.
 *
 * Consumes the channel's delta topic for real-time updates.
 */
export default class ActivityPanel {
    constructor(ctx) {
        this.api = ctx.api;
        this.channel = ctx.channel;
        this.mount = ctx.mount;
        this.seen = new Set();
        this.unsubscribe = null;
    }

    async render() {
        let res;
        try {
            res = await this.api.activity();
        } catch (err) {
            this.mount.innerHTML = this.renderError(err.message || 'Unable to load the activity log.');
            this.mount.querySelector('#act-retry')?.addEventListener('click', () => this.render());
            return;
        }
        if (!res) return;

        const events = res.data?.events || [];
        this.channel.setCursor('timeline', res.meta?.last_id);

        this.mount.innerHTML = `
            <div class="cr-panel" style="height:100%;display:flex;flex-direction:column">
                <div class="cr-panel-header">
                    <h2 class="cr-panel-title">
                        <span class="material-symbols-outlined" aria-hidden="true">history</span>
                        Activity
                    </h2>
                </div>
                <div id="act-list" class="flex-1 overflow-y-auto" style="margin:0 calc(var(--cr-space-3) * -1);padding:0 var(--cr-space-3)"></div>
            </div>
        `;

        this.list = this.mount.querySelector('#act-list');

        if (events.length) {
            this.append(events);
        } else {
            this.list.innerHTML = `
                <div class="cr-empty">
                    <div class="cr-empty-icon">
                        <span class="material-symbols-outlined">history_toggle_off</span>
                    </div>
                    <p class="cr-empty-title">Nothing has happened yet</p>
                    <p class="cr-empty-desc">Joins, announcements and attendance show up here.</p>
                </div>
            `;
        }

        this.unsubscribe = this.channel.on('timeline', ({ events: incoming }) => this.append(incoming));
        this.channel.start('timeline');
    }

    append(events) {
        if (!this.list || !events?.length) return;

        const fresh = events.filter((e) => !this.seen.has(e.id));
        if (!fresh.length) return;

        if (this.seen.size === 0) this.list.innerHTML = '';

        // Newest at the top
        fresh.reverse().forEach((e) => {
            this.seen.add(e.id);
            this.list.insertAdjacentHTML('afterbegin', this.entry(e));
        });
    }

    entry(e) {
        return `
            <div class="flex gap-3 pb-4 relative" style="padding-left:var(--cr-space-3)">
                <div class="flex flex-col items-center flex-shrink-0">
                    <div class="w-7 h-7 rounded-full bg-white/[0.04] border border-white/[0.06] flex items-center justify-center">
                        <span class="material-symbols-outlined text-[14px] text-indigo-400" aria-hidden="true">${escapeHtml(e.icon)}</span>
                    </div>
                    <div class="w-px flex-1 bg-white/[0.04] mt-1" aria-hidden="true"></div>
                </div>

                <div class="min-w-0 pt-1">
                    <p class="text-xs font-semibold text-slate-100">${escapeHtml(e.description || e.label)}</p>
                    <p class="text-[10px] text-slate-500 mt-0.5">
                        ${e.actor ? escapeHtml(e.actor) + ' · ' : ''}${formatTime(e.at)}
                    </p>
                </div>
            </div>
        `;
    }

    renderError(message) {
        return `
            <div class="cr-panel" style="height:100%;display:flex;flex-direction:column">
                <div class="cr-panel-header">
                    <h2 class="cr-panel-title">
                        <span class="material-symbols-outlined" aria-hidden="true">history</span>
                        Activity
                    </h2>
                </div>
                <div class="cr-error">
                    <div class="cr-error-icon">
                        <span class="material-symbols-outlined text-[22px] text-red-400">error</span>
                    </div>
                    <p class="text-xs text-red-200 mt-2 mb-4">${escapeHtml(message)}</p>
                    <button type="button" id="act-retry" class="cr-modal-btn secondary">Try again</button>
                </div>
            </div>
        `;
    }

    destroy() {
        this.unsubscribe?.();
        this.channel.stop('timeline');
    }
}
