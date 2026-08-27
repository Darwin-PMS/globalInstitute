/**
 * Room UI primitives: skeletons, empty/error states, toasts, formatting.
 *
 * Skeletons delegate to the shared templates in resources/js/ui/loading.js so the
 * room matches the rest of the portal; everything else here is room-specific.
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

/** Month grid, for the room calendar. */
export function skeletonCalendar(weeks = 5) {
    return skeletons.calendar({ weeks });
}

/** Participant and attendance rows, each ending in a status control. */
export function skeletonRoster(rows = 6) {
    return skeletons.roster({ rows });
}

/** Materials, recordings and assignments: card grid with a thumbnail. */
export function skeletonResources(count = 6) {
    return skeletons.cards({ count, media: true });
}

/**
 * The room shell itself: header, stage, tabs, panel.
 * Mirrors the regions in room.blade.php so the first paint does not shift.
 */
export function skeletonRoomShell() {
    return `
        <div class="skeleton-group" style="gap:24px" aria-hidden="true">
            <div class="skeleton" style="height:64px;border-radius:12px"></div>
            <div class="skeleton" style="height:190px;border-radius:16px"></div>
            <div class="skeleton-row">
                ${Array(5).fill('<div class="skeleton skeleton-btn" style="width:96px;height:36px"></div>').join('')}
            </div>
            <div class="skeleton" style="height:260px;border-radius:16px"></div>
        </div>
    `;
}

/* ------------------------------------------------------- states & chrome */

export function emptyState({ icon = 'inbox', title, description = '', action = '' }) {
    return `
        <div class="flex flex-col items-center justify-center text-center py-14 px-6 bg-slate-900/50 border border-slate-800 rounded-2xl">
            <div class="w-14 h-14 rounded-2xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-center text-slate-400 mb-4">
                <span class="material-symbols-outlined text-3xl" aria-hidden="true">${icon}</span>
            </div>
            <h3 class="text-sm font-semibold text-slate-200 mb-1">${escapeHtml(title)}</h3>
            ${description ? `<p class="text-xs text-slate-400 max-w-sm leading-relaxed">${escapeHtml(description)}</p>` : ''}
            ${action ? `<div class="mt-5">${action}</div>` : ''}
        </div>
    `;
}

export function errorState(message, retryId = null) {
    return `
        <div class="p-6 text-center bg-rose-950/20 border border-rose-500/30 rounded-2xl">
            <span class="material-symbols-outlined text-2xl text-rose-400" aria-hidden="true">error</span>
            <p class="text-xs text-rose-200 mt-2 mb-4">${escapeHtml(message)}</p>
            ${retryId ? `<button type="button" id="${retryId}"
                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition-colors">
                Try again</button>` : ''}
        </div>
    `;
}

export function panelHeading(icon, title, trailing = '') {
    return `
        <div class="flex items-center justify-between gap-3 mb-4">
            <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                <span class="material-symbols-outlined text-base text-indigo-400" aria-hidden="true">${icon}</span>
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

/** "1h 05m" / "12m 30s" — used for time-in-room. */
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
        scheduled: 'bg-blue-500/10 text-blue-300 border-blue-500/25',
        open: 'bg-amber-500/10 text-amber-300 border-amber-500/25',
        live: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
        ended: 'bg-slate-800 text-slate-400 border-slate-700',
        cancelled: 'bg-rose-500/10 text-rose-300 border-rose-500/25',
        archived: 'bg-slate-800 text-slate-500 border-slate-700',
    };

    const dot = state === 'live'
        ? '<span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" aria-hidden="true"></span>'
        : '<span class="w-1.5 h-1.5 rounded-full bg-current opacity-70" aria-hidden="true"></span>';

    return `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border ${tones[state] || tones.scheduled}">
        ${dot}${escapeHtml(label || state)}
    </span>`;
}

/* ----------------------------------------------------------------- toasts */

export function toast(message, type = 'info') {
    const host = document.getElementById('room-toasts');
    if (!host) return;

    const tones = {
        success: 'bg-emerald-950/90 border-emerald-500/30 text-emerald-100',
        error: 'bg-rose-950/90 border-rose-500/30 text-rose-100',
        warning: 'bg-amber-950/90 border-amber-500/30 text-amber-100',
        info: 'bg-slate-900/90 border-slate-700 text-slate-100',
    };
    const icons = { success: 'check_circle', error: 'error', warning: 'warning', info: 'info' };

    const el = document.createElement('div');
    el.className = `pointer-events-auto flex items-start gap-3 p-3.5 rounded-xl border backdrop-blur-lg shadow-2xl text-xs transition-all duration-300 translate-x-3 opacity-0 ${tones[type] || tones.info}`;
    el.innerHTML = `
        <span class="material-symbols-outlined text-lg flex-shrink-0" aria-hidden="true">${icons[type] || 'info'}</span>
        <span class="flex-1 leading-relaxed">${escapeHtml(message)}</span>
    `;

    host.appendChild(el);
    requestAnimationFrame(() => el.classList.remove('translate-x-3', 'opacity-0'));

    setTimeout(() => {
        el.classList.add('translate-x-3', 'opacity-0');
        setTimeout(() => el.remove(), 300);
    }, 4000);
}
