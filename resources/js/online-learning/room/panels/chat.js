import { escapeHtml, formatTime, toast } from '../lib/ui.js';

/**
 * Chat Panel — Classroom-specific chat for the sidebar.
 *
 * Supports messages, announcements, pinned messages, moderation, and
 * real-time updates via the channel's delta polling.
 */
export default class ChatPanel {
    constructor(ctx) {
        this.api = ctx.api;
        this.channel = ctx.channel;
        this.caps = ctx.capabilities;
        this.userId = ctx.userId;
        this.mount = ctx.mount;
        this.shell = ctx.shell;
        this.seen = new Set();
        this.unsubscribe = null;
    }

    async render() {
        let initial;
        try {
            initial = await this.api.messages();
        } catch (err) {
            this.mount.innerHTML = this.renderError(err.message || 'Unable to load the chat.');
            this.mount.querySelector('#chat-retry')?.addEventListener('click', () => this.render());
            return;
        }
        if (!initial) return;

        const messages = initial.data?.messages || [];
        const pinned = initial.data?.pinned || [];

        this.channel.setCursor('messages', initial.meta?.last_id);

        this.paint(pinned);

        if (messages.length) {
            this.append(messages);
        } else {
            this.log.innerHTML = this.renderEmpty();
        }

        this.unsubscribe = this.channel.on('messages', ({ messages: incoming }) => {
            this.append(incoming);
            this.clearUnreadBadge();
        });
        this.channel.start('messages');
    }

    paint(pinned) {
        const canPost = this.caps.can_post_message || this.caps.can_post_announcement;

        this.mount.innerHTML = `
            <div class="cr-chat">
                ${pinned.length ? `
                    <div id="chat-pinned" class="cr-panel" style="border-bottom:1px solid var(--cr-border-subtle);padding:var(--cr-space-3)">
                        ${pinned.map((m) => `
                            <div class="flex items-start gap-2.5 mb-2 last:mb-0">
                                <span class="material-symbols-outlined text-[14px] text-amber-400 mt-0.5" aria-hidden="true">push_pin</span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold text-amber-300">${escapeHtml(m.author)}</p>
                                    <p class="text-xs text-slate-200 leading-relaxed mt-0.5">${escapeHtml(m.body)}</p>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                ` : ''}

                <div id="chat-log" class="cr-chat-log" role="log" aria-label="Class chat"></div>

                ${canPost ? `
                    <form id="chat-form" class="cr-chat-composer">
                        <label for="chat-input" class="sr-only">Message</label>
                        <textarea id="chat-input" rows="1" maxlength="2000" class="cr-chat-input"
                            placeholder="${this.caps.can_post_message ? 'Message the class…' : 'Post an announcement…'}"></textarea>

                        ${this.caps.can_post_announcement ? `
                            <label class="flex items-center gap-1.5 text-[11px] text-slate-500 whitespace-nowrap cursor-pointer hover:text-slate-300 transition-colors pb-1">
                                <input type="checkbox" id="chat-announce" class="w-3.5 h-3.5 rounded border-slate-700 bg-slate-950 text-amber-500 focus:ring-amber-500/30 focus:ring-offset-0">
                                Announce
                            </label>
                        ` : ''}

                        <button type="submit" id="chat-send" class="cr-chat-send">Send</button>
                    </form>
                ` : `
                    <div class="flex-shrink-0 border-t border-white/[0.04] px-4 py-3 text-[11px] text-slate-600 text-center">
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

    append(messages) {
        if (!this.log || !messages?.length) return;

        const fresh = messages.filter((m) => !this.seen.has(m.id));
        if (!fresh.length) return;

        if (this.seen.size === 0) this.log.innerHTML = '';

        const nearBottom = this.log.scrollHeight - this.log.scrollTop - this.log.clientHeight < 80;

        fresh.forEach((m) => {
            this.seen.add(m.id);
            this.log.insertAdjacentHTML('beforeend', this.bubble(m));
        });

        if (nearBottom) this.log.scrollTop = this.log.scrollHeight;

        if (this.caps.can_moderate_chat) this.bindModeration();
    }

    bubble(m) {
        if (m.type === 'system') {
            return `<div class="cr-chat-system">${escapeHtml(m.body)}</div>`;
        }

        if (m.type === 'announcement') {
            return `
                <div class="cr-chat-announcement" data-message-id="${m.id}">
                    <div class="cr-chat-ann-header">
                        <span class="material-symbols-outlined" aria-hidden="true">campaign</span>
                        <span class="cr-ann-author">${escapeHtml(m.author)}</span>
                        <span class="text-[10px] text-slate-600">${formatTime(m.at)}</span>
                        ${this.moderationControls(m)}
                    </div>
                    <p class="text-xs text-slate-100 leading-relaxed whitespace-pre-line">${escapeHtml(m.body)}</p>
                </div>
            `;
        }

        const mine = m.user_id === this.userId;

        return `
            <div class="cr-chat-msg ${mine ? 'mine' : ''}" data-message-id="${m.id}">
                <div class="cr-chat-author">
                    <span class="cr-chat-sender">${mine ? 'You' : escapeHtml(m.author)}</span>
                    <span class="cr-chat-time">${formatTime(m.at)}</span>
                    ${this.moderationControls(m)}
                </div>
                <div class="cr-chat-bubble">${escapeHtml(m.body)}</div>
            </div>
        `;
    }

    moderationControls(m) {
        if (!this.caps.can_moderate_chat) return '';

        return `
            <span class="opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity inline-flex gap-0.5">
                <button type="button" data-pin="${m.id}" title="${m.is_pinned ? 'Unpin' : 'Pin'}" aria-label="Pin message"
                    class="p-0.5 rounded text-slate-600 hover:text-amber-400 transition-colors">
                    <span class="material-symbols-outlined text-[13px]" aria-hidden="true">push_pin</span>
                </button>
                <button type="button" data-remove="${m.id}" title="Remove" aria-label="Remove message"
                    class="p-0.5 rounded text-slate-600 hover:text-red-400 transition-colors">
                    <span class="material-symbols-outlined text-[13px]" aria-hidden="true">delete</span>
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

    clearUnreadBadge() {
        const badge = document.getElementById('chat-unread-badge');
        if (badge) {
            badge.textContent = '0';
            badge.style.display = 'none';
        }
    }

    renderEmpty() {
        return `
            <div class="cr-empty">
                <div class="cr-empty-icon">
                    <span class="material-symbols-outlined">chat_bubble</span>
                </div>
                <p class="cr-empty-title">No messages yet</p>
                <p class="cr-empty-desc">Start the conversation.</p>
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
                <button type="button" id="chat-retry" class="cr-modal-btn secondary">Try again</button>
            </div>
        `;
    }

    destroy() {
        this.unsubscribe?.();
        this.channel.stop('messages');
    }
}
