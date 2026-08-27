import { skeletonList, emptyState, errorState, escapeHtml, formatTime, panelHeading } from '../lib/ui.js';

/**
 * Activity timeline.
 *
 * Everything the room recorded, appended as it happens. Like chat, it consumes
 * the channel's delta topic rather than polling for itself.
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
        this.mount.innerHTML = skeletonList(5);

        let res;
        try {
            res = await this.api.activity();
        } catch (err) {
            this.mount.innerHTML = errorState(err.message || 'Unable to load the activity log.', 'act-retry');
            this.mount.querySelector('#act-retry')?.addEventListener('click', () => this.render());
            return;
        }
        if (!res) return;

        const events = res.data?.events || [];
        this.channel.setCursor('timeline', res.meta?.last_id);

        this.mount.innerHTML = `
            ${panelHeading('history', 'Class activity')}
            <div id="act-list" class="relative space-y-0"></div>
        `;
        this.list = this.mount.querySelector('#act-list');

        if (events.length) {
            this.append(events);
        } else {
            this.list.innerHTML = emptyState({
                icon: 'history_toggle_off',
                title: 'Nothing has happened yet',
                description: 'Joins, announcements and attendance all show up here as the class runs.',
            });
        }

        this.unsubscribe = this.channel.on('timeline', ({ events: incoming }) => this.append(incoming));
        this.channel.start('timeline');
    }

    append(events) {
        if (!this.list || !events?.length) return;

        const fresh = events.filter((e) => !this.seen.has(e.id));
        if (!fresh.length) return;

        if (this.seen.size === 0) this.list.innerHTML = '';

        // Newest at the top: a timeline is read from the most recent backwards.
        fresh.reverse().forEach((e) => {
            this.seen.add(e.id);
            this.list.insertAdjacentHTML('afterbegin', this.entry(e));
        });
    }

    entry(e) {
        return `
            <div class="flex gap-3 pb-4 relative">
                <div class="flex flex-col items-center flex-shrink-0">
                    <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-indigo-400">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">${escapeHtml(e.icon)}</span>
                    </div>
                    <div class="w-px flex-1 bg-slate-800 mt-1" aria-hidden="true"></div>
                </div>

                <div class="min-w-0 pt-1.5">
                    <p class="text-xs font-semibold text-slate-100">${escapeHtml(e.description || e.label)}</p>
                    <p class="text-[10px] text-slate-500 mt-0.5">
                        ${e.actor ? escapeHtml(e.actor) + ' · ' : ''}${formatTime(e.at)}
                    </p>
                </div>
            </div>
        `;
    }

    destroy() {
        this.unsubscribe?.();
        this.channel.stop('timeline');
    }
}
