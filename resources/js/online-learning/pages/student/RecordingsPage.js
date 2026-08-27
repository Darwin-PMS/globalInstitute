import { recordingsApi } from '../../api/recordings.js';
import { getCardsSkeletonHTML } from '../../components/LoadingState.js';
import { getEmptyStateHTML } from '../../components/EmptyState.js';
import { renderPagination, bindPagination } from '../../components/Pagination.js';
import { debounce } from '../../utils/debounce.js';
import { formatDate } from '../../utils/date.js';
import { hydrateFilters, writeQuery } from '../../utils/urlState.js';
import { path } from '../../utils/permissions.js';

/**
 * Student recordings library. Search + course filter, server-paginated,
 * with filter state mirrored into the URL so a refresh keeps the view.
 */
export class StudentRecordingsPage {
    constructor() {
        this.container = document.getElementById('learning-content');
        this.filters = hydrateFilters({ search: '', course_id: '', page: 1 });
        this.meta = {};
        this.recordings = [];
        this.courses = [];
    }

    async render() {
        if (!this.container) return;

        this.paintShell();
        await this.fetch();
    }

    paintShell() {
        this.container.innerHTML = `
            <div class="space-y-6">
                <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                            <span class="material-symbols-outlined text-xl">smart_display</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-100">Class Recordings</h2>
                            <p class="text-xs text-slate-400">Catch up on any session you missed.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <div class="relative flex-1 sm:w-64">
                            <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-base" aria-hidden="true">search</span>
                            <label for="rec-search" class="sr-only">Search recordings</label>
                            <input type="search" id="rec-search" value="${this.filters.search}" placeholder="Search recordings…"
                                class="w-full bg-slate-900 border border-slate-800 text-slate-200 text-xs rounded-xl pl-9 pr-3 py-2.5 outline-none focus:border-indigo-500 transition-colors">
                        </div>
                        <label for="rec-course" class="sr-only">Filter by course</label>
                        <select id="rec-course" class="bg-slate-900 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 outline-none focus:border-indigo-500">
                            <option value="">All courses</option>
                        </select>
                    </div>
                </header>

                <div id="rec-list">${getCardsSkeletonHTML(6)}</div>
                <div id="rec-pagination"></div>
            </div>
        `;

        const search = this.container.querySelector('#rec-search');
        search.addEventListener('input', debounce((e) => {
            this.filters.search = e.target.value;
            this.filters.page = 1;
            this.fetch();
        }, 350));

        this.container.querySelector('#rec-course').addEventListener('change', (e) => {
            this.filters.course_id = e.target.value;
            this.filters.page = 1;
            this.fetch();
        });
    }

    async fetch() {
        const list = this.container.querySelector('#rec-list');
        if (list) list.innerHTML = getCardsSkeletonHTML(6);

        writeQuery(this.filters);

        try {
            const res = await recordingsApi.listForStudent(this.filters);
            if (!res) return; // superseded request

            this.recordings = res.data || [];
            this.meta = res.meta || {};
            this.paintList();
            this.syncCourseOptions();
        } catch (err) {
            if (list) {
                list.innerHTML = `
                    <div class="p-8 text-center bg-rose-950/20 border border-rose-500/30 rounded-2xl">
                        <p class="text-xs text-rose-300 mb-4">Unable to load recordings. ${err.message}</p>
                        <button id="rec-retry" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700">
                            Try again
                        </button>
                    </div>
                `;
                list.querySelector('#rec-retry')?.addEventListener('click', () => this.fetch());
            }
        }
    }

    paintList() {
        const list = this.container.querySelector('#rec-list');
        const pager = this.container.querySelector('#rec-pagination');

        if (this.recordings.length === 0) {
            list.innerHTML = getEmptyStateHTML({
                icon: 'video_library',
                title: this.filters.search || this.filters.course_id ? 'No recordings match your filters' : 'No recordings yet',
                description: this.filters.search || this.filters.course_id
                    ? 'Try clearing the search or choosing a different course.'
                    : 'Recordings appear here once your instructor publishes a completed class.',
            });
            pager.innerHTML = '';
            return;
        }

        list.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                ${this.recordings.map((rec) => this.card(rec)).join('')}
            </div>
        `;

        pager.innerHTML = renderPagination(this.meta);
        bindPagination(pager, (page) => {
            this.filters.page = page;
            this.fetch();
            this.container.scrollTo?.({ top: 0, behavior: 'smooth' });
        });
    }

    card(rec) {
        const cls = rec.online_class || {};
        const course = cls.course || {};

        return `
            <article class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden hover:border-indigo-500/40 transition-all group flex flex-col">
                <div class="relative aspect-video bg-gradient-to-br from-slate-800 to-slate-950 flex items-center justify-center">
                    <span class="material-symbols-outlined text-4xl text-slate-600 group-hover:text-indigo-400 transition-colors">play_circle</span>
                    ${rec.duration ? `
                        <span class="absolute bottom-2 right-2 px-1.5 py-0.5 rounded bg-slate-950/80 text-[10px] font-bold text-slate-200 tabular-nums">
                            ${rec.duration} min
                        </span>
                    ` : ''}
                </div>

                <div class="p-4 flex-1 flex flex-col justify-between gap-3">
                    <div class="space-y-1.5 min-w-0">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-400 block truncate">
                            ${course.title || 'Course'}
                        </span>
                        <h3 class="text-xs font-bold text-slate-100 line-clamp-2 leading-snug">${rec.title}</h3>
                        <p class="text-[11px] text-slate-400">
                            ${cls.class_date ? formatDate(cls.class_date) : ''} • ${rec.view_count || 0} views
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="${rec.recording_url}" target="_blank" rel="noopener noreferrer"
                           class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-bold transition-colors">
                            <span class="material-symbols-outlined text-sm">play_arrow</span> Watch
                        </a>
                        ${cls.id ? `
                            <a href="${path('classes', cls.id)}" data-spa-link
                               class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-semibold border border-slate-700 transition-colors">
                                Class
                            </a>
                        ` : ''}
                    </div>
                </div>
            </article>
        `;
    }

    /**
     * Course options are derived from what came back, so the filter never
     * offers a course the student has no recordings for.
     */
    syncCourseOptions() {
        const select = this.container.querySelector('#rec-course');
        if (!select) return;

        const seen = new Map();
        this.recordings.forEach((rec) => {
            const course = rec.online_class?.course;
            if (course && !seen.has(course.id)) seen.set(course.id, course.title);
        });

        // Keep any course already chosen even if this page has none of it.
        if (this.filters.course_id && !seen.has(Number(this.filters.course_id))) {
            this.courses.forEach(([id, title]) => seen.set(id, title));
        }
        this.courses = [...seen.entries()];

        select.innerHTML = `<option value="">All courses</option>` +
            this.courses.map(([id, title]) =>
                `<option value="${id}" ${String(this.filters.course_id) === String(id) ? 'selected' : ''}>${title}</option>`
            ).join('');
    }
}
