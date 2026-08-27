import { classDetailApi } from '../../api/learning.js';
import { getEmptyStateHTML } from '../../components/EmptyState.js';
import { formatTime } from '../../utils/date.js';
import { getStatusBadgeHTML } from '../../utils/formatter.js';
import { path } from '../../utils/permissions.js';

const VIEWS = [
    { key: 'month', label: 'Month' },
    { key: 'week', label: 'Week' },
    { key: 'day', label: 'Day' },
    { key: 'agenda', label: 'Agenda' },
];

const DAY_MS = 86400000;

/**
 * Calendar for every role (§21). Month / week / day / agenda.
 *
 * The server scopes the range by role, so the same component serves admin,
 * teacher and student without a client-side filter deciding visibility.
 *
 * Drag-to-reschedule is deliberately NOT implemented: rescheduling has to run
 * the conflict checks server-side, so it stays an explicit action on the
 * class rather than a drag the front end could fake.
 */
export class CalendarPage {
    constructor() {
        this.container = document.getElementById('learning-content');
        this.view = localStorage.getItem('lms_calendar_view') || 'month';
        this.cursor = this.startOfDay(new Date());
        this.classes = [];
    }

    async render() {
        if (!this.container) return;

        const params = new URLSearchParams(window.location.search);
        if (VIEWS.some((v) => v.key === params.get('view'))) this.view = params.get('view');
        if (params.get('date')) {
            const parsed = new Date(`${params.get('date')}T00:00:00`);
            if (!Number.isNaN(parsed.getTime())) this.cursor = this.startOfDay(parsed);
        }

        this.paintShell();
        await this.fetch();
    }

    /* ---------- range maths ---------- */

    startOfDay(d) {
        const copy = new Date(d);
        copy.setHours(0, 0, 0, 0);
        return copy;
    }

    iso(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    /**
     * The fetch range for the active view. Month view pads to whole weeks so
     * the leading/trailing cells are populated too.
     */
    range() {
        const c = this.cursor;

        if (this.view === 'day') {
            return { from: c, to: c };
        }

        if (this.view === 'week') {
            const start = new Date(c.getTime() - c.getDay() * DAY_MS);
            return { from: start, to: new Date(start.getTime() + 6 * DAY_MS) };
        }

        if (this.view === 'agenda') {
            return { from: c, to: new Date(c.getTime() + 29 * DAY_MS) };
        }

        const first = new Date(c.getFullYear(), c.getMonth(), 1);
        const last = new Date(c.getFullYear(), c.getMonth() + 1, 0);
        return {
            from: new Date(first.getTime() - first.getDay() * DAY_MS),
            to: new Date(last.getTime() + (6 - last.getDay()) * DAY_MS),
        };
    }

    step(direction) {
        const c = new Date(this.cursor);

        if (this.view === 'month') c.setMonth(c.getMonth() + direction);
        else if (this.view === 'week') c.setDate(c.getDate() + 7 * direction);
        else if (this.view === 'agenda') c.setDate(c.getDate() + 30 * direction);
        else c.setDate(c.getDate() + direction);

        this.cursor = this.startOfDay(c);
        this.syncUrl();
        this.paintShell();
        this.fetch();
    }

    syncUrl() {
        const params = new URLSearchParams(window.location.search);
        params.set('view', this.view);
        params.set('date', this.iso(this.cursor));
        window.history.replaceState(window.history.state, '', `${window.location.pathname}?${params.toString()}`);
    }

    label() {
        const opts = { month: 'long', year: 'numeric' };

        if (this.view === 'day') {
            return this.cursor.toLocaleDateString('en-US', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        }
        if (this.view === 'week') {
            const { from, to } = this.range();
            return `${from.toLocaleDateString('en-US', { day: 'numeric', month: 'short' })} – ${to.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' })}`;
        }
        if (this.view === 'agenda') {
            const { from, to } = this.range();
            return `Next 30 days · ${from.toLocaleDateString('en-US', { day: 'numeric', month: 'short' })} – ${to.toLocaleDateString('en-US', { day: 'numeric', month: 'short' })}`;
        }
        return this.cursor.toLocaleDateString('en-US', opts);
    }

    /* ---------- rendering ---------- */

    paintShell() {
        this.container.innerHTML = `
            <div class="space-y-5">
                <header class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1">
                            <button type="button" id="cal-prev" aria-label="Previous period"
                                class="p-2 rounded-lg bg-slate-950 border border-slate-800 text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
                                <span class="material-symbols-outlined text-base">chevron_left</span>
                            </button>
                            <button type="button" id="cal-next" aria-label="Next period"
                                class="p-2 rounded-lg bg-slate-950 border border-slate-800 text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
                                <span class="material-symbols-outlined text-base">chevron_right</span>
                            </button>
                        </div>

                        <div>
                            <h2 class="text-base font-bold text-slate-100">${this.label()}</h2>
                            <p class="text-[11px] text-slate-400" id="cal-count">Loading…</p>
                        </div>

                        <button type="button" id="cal-today"
                            class="ml-2 px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-slate-300 hover:text-white hover:bg-slate-800 text-[11px] font-semibold transition-colors">
                            Today
                        </button>
                    </div>

                    <div class="flex items-center bg-slate-950 p-1 rounded-xl border border-slate-800 self-start" role="tablist" aria-label="Calendar view">
                        ${VIEWS.map((v) => `
                            <button type="button" role="tab" aria-selected="${this.view === v.key}" data-view="${v.key}"
                                class="px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-colors ${this.view === v.key
                                    ? 'bg-indigo-600 text-white shadow'
                                    : 'text-slate-400 hover:text-slate-200'}">
                                ${v.label}
                            </button>
                        `).join('')}
                    </div>
                </header>

                <div id="cal-body">
                    <div class="h-96 bg-slate-900/70 border border-slate-800 rounded-2xl animate-pulse"></div>
                </div>
            </div>
        `;

        this.container.querySelector('#cal-prev').addEventListener('click', () => this.step(-1));
        this.container.querySelector('#cal-next').addEventListener('click', () => this.step(1));

        this.container.querySelector('#cal-today').addEventListener('click', () => {
            this.cursor = this.startOfDay(new Date());
            this.syncUrl();
            this.paintShell();
            this.fetch();
        });

        this.container.querySelectorAll('[data-view]').forEach((btn) => {
            btn.addEventListener('click', () => {
                this.view = btn.dataset.view;
                localStorage.setItem('lms_calendar_view', this.view);
                this.syncUrl();
                this.paintShell();
                this.fetch();
            });
        });
    }

    async fetch() {
        const { from, to } = this.range();

        try {
            const res = await classDetailApi.calendar({ from: this.iso(from), to: this.iso(to) });
            if (!res) return;

            this.classes = res.data || [];
            this.paintBody();

            const count = this.container.querySelector('#cal-count');
            if (count) {
                count.textContent = `${this.classes.length} ${this.classes.length === 1 ? 'class' : 'classes'} in view`;
            }
        } catch (err) {
            const body = this.container.querySelector('#cal-body');
            if (body) {
                body.innerHTML = `
                    <div class="p-8 text-center bg-rose-950/20 border border-rose-500/30 rounded-2xl">
                        <p class="text-xs text-rose-300 mb-4">Unable to load the calendar. ${err.message}</p>
                        <button id="cal-retry" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700">Try again</button>
                    </div>
                `;
                body.querySelector('#cal-retry')?.addEventListener('click', () => this.fetch());
            }
        }
    }

    /**
     * Index the fetched classes by yyyy-mm-dd for O(1) cell lookup.
     */
    byDate() {
        const map = new Map();
        this.classes.forEach((c) => {
            const key = String(c.class_date).substring(0, 10);
            if (!map.has(key)) map.set(key, []);
            map.get(key).push(c);
        });
        // Within a day, order by start time.
        map.forEach((list) => list.sort((a, b) => String(a.start_time).localeCompare(String(b.start_time))));
        return map;
    }

    paintBody() {
        const body = this.container.querySelector('#cal-body');
        if (!body) return;

        if (this.classes.length === 0) {
            body.innerHTML = getEmptyStateHTML({
                icon: 'event_busy',
                title: 'Nothing scheduled',
                description: 'There are no classes in this period. Use the arrows to look at another range.',
            });
            return;
        }

        if (this.view === 'agenda' || this.view === 'day') body.innerHTML = this.agendaHTML();
        else if (this.view === 'week') body.innerHTML = this.weekHTML();
        else body.innerHTML = this.monthHTML();

        this.bindCells(body);
    }

    monthHTML() {
        const map = this.byDate();
        const { from, to } = this.range();
        const todayKey = this.iso(new Date());
        const month = this.cursor.getMonth();

        const cells = [];
        for (let d = new Date(from); d <= to; d = new Date(d.getTime() + DAY_MS)) {
            const key = this.iso(d);
            const items = map.get(key) || [];
            const outside = d.getMonth() !== month;

            cells.push(`
                <div class="min-h-[104px] p-2 border border-slate-800/60 ${outside ? 'bg-slate-950/40' : 'bg-slate-900/60'} space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold ${key === todayKey
                            ? 'w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center'
                            : outside ? 'text-slate-600' : 'text-slate-400'}">${d.getDate()}</span>
                        ${items.length > 2 ? `<span class="text-[10px] text-slate-500">${items.length}</span>` : ''}
                    </div>

                    ${items.slice(0, 3).map((c) => `
                        <button type="button" data-class-id="${c.id}"
                            class="w-full text-left px-1.5 py-1 rounded-md text-[10px] font-semibold truncate transition-colors ${this.chipTone(c.status)}"
                            title="${c.title} · ${formatTime(c.start_time)}">
                            ${formatTime(c.start_time)} ${c.title}
                        </button>
                    `).join('')}

                    ${items.length > 3 ? `<span class="block px-1.5 text-[10px] text-slate-500">+${items.length - 3} more</span>` : ''}
                </div>
            `);
        }

        return `
            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
                <div class="grid grid-cols-7 bg-slate-950/70 border-b border-slate-800">
                    ${['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((d) => `
                        <div class="py-2.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400">${d}</div>
                    `).join('')}
                </div>
                <div class="grid grid-cols-7">${cells.join('')}</div>
            </div>
        `;
    }

    weekHTML() {
        const map = this.byDate();
        const { from } = this.range();
        const todayKey = this.iso(new Date());

        const days = Array.from({ length: 7 }, (_, i) => new Date(from.getTime() + i * DAY_MS));

        return `
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-7 gap-3">
                ${days.map((d) => {
                    const key = this.iso(d);
                    const items = map.get(key) || [];

                    return `
                        <div class="bg-slate-900 border ${key === todayKey ? 'border-indigo-500/40' : 'border-slate-800'} rounded-xl p-3 space-y-2 min-h-[140px]">
                            <div class="pb-2 border-b border-slate-800">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                    ${d.toLocaleDateString('en-US', { weekday: 'short' })}
                                </p>
                                <p class="text-sm font-black ${key === todayKey ? 'text-indigo-400' : 'text-slate-200'}">${d.getDate()}</p>
                            </div>

                            ${items.length === 0
                                ? `<p class="text-[10px] text-slate-600">—</p>`
                                : items.map((c) => `
                                    <button type="button" data-class-id="${c.id}"
                                        class="w-full text-left p-2 rounded-lg text-[10px] font-semibold transition-colors ${this.chipTone(c.status)}">
                                        <span class="block tabular-nums">${formatTime(c.start_time)}</span>
                                        <span class="block truncate font-bold">${c.title}</span>
                                    </button>
                                `).join('')}
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    }

    agendaHTML() {
        const map = this.byDate();
        const keys = [...map.keys()].sort();

        return `
            <div class="space-y-4">
                ${keys.map((key) => {
                    const items = map.get(key);
                    const d = new Date(`${key}T00:00:00`);

                    return `
                        <section class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5">
                            <div class="flex items-center gap-3 pb-3 mb-3 border-b border-slate-800">
                                <div class="w-11 h-11 rounded-xl bg-indigo-600/10 border border-indigo-500/20 flex flex-col items-center justify-center flex-shrink-0">
                                    <span class="text-[9px] font-bold uppercase text-indigo-400">${d.toLocaleDateString('en-US', { month: 'short' })}</span>
                                    <span class="text-sm font-black text-slate-100 leading-none">${d.getDate()}</span>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-100">${d.toLocaleDateString('en-US', { weekday: 'long' })}</h3>
                                    <p class="text-[11px] text-slate-400">${items.length} ${items.length === 1 ? 'class' : 'classes'}</p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                ${items.map((c) => `
                                    <button type="button" data-class-id="${c.id}"
                                        class="w-full flex flex-col sm:flex-row sm:items-center gap-3 p-3 bg-slate-950/70 border border-slate-800 rounded-xl hover:border-indigo-500/40 transition-colors text-left">
                                        <span class="text-[11px] font-bold text-slate-300 tabular-nums sm:w-32 flex-shrink-0">
                                            ${formatTime(c.start_time)} – ${formatTime(c.end_time)}
                                        </span>
                                        <span class="flex-1 min-w-0">
                                            <span class="block text-xs font-bold text-slate-100 truncate">${c.title}</span>
                                            <span class="block text-[11px] text-slate-400 truncate">
                                                ${c.course?.title || 'Course'}
                                                ${c.teacher?.name ? ` • ${c.teacher.name}` : ''}
                                            </span>
                                        </span>
                                        <span class="flex-shrink-0">${getStatusBadgeHTML(c.status)}</span>
                                    </button>
                                `).join('')}
                            </div>
                        </section>
                    `;
                }).join('')}
            </div>
        `;
    }

    bindCells(root) {
        root.querySelectorAll('[data-class-id]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.classId;
                // Navigate through the router so back/forward keep working.
                window.spaRouter?.navigate(path('classes', id));
            });
        });
    }

    chipTone(status) {
        return {
            live: 'bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30',
            starting_soon: 'bg-amber-500/20 text-amber-300 hover:bg-amber-500/30',
            cancelled: 'bg-rose-500/15 text-rose-300 line-through hover:bg-rose-500/25',
            completed: 'bg-slate-800 text-slate-400 hover:bg-slate-700',
            postponed: 'bg-indigo-500/15 text-indigo-300 hover:bg-indigo-500/25',
        }[status] || 'bg-blue-500/15 text-blue-300 hover:bg-blue-500/25';
    }
}
