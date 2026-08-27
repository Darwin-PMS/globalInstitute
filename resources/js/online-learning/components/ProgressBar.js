/**
 * Progress bar / ring primitives shared by course, lesson and attendance views.
 */

const TONES = {
    indigo: 'from-indigo-500 to-purple-500',
    emerald: 'from-emerald-500 to-teal-500',
    amber: 'from-amber-500 to-orange-500',
    rose: 'from-rose-500 to-pink-500',
};

/**
 * Pick a tone from the value so a weak figure reads as weak without the
 * caller having to decide.
 */
export function toneFor(percent) {
    if (percent >= 75) return 'emerald';
    if (percent >= 40) return 'indigo';
    if (percent >= 15) return 'amber';
    return 'rose';
}

export function renderProgressBar(percent = 0, options = {}) {
    const value = Math.max(0, Math.min(100, Math.round(Number(percent) || 0)));
    const tone = TONES[options.tone || toneFor(value)] || TONES.indigo;
    const label = options.label || '';
    const showValue = options.showValue !== false;
    const height = options.size === 'sm' ? 'h-1.5' : 'h-2.5';

    return `
        <div class="space-y-1.5">
            ${label || showValue ? `
                <div class="flex items-center justify-between text-[11px]">
                    ${label ? `<span class="font-medium text-slate-400">${label}</span>` : '<span></span>'}
                    ${showValue ? `<span class="font-bold text-slate-200 tabular-nums">${value}%</span>` : ''}
                </div>
            ` : ''}
            <div class="w-full ${height} bg-slate-800 rounded-full overflow-hidden"
                 role="progressbar" aria-valuenow="${value}" aria-valuemin="0" aria-valuemax="100"
                 ${label ? `aria-label="${label}"` : ''}>
                <div class="h-full bg-gradient-to-r ${tone} rounded-full transition-all duration-500" style="width: ${value}%"></div>
            </div>
        </div>
    `;
}

/**
 * Compact SVG ring for stat tiles.
 */
export function renderProgressRing(percent = 0, options = {}) {
    const value = Math.max(0, Math.min(100, Math.round(Number(percent) || 0)));
    const size = options.size || 88;
    const stroke = options.stroke || 8;
    const radius = (size - stroke) / 2;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (value / 100) * circumference;

    const strokeColor = {
        emerald: '#10b981',
        indigo: '#6366f1',
        amber: '#f59e0b',
        rose: '#f43f5e',
    }[options.tone || toneFor(value)] || '#6366f1';

    return `
        <div class="relative inline-flex items-center justify-center" style="width:${size}px;height:${size}px"
             role="img" aria-label="${options.label || 'Progress'}: ${value} percent">
            <svg width="${size}" height="${size}" class="-rotate-90" aria-hidden="true">
                <circle cx="${size / 2}" cy="${size / 2}" r="${radius}" fill="none"
                        stroke="#1e293b" stroke-width="${stroke}"></circle>
                <circle cx="${size / 2}" cy="${size / 2}" r="${radius}" fill="none"
                        stroke="${strokeColor}" stroke-width="${stroke}" stroke-linecap="round"
                        stroke-dasharray="${circumference}" stroke-dashoffset="${offset}"
                        style="transition: stroke-dashoffset 600ms ease"></circle>
            </svg>
            <span class="absolute text-sm font-black text-slate-100 tabular-nums">${value}%</span>
        </div>
    `;
}
