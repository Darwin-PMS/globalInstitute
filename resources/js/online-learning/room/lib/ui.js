/**
 * Room UI primitives: skeletons, empty/error states, toasts, formatting.
 */
import { skeletons } from '../../../ui/loading.js';

export { setButtonLoading, withButtonLoading, showLoader, hideLoader } from '../../../ui/loading.js';

export function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(String(value)));
    return div.innerHTML;
}

/* ------------------------------------------------------------- skeletons */

export function skeletonList(rows = 4) {
    return skeletons.list({ rows });
}

export function skeletonChat(rows = 5) {
    return skeletons.chat({ rows });
}

export function skeletonGrid(count = 6) {
    return skeletons.cards({ count, media: false });
}

export function skeletonCalendar(weeks = 5) {
    return skeletons.calendar({ weeks });
}

export function skeletonRoster(rows = 6) {
    return skeletons.roster({ rows });
}

export function skeletonResources(count = 6) {
    return skeletons.cards({ count, media: true });
}

export function skeletonRoomShell() {
    return `
        <div class="skeleton-group" style="gap:24px" aria-hidden="true">
            <div class="skeleton" style="height:56px;border-radius:14px"></div>
            <div class="skeleton" style="height:280px;border-radius:20px"></div>
            <div class="skeleton-row">
                ${Array(5).fill('<div class="skeleton skeleton-btn" style="width:96px;height:36px;border-radius:12px"></div>').join('')}
            </div>
            <div class="skeleton" style="height:260px;border-radius:20px"></div>
        </div>
    `;
}

/* ------------------------------------------------------- states & chrome */

export function emptyState({ icon = 'inbox', title, description = '', action = '' }) {
    return `
        <div class="flex flex-col items-center justify-center text-center py-14 px-6 bg-[#0a0e17]/50 border border-white/[0.04] rounded-2xl">
            <div class="w-14 h-14 rounded-2xl bg-white/[0.03] border border-white/[0.06] flex items-center justify-center text-slate-400 mb-4">
                <span class="material-symbols-outlined text-[28px]" aria-hidden="true">${icon}</span>
            </div>
            <h3 class="text-sm font-semibold text-white mb-1">${escapeHtml(title)}</h3>
            ${description ? `<p class="text-xs text-slate-400 max-w-sm leading-relaxed">${escapeHtml(description)}</p>` : ''}
            ${action ? `<div class="mt-5">${action}</div>` : ''}
        </div>
    `;
}

export function errorState(message, retryId = null) {
    return `
        <div class="p-6 text-center bg-red-950/10 border border-red-500/15 rounded-2xl">
            <span class="material-symbols-outlined text-[22px] text-red-400" aria-hidden="true">error</span>
            <p class="text-xs text-red-200 mt-2 mb-4">${escapeHtml(message)}</p>
            ${retryId ? `<button type="button" id="${retryId}"
                class="px-4 py-2 rounded-xl bg-white/[0.04] hover:bg-white/[0.08] text-slate-200 text-xs font-semibold border border-white/[0.06] transition-all">
                Try again</button>` : ''}
        </div>
    `;
}

export function panelHeading(icon, title, trailing = '') {
    return `
        <div class="flex items-center justify-between gap-3 mb-4">
            <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                <span class="material-symbols-outlined text-[15px] text-indigo-400" aria-hidden="true">${icon}</span>
                ${escapeHtml(title)}
            </h2>
            ${trailing}
        </div>
    `;
}

/* ------------------------------------------------------------- formatting */

export function formatTime(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

export function formatDateTime(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleString([], { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}

export function formatDuration(seconds) {
    const total = Math.max(0, Math.floor(Number(seconds) || 0));
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;

    if (h > 0) return `${h}h ${String(m).padStart(2, '0')}m`;
    if (m > 0) return `${m}m ${String(s).padStart(2, '0')}s`;
    return `${s}s`;
}

export function roomStateBadge(state, label) {
    const tones = {
        scheduled: 'bg-blue-500/10 text-blue-300 border-blue-500/20',
        open: 'bg-amber-500/10 text-amber-300 border-amber-500/20',
        live: 'bg-emerald-500/15 text-emerald-300 border-emerald-500/25',
        ended: 'bg-white/[0.03] text-slate-500 border-white/[0.06]',
        cancelled: 'bg-red-500/10 text-red-300 border-red-500/20',
        archived: 'bg-white/[0.02] text-slate-600 border-white/[0.04]',
    };

    const dot = state === 'live'
        ? '<span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" aria-hidden="true"></span>'
        : '<span class="w-1.5 h-1.5 rounded-full bg-current opacity-50" aria-hidden="true"></span>';

    return `<span class="inline-flex items-center gap-1.5 px-2.5 py-[3px] rounded-lg text-[11px] font-bold border ${tones[state] || tones.scheduled}">
        ${dot}${escapeHtml(label || state)}
    </span>`;
}

/* ----------------------------------------------------------------- toasts */

export function toast(message, type = 'info') {
    const host = document.getElementById('room-toasts');
    if (!host) return;

    const tones = {
        success: 'bg-emerald-950/90 border-emerald-500/30 text-emerald-100',
        error: 'bg-red-950/90 border-red-500/30 text-red-100',
        warning: 'bg-amber-950/90 border-amber-500/30 text-amber-100',
        info: 'bg-[#0f1520]/90 border-white/[0.08] text-slate-100',
    };
    const icons = { success: 'check_circle', error: 'error', warning: 'warning', info: 'info' };

    const el = document.createElement('div');
    el.className = `pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-xl border backdrop-blur-lg shadow-2xl text-xs font-medium transition-all duration-300 translate-x-4 opacity-0 ${tones[type] || tones.info}`;
    el.innerHTML = `
        <span class="material-symbols-outlined text-[16px] flex-shrink-0 mt-px" aria-hidden="true">${icons[type] || 'info'}</span>
        <span class="flex-1 leading-relaxed">${escapeHtml(message)}</span>
    `;

    host.appendChild(el);
    requestAnimationFrame(() => el.classList.remove('translate-x-4', 'opacity-0'));

    setTimeout(() => {
        el.classList.add('translate-x-4', 'opacity-0');
        setTimeout(() => el.remove(), 300);
    }, 4000);
}
