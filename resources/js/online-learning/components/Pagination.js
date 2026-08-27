/**
 * Server-pagination control (§37).
 *
 * Renders from the API `meta` block and emits page changes through a
 * data-attribute so a page can delegate clicks like any other action.
 */

export function renderPagination(meta = {}) {
    const current = Number(meta.current_page) || 1;
    const last = Number(meta.last_page) || 1;

    if (last <= 1) return '';

    const from = meta.from ?? 0;
    const to = meta.to ?? 0;
    const total = meta.total ?? 0;

    return `
        <nav class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 bg-slate-900 border border-slate-800 rounded-xl" aria-label="Pagination">
            <p class="text-[11px] text-slate-400">
                Showing <span class="font-semibold text-slate-200">${from}</span>–<span class="font-semibold text-slate-200">${to}</span>
                of <span class="font-semibold text-slate-200">${total}</span>
            </p>

            <div class="flex items-center gap-1">
                <button type="button" data-page="${current - 1}" ${current <= 1 ? 'disabled' : ''}
                    aria-label="Previous page"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold border transition-colors ${current <= 1
                        ? 'border-slate-800 text-slate-600 cursor-not-allowed'
                        : 'border-slate-700 text-slate-300 hover:bg-slate-800 hover:text-white'}">
                    <span class="material-symbols-outlined text-base align-middle">chevron_left</span>
                </button>

                ${pageWindow(current, last).map((p) => p === '…'
                    ? `<span class="px-2 text-slate-600 text-xs" aria-hidden="true">…</span>`
                    : `<button type="button" data-page="${p}" ${p === current ? 'aria-current="page"' : ''}
                            class="min-w-[2rem] px-2.5 py-1.5 rounded-lg text-xs font-semibold border transition-colors ${p === current
                                ? 'bg-indigo-600 border-indigo-500 text-white'
                                : 'border-slate-700 text-slate-300 hover:bg-slate-800 hover:text-white'}">${p}</button>`
                ).join('')}

                <button type="button" data-page="${current + 1}" ${current >= last ? 'disabled' : ''}
                    aria-label="Next page"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold border transition-colors ${current >= last
                        ? 'border-slate-800 text-slate-600 cursor-not-allowed'
                        : 'border-slate-700 text-slate-300 hover:bg-slate-800 hover:text-white'}">
                    <span class="material-symbols-outlined text-base align-middle">chevron_right</span>
                </button>
            </div>
        </nav>
    `;
}

/**
 * Compact page list: 1 … 4 [5] 6 … 20
 */
function pageWindow(current, last) {
    const pages = new Set([1, last, current, current - 1, current + 1]);
    const sorted = [...pages].filter((p) => p >= 1 && p <= last).sort((a, b) => a - b);

    const out = [];
    sorted.forEach((p, i) => {
        if (i > 0 && p - sorted[i - 1] > 1) out.push('…');
        out.push(p);
    });
    return out;
}

/**
 * Wire a rendered pagination block. `onChange` receives the target page.
 */
export function bindPagination(root, onChange) {
    if (!root) return;

    root.querySelectorAll('[data-page]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (btn.disabled) return;
            const page = parseInt(btn.dataset.page, 10);
            if (!Number.isNaN(page)) onChange(page);
        });
    });
}
