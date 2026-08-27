import { skeletonChat, emptyState, errorState, escapeHtml, formatTime, toast } from '../lib/ui.js';

/**
 * Chat + announcements panel.
 *
 * Subscribes to the channel's `messages` topic rather than polling itself, so
 * whichever transport is in use (interval today, socket later) this panel is
 * unchanged. Appends deltas instead of re-rendering the transcript.
 */
export default class ChatPanel {
    constructor(ctx) {
        this.api = ctx.api;
        this.channel = ctx.channel;
        this.caps = ctx.capabilities;
        this.userId = ctx.userId;
        this.mount = ctx.mount;
        this.seen = new Set();
        this.unsubscribe = null;
    }

    async render() {
        this.mount.innerHTML = skeletonChat();

        let initial;
        try {
            initial = await this.api.messages();
        } catch (err) {
            this.mount.innerHTML = errorState(err.message || 'Unable to load the chat.', 'chat-retry');
            this.mount.querySelector('#chat-retry')?.addEventListener('click', () => this.render());
            return;
        }
        if (!initial) return; // superseded

        const messages = initial.data?.messages || [];
        const pinned = initial.data?.pinned || [];

        // Seed the cursor so the next poll only asks for what follows.
        this.channel.setCursor('messages', initial.meta?.last_id);

        this.paint(pinned);

        if (messages.length) {
            this.append(messages);
        } else {
            this.log.innerHTML = `
                <p class="text-center text-[11px] text-slate-500 py-8">
                    No messages yet. Say hello to the class.
                </p>
            `;
        }

        this.unsubscribe = this.channel.on('messages', ({ messages: incoming }) => this.append(incoming));
        this.channel.start('messages');
    }

    paint(pinned) {
        const canPost = this.caps.can_post_message || this.caps.can_post_announcement;

        this.mount.innerHTML = `
            <div class="flex flex-col h-[32rem] bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
                ${pinned.length ? `
                    <div id="chat-pinned" class="flex-shrink-0 border-b border-slate-800 bg-amber-950/20 divide-y divide-slate-800/60">
                        ${pinned.map((m) => `
                            <div class="px-4 py-2.5 flex items-start gap-2">
                                <span class="material-symbols-outlined text-sm text-amber-400 mt-0.5" aria-hidden="true">push_pin</span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold text-amber-300">${escapeHtml(m.author)}</p>
                                    <p class="text-xs text-slate-200 leading-relaxed">${escapeHtml(m.body)}</p>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                ` : ''}

                <div id="chat-log" class="flex-1 overflow-y-auto p-4 space-y-3" role="log" aria-label="Class chat"></div>

                ${canPost ? `
                    <form id="chat-form" class="flex-shrink-0 border-t border-slate-800 p-3 flex items-end gap-2">
                        <label for="chat-input" class="sr-only">Message</label>
                        <textarea id="chat-input" rows="1" maxlength="2000"
                            placeholder="${this.caps.can_post_message ? 'Message the class…' : 'Post an announcement…'}"
                            class="flex-1 resize-none bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500 max-h-32"></textarea>

                        ${this.caps.can_post_announcement ? `
                            <label class="flex items-center gap-1.5 text-[11px] text-slate-400 whitespace-nowrap cursor-pointer pb-2.5">
                                <input type="checkbox" id="chat-announce" class="w-3.5 h-3.5 rounded border-slate-700 bg-slate-950 text-amber-500">
                                Announce
                            </label>
                        ` : ''}

                        <button type="submit" id="chat-send"
                            class="flex-shrink-0 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-colors disabled:opacity-50">
                            Send
                        </button>
                    </form>
                ` : `
                    <div class="flex-shrink-0 border-t border-slate-800 px-4 py-3 text-[11px] text-slate-500 text-center">
                        Chat is closed for this class.
                    </div>
                `}
            </div>
        `;

        this.log = this.mount.querySelector('#chat-log');
        if (canPost) this.bindComposer();
    }

    bindComposer() {
        const form = this.mount.querySelector('#chat-form');
        const input = this.mount.querySelector('#chat-input');
        const send = this.mount.querySelector('#chat-send');
        const announce = this.mount.querySelector('#chat-announce');

        // Enter sends, Shift+Enter makes a new line.
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                form.requestSubmit();
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const body = input.value.trim();
            if (!body) return;

            const type = announce?.checked ? 'announcement' : 'message';

            send.disabled = true;
            input.disabled = true;

            try {
                const res = await this.api.postMessage(body, type);
                input.value = '';
                if (announce) announce.checked = false;

                // Show it immediately; the delta poll will skip it via `seen`.
                if (res?.data?.message) this.append([res.data.message]);
                if (res?.message) toast(res.message, 'success');
            } catch (err) {
                toast(err.message || 'Message not sent.', 'error');
            } finally {
                send.disabled = false;
                input.disabled = false;
                input.focus();
            }
        });
    }

    /** Append only messages we have not already drawn. */
    append(messages) {
        if (!this.log || !messages?.length) return;

        const fresh = messages.filter((m) => !this.seen.has(m.id));
        if (!fresh.length) return;

        // Clear the "no messages" placeholder on first real content.
        if (this.seen.size === 0) this.log.innerHTML = '';

        const nearBottom = this.log.scrollHeight - this.log.scrollTop - this.log.clientHeight < 80;

        fresh.forEach((m) => {
            this.seen.add(m.id);
            this.log.insertAdjacentHTML('beforeend', this.bubble(m));
        });

        // Only auto-scroll if the reader was already at the bottom.
        if (nearBottom) this.log.scrollTop = this.log.scrollHeight;

        if (this.caps.can_moderate_chat) this.bindModeration();
    }

    bubble(m) {
        if (m.type === 'announcement') {
            return `
                <div class="p-3 rounded-xl bg-amber-950/30 border border-amber-500/30" data-message-id="${m.id}">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="material-symbols-outlined text-sm text-amber-400" aria-hidden="true">campaign</span>
                        <span class="text-[11px] font-bold text-amber-300">${escapeHtml(m.author)}</span>
                        <span class="text-[10px] text-slate-500">${formatTime(m.at)}</span>
                        ${this.moderationControls(m)}
                    </div>
                    <p class="text-xs text-slate-100 leading-relaxed whitespace-pre-line">${escapeHtml(m.body)}</p>
                </div>
            `;
        }

        const mine = m.user_id === this.userId;

        return `
            <div class="flex ${mine ? 'justify-end' : 'justify-start'}" data-message-id="${m.id}">
                <div class="max-w-[80%] group">
                    <div class="flex items-center gap-1.5 mb-1 ${mine ? 'justify-end' : ''}">
                        <span class="text-[11px] font-semibold text-slate-400">${mine ? 'You' : escapeHtml(m.author)}</span>
                        <span class="text-[10px] text-slate-600">${formatTime(m.at)}</span>
                        ${this.moderationControls(m)}
                    </div>
                    <div class="px-3.5 py-2 rounded-2xl text-xs leading-relaxed whitespace-pre-line ${mine
                        ? 'bg-indigo-600 text-white rounded-br-sm'
                        : 'bg-slate-800 text-slate-100 rounded-bl-sm'}">${escapeHtml(m.body)}</div>
                </div>
            </div>
        `;
    }

    moderationControls(m) {
        if (!this.caps.can_moderate_chat) return '';

        return `
            <span class="opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity inline-flex gap-0.5">
                <button type="button" data-pin="${m.id}" title="${m.is_pinned ? 'Unpin' : 'Pin'}" aria-label="Pin message"
                    class="p-0.5 rounded text-slate-500 hover:text-amber-400">
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">push_pin</span>
                </button>
                <button type="button" data-remove="${m.id}" title="Remove" aria-label="Remove message"
                    class="p-0.5 rounded text-slate-500 hover:text-rose-400">
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">delete</span>
                </button>
            </span>
        `;
    }

    bindModeration() {
        this.log.querySelectorAll('[data-pin]:not([data-bound])').forEach((btn) => {
            btn.setAttribute('data-bound', '1');
            btn.addEventListener('click', async () => {
                try {
                    const res = await this.api.pinMessage(Number(btn.dataset.pin), true);
                    toast(res?.message || 'Message pinned.', 'success');
                } catch (err) {
                    toast(err.message || 'Unable to pin.', 'error');
                }
            });
        });

        this.log.querySelectorAll('[data-remove]:not([data-bound])').forEach((btn) => {
            btn.setAttribute('data-bound', '1');
            btn.addEventListener('click', async () => {
                const id = Number(btn.dataset.remove);
                try {
                    await this.api.deleteMessage(id);
                    this.log.querySelector(`[data-message-id="${id}"]`)?.remove();
                    toast('Message removed.', 'success');
                } catch (err) {
                    toast(err.message || 'Unable to remove.', 'error');
                }
            });
        });
    }

    /** Panels are destroyed when the user switches tabs. */
    destroy() {
        this.unsubscribe?.();
        this.channel.stop('messages');
    }
}
