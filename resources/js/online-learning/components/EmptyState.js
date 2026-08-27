/**
 * Reusable Empty State Component
 */

export function getEmptyStateHTML(options = {}) {
    const icon = options.icon || 'videocam_off';
    const title = options.title || 'No classes found';
    const description = options.description || 'There are no classes scheduled for the selected filter criteria.';
    const actionButtonText = options.actionButtonText || null;
    const actionButtonId = options.actionButtonId || null;

    return `
        <div class="flex flex-col items-center justify-center p-12 text-center bg-slate-900/50 border border-slate-800/80 rounded-2xl">
            <div class="w-16 h-16 rounded-2xl bg-slate-800/80 border border-slate-700/50 flex items-center justify-center text-slate-400 mb-4 shadow-inner">
                <span class="material-symbols-outlined text-3xl">${icon}</span>
            </div>
            <h3 class="text-base font-semibold text-slate-200 mb-1">${title}</h3>
            <p class="text-xs text-slate-400 max-w-sm mb-6 leading-relaxed">${description}</p>
            ${actionButtonText ? `
                <button id="${actionButtonId}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold shadow-lg shadow-indigo-600/20 transition-all hover:scale-[1.02] active:scale-[0.98]">
                    <span class="material-symbols-outlined text-sm">add</span> ${actionButtonText}
                </button>
            ` : ''}
        </div>
    `;
}
