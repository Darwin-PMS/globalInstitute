import { getCountdownParts } from '../utils/date.js';

export class Countdown {
    constructor(targetDate, targetTime, status = 'scheduled') {
        this.targetDate = targetDate;
        this.targetTime = targetTime;
        this.status = status;
        this.timerId = null;
    }

    getDisplayState() {
        if (this.status === 'cancelled') {
            return { label: 'Class Cancelled', state: 'cancelled', colorClass: 'text-rose-400 bg-rose-500/10 border-rose-500/20' };
        }
        if (this.status === 'completed') {
            return { label: 'Class Ended', state: 'completed', colorClass: 'text-slate-400 bg-slate-800 border-slate-700' };
        }
        if (this.status === 'live') {
            return { label: '● LIVE NOW', state: 'live', colorClass: 'text-emerald-400 bg-emerald-500/20 border-emerald-500/30 font-bold animate-pulse' };
        }

        const parts = getCountdownParts(this.targetDate, this.targetTime);
        if (!parts || parts.isPast) {
            return { label: 'Class is Ready / In Progress', state: 'ready', colorClass: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20 font-semibold' };
        }

        if (parts.days > 0) {
            return { label: `Starts in ${parts.days}d ${parts.hours}h`, state: 'upcoming', colorClass: 'text-indigo-400 bg-indigo-500/10 border-indigo-500/20' };
        }
        if (parts.hours > 0) {
            return { label: `Starts in ${parts.hours}h ${parts.minutes}m`, state: 'upcoming', colorClass: 'text-indigo-400 bg-indigo-500/10 border-indigo-500/20' };
        }

        if (parts.minutes <= 15) {
            return { label: `Starts in ${parts.minutes}m ${parts.seconds}s — Ready soon`, state: 'starting_soon', colorClass: 'text-amber-400 bg-amber-500/10 border-amber-500/20 font-semibold animate-pulse' };
        }

        return { label: `Starts in ${parts.minutes} minutes`, state: 'upcoming', colorClass: 'text-indigo-400 bg-indigo-500/10 border-indigo-500/20' };
    }

    renderHTML() {
        const info = this.getDisplayState();
        return `
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs border ${info.colorClass}">
                <span class="material-symbols-outlined text-sm">schedule</span>
                ${info.label}
            </span>
        `;
    }

    startTicking(element) {
        if (!element) return;
        const update = () => {
            element.innerHTML = this.renderHTML();
        };
        update();
        this.timerId = setInterval(update, 1000);
    }

    stop() {
        if (this.timerId) {
            clearInterval(this.timerId);
            this.timerId = null;
        }
    }
}
