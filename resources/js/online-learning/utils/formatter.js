/**
 * Formatter Utilities for Status Badges, Provider Badges, and Text
 */

export function getStatusBadgeHTML(status) {
    switch (status) {
        case 'scheduled':
            return `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-500/10 text-blue-400 border border-blue-500/20">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span> Scheduled
            </span>`;
        case 'starting_soon':
            return `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20 animate-pulse">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Starting Soon
            </span>`;
        case 'live':
            return `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span> LIVE
            </span>`;
        case 'completed':
            return `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-800 text-slate-400 border border-slate-700">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Completed
            </span>`;
        case 'cancelled':
            return `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-rose-500/10 text-rose-400 border border-rose-500/20">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Cancelled
            </span>`;
        case 'postponed':
            return `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span> Postponed
            </span>`;
        default:
            return `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-800 text-slate-300">
                ${status}
            </span>`;
    }
}

export function getProviderBadgeHTML(provider) {
    const prov = (provider || 'google_meet').toLowerCase();
    let name = 'Google Meet';
    let icon = 'videocam';
    let colorClass = 'text-emerald-400 bg-emerald-500/10';

    if (prov === 'zoom') {
        name = 'Zoom';
        colorClass = 'text-blue-400 bg-blue-500/10';
    } else if (prov === 'teams') {
        name = 'Microsoft Teams';
        colorClass = 'text-indigo-400 bg-indigo-500/10';
    } else if (prov === 'other') {
        name = 'Custom Link';
        icon = 'link';
        colorClass = 'text-purple-400 bg-purple-500/10';
    }

    return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium ${colorClass}">
        <span class="material-symbols-outlined text-sm">${icon}</span> ${name}
    </span>`;
}
