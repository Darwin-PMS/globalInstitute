import { classesApi } from '../../api/classes.js';
import { adminLearningApi } from '../../api/adminLearning.js';
import { getDashboardSkeletonHTML, getTableSkeletonHTML } from '../../components/LoadingState.js';
import { renderClassTable } from '../../components/ClassTable.js';
import { getEmptyStateHTML } from '../../components/EmptyState.js';
import { path } from '../../utils/permissions.js';

/**
 * Admin operations dashboard (§10).
 *
 * Loads progressively (§41): the shell paints first, then the summary tiles
 * and the recent-classes table fill in independently, so a slow table never
 * holds up the numbers.
 */
export class AdminDashboardPage {
    constructor() {
        this.container = document.getElementById('learning-content');
    }

    async render() {
        if (!this.container) return;

        this.container.innerHTML = getDashboardSkeletonHTML();
        this.paintShell();

        // Two independent requests - neither blocks the other.
        this.loadSummary();
        this.loadRecentClasses();
    }

    paintShell() {
        this.container.innerHTML = `
            <div class="space-y-8">
                <section class="bg-gradient-to-r from-indigo-950 via-slate-900 to-slate-950 border border-indigo-500/20 rounded-2xl p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>

                    <div class="space-y-2 max-w-xl">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            Admin control panel
                        </span>
                        <h2 class="text-2xl font-black text-white tracking-tight">Online Learning Operations</h2>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Schedule live sessions, watch attendance, and keep recordings flowing to students.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <a href="${path('calendar')}" data-spa-link
                           class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900/80 hover:bg-slate-800 text-slate-200 rounded-xl text-xs font-semibold border border-slate-700 transition-all">
                            <span class="material-symbols-outlined text-lg" aria-hidden="true">calendar_month</span> Calendar
                        </a>
                        <button type="button" id="btn-create-class-dash"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30 transition-all hover:scale-105 active:scale-95">
                            <span class="material-symbols-outlined text-lg" aria-hidden="true">add</span> Schedule Class
                        </button>
                    </div>
                </section>

                <section aria-label="Summary">
                    <div id="dash-summary" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        ${Array(8).fill(0).map(() => `
                            <div class="h-24 bg-slate-900/80 border border-slate-800 rounded-2xl animate-pulse"></div>
                        `).join('')}
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-100 flex items-center gap-2">
                            <span class="material-symbols-outlined text-indigo-400" aria-hidden="true">schedule</span>
                            Recent class schedules
                        </h3>
                        <a href="${path('classes')}" data-spa-link
                           class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors">
                            View all classes →
                        </a>
                    </div>

                    <div id="dash-recent">${getTableSkeletonHTML(5)}</div>
                </section>
            </div>
        `;

        this.container.querySelector('#btn-create-class-dash')?.addEventListener('click', () => {
            window.spaRouter?.navigate(`${path('classes')}?action=create`);
        });
    }

    async loadSummary() {
        const mount = this.container.querySelector('#dash-summary');
        if (!mount) return;

        try {
            const res = await adminLearningApi.summary();
            if (!res) return;

            const d = res.data || {};
            const today = d.today || {};

            const tiles = [
                { label: "Today's classes", value: today.total ?? 0, icon: 'today', tone: 'indigo' },
                { label: 'Live now', value: d.live_now ?? 0, icon: 'sensors', tone: 'emerald', pulse: (d.live_now ?? 0) > 0 },
                { label: 'Upcoming', value: d.upcoming ?? 0, icon: 'event_upcoming', tone: 'blue' },
                { label: 'Completed today', value: today.completed ?? 0, icon: 'task_alt', tone: 'purple' },
                { label: 'Cancelled today', value: today.cancelled ?? 0, icon: 'event_busy', tone: 'rose' },
                { label: 'Pending recordings', value: d.pending_recordings ?? 0, icon: 'video_library', tone: 'amber', href: path('recordings') },
                { label: 'Ungraded work', value: d.pending_assignments ?? 0, icon: 'grading', tone: 'amber' },
                { label: 'Total classes', value: d.total_classes ?? 0, icon: 'video_camera_front', tone: 'slate' },
            ];

            mount.innerHTML = tiles.map((t) => this.tile(t)).join('');
        } catch (err) {
            mount.innerHTML = `
                <div class="col-span-2 md:col-span-4 p-6 text-center bg-rose-950/20 border border-rose-500/30 rounded-2xl">
                    <p class="text-xs text-rose-300 mb-3">Unable to load the summary. ${err.message}</p>
                    <button id="summary-retry" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700">
                        Try again
                    </button>
                </div>
            `;
            mount.querySelector('#summary-retry')?.addEventListener('click', () => this.loadSummary());
        }
    }

    tile({ label, value, icon, tone, pulse, href }) {
        const tones = {
            indigo: 'text-indigo-400 bg-indigo-500/10',
            emerald: 'text-emerald-400 bg-emerald-500/10',
            blue: 'text-blue-400 bg-blue-500/10',
            purple: 'text-purple-400 bg-purple-500/10',
            rose: 'text-rose-400 bg-rose-500/10',
            amber: 'text-amber-400 bg-amber-500/10',
            slate: 'text-slate-300 bg-slate-800',
        };
        const toneClass = tones[tone] || tones.slate;
        const valueTone = toneClass.split(' ')[0];

        const inner = `
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">${label}</div>
                    <div class="text-2xl font-black mt-1 tabular-nums ${valueTone}">${value}</div>
                </div>
                <div class="w-10 h-10 rounded-xl ${toneClass} flex items-center justify-center flex-shrink-0 ${pulse ? 'animate-pulse' : ''}">
                    <span class="material-symbols-outlined text-xl" aria-hidden="true">${icon}</span>
                </div>
            </div>
        `;

        return href
            ? `<a href="${href}" data-spa-link class="block bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-2xl p-5 shadow-xl transition-colors">${inner}</a>`
            : `<div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">${inner}</div>`;
    }

    async loadRecentClasses() {
        const mount = this.container.querySelector('#dash-recent');
        if (!mount) return;

        try {
            const res = await classesApi.getAdminClasses({ per_page: 8 });
            if (!res) return;

            const classes = res.data || [];

            mount.innerHTML = classes.length > 0
                ? renderClassTable(classes)
                : getEmptyStateHTML({
                    title: 'No classes scheduled yet',
                    description: 'Schedule your first live online class to get started.',
                    actionButtonText: 'Schedule first class',
                    actionButtonId: 'btn-empty-create',
                });

            mount.querySelector('#btn-empty-create')?.addEventListener('click', () => {
                window.spaRouter?.navigate(`${path('classes')}?action=create`);
            });

            // Rows carry action buttons; the list screen owns those handlers,
            // so from here a click just opens the class.
            mount.querySelectorAll('[data-action]').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const id = btn.dataset.id;
                    if (id) window.spaRouter?.navigate(path('classes', id));
                });
            });
        } catch (err) {
            mount.innerHTML = `
                <div class="p-8 text-center bg-rose-950/20 border border-rose-500/30 rounded-2xl">
                    <p class="text-xs text-rose-300 mb-4">Unable to load recent classes. ${err.message}</p>
                    <button id="recent-retry" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700">
                        Try again
                    </button>
                </div>
            `;
            mount.querySelector('#recent-retry')?.addEventListener('click', () => this.loadRecentClasses());
        }
    }
}
