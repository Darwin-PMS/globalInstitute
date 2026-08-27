import { recordingsApi } from '../../api/recordings.js';
import { classesApi } from '../../api/classes.js';
import { getTableSkeletonHTML } from '../../components/LoadingState.js';
import { getEmptyStateHTML } from '../../components/EmptyState.js';
import { renderPagination, bindPagination } from '../../components/Pagination.js';
import { confirmAction } from '../../components/ConfirmDialog.js';
import { debounce } from '../../utils/debounce.js';
import { formatDate } from '../../utils/date.js';
import { hydrateFilters, writeQuery } from '../../utils/urlState.js';
import { path } from '../../utils/permissions.js';

/**
 * Admin recordings library: find a recording, see whether students can watch
 * it, and publish or unpublish without a page reload.
 */
export class AdminRecordingsPage {
    constructor() {
        this.container = document.getElementById('learning-content');
        this.filters = hydrateFilters({ search: '', course_id: '', published: '', page: 1 });
        this.recordings = [];
        this.meta = {};
    }

    async render() {
        if (!this.container) return;

        this.paintShell();
        await Promise.all([this.loadCourses(), this.fetch()]);
    }

    paintShell() {
        this.container.innerHTML = `
            <div class="space-y-6">
                <header class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                        <span class="material-symbols-outlined text-xl">smart_display</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-100">Recordings Library</h2>
                        <p class="text-xs text-slate-400">Control which session recordings students can watch.</p>
                    </div>
                </header>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-slate-900/60 p-4 rounded-xl border border-slate-800">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-base" aria-hidden="true">search</span>
                        <label for="rl-search" class="sr-only">Search recordings</label>
                        <input type="search" id="rl-search" value="${this.filters.search}" placeholder="Search recording title…"
                            class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl pl-9 pr-3 py-2 outline-none focus:border-indigo-500">
                    </div>

                    <label for="rl-course" class="sr-only">Filter by course</label>
                    <select id="rl-course" class="bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2 outline-none focus:border-indigo-500">
                        <option value="">All courses</option>
                    </select>

                    <label for="rl-published" class="sr-only">Filter by publish state</label>
                    <select id="rl-published" class="bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2 outline-none focus:border-indigo-500">
                        <option value="" ${this.filters.published === '' ? 'selected' : ''}>All states</option>
                        <option value="yes" ${this.filters.published === 'yes' ? 'selected' : ''}>Published</option>
                        <option value="no" ${this.filters.published === 'no' ? 'selected' : ''}>Unpublished</option>
                    </select>
                </div>

                <div id="rl-list">${getTableSkeletonHTML(6)}</div>
                <div id="rl-pagination"></div>
            </div>
        `;

        this.container.querySelector('#rl-search').addEventListener('input', debounce((e) => {
            this.filters.search = e.target.value;
            this.filters.page = 1;
            this.fetch();
        }, 350));

        [['#rl-course', 'course_id'], ['#rl-published', 'published']].forEach(([selector, key]) => {
            this.container.querySelector(selector).addEventListener('change', (e) => {
                this.filters[key] = e.target.value;
                this.filters.page = 1;
                this.fetch();
            });
        });
    }

    async loadCourses() {
        try {
            const res = await classesApi.getFormData();
            const select = this.container.querySelector('#rl-course');
            if (select && res?.courses) {
                select.innerHTML = `<option value="">All courses</option>` +
                    res.courses.map((c) =>
                        `<option value="${c.id}" ${String(this.filters.course_id) === String(c.id) ? 'selected' : ''}>${c.title}</option>`
                    ).join('');
            }
        } catch {
            // Filter stays as "All courses".
        }
    }

    async fetch() {
        const list = this.container.querySelector('#rl-list');
        if (list) list.innerHTML = getTableSkeletonHTML(6);

        writeQuery(this.filters);

        try {
            const res = await recordingsApi.listForAdmin(this.filters);
            if (!res) return;

            this.recordings = res.data || [];
            this.meta = res.meta || {};
            this.paintList();
        } catch (err) {
            if (list) {
                list.innerHTML = `
                    <div class="p-8 text-center bg-rose-950/20 border border-rose-500/30 rounded-2xl">
                        <p class="text-xs text-rose-300 mb-4">Unable to load recordings. ${err.message}</p>
                        <button id="rl-retry" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700">Try again</button>
                    </div>
                `;
                list.querySelector('#rl-retry')?.addEventListener('click', () => this.fetch());
            }
        }
    }

    paintList() {
        const list = this.container.querySelector('#rl-list');
        const pager = this.container.querySelector('#rl-pagination');

        if (this.recordings.length === 0) {
            list.innerHTML = getEmptyStateHTML({
                icon: 'video_library',
                title: 'No recordings found',
                description: 'Recordings appear here once they are attached to a completed class.',
            });
            pager.innerHTML = '';
            return;
        }

        list.innerHTML = `<div class="space-y-3">${this.recordings.map((r) => this.row(r)).join('')}</div>`;

        list.querySelectorAll('[data-toggle]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.toggle, 10);
                const recording = this.recordings.find((r) => r.id === id);
                if (recording) this.handleToggle(recording, btn);
            });
        });

        pager.innerHTML = renderPagination(this.meta);
        bindPagination(pager, (page) => {
            this.filters.page = page;
            this.fetch();
        });
    }

    row(recording) {
        const cls = recording.online_class || {};
        const published = !!recording.is_published;

        return `
            <article class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center gap-4 hover:border-slate-700 transition-colors">
                <div class="w-11 h-11 flex-shrink-0 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-indigo-400">
                    <span class="material-symbols-outlined text-xl" aria-hidden="true">play_circle</span>
                </div>

                <div class="flex-1 min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-xs font-bold text-slate-100 truncate">${recording.title}</h3>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border ${published
                            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
                            : 'bg-slate-800 text-slate-400 border-slate-700'}">
                            <span class="w-1.5 h-1.5 rounded-full ${published ? 'bg-emerald-400' : 'bg-slate-500'}"></span>
                            ${published ? 'Published' : 'Unpublished'}
                        </span>
                    </div>

                    <p class="text-[11px] text-slate-400 truncate">
                        ${cls.course?.title || 'Course'}
                        ${cls.title ? ` • ${cls.title}` : ''}
                        ${cls.class_date ? ` • ${formatDate(cls.class_date)}` : ''}
                    </p>

                    <p class="text-[11px] text-slate-500">
                        ${recording.duration ? `${recording.duration} min • ` : ''}${recording.view_count || 0} views • ${recording.visibility || 'enrolled'}
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="${recording.recording_url}" target="_blank" rel="noopener noreferrer"
                       class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-semibold border border-slate-700 transition-colors">
                        Preview
                    </a>

                    ${cls.id ? `
                        <a href="${path('classes', cls.id)}" data-spa-link
                           class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-semibold border border-slate-700 transition-colors">
                            Class
                        </a>
                    ` : ''}

                    <button type="button" data-toggle="${recording.id}"
                        class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition-colors ${published
                            ? 'bg-slate-800 hover:bg-slate-700 text-amber-300 border border-slate-700'
                            : 'bg-indigo-600 hover:bg-indigo-500 text-white'}">
                        ${published ? 'Unpublish' : 'Publish'}
                    </button>
                </div>
            </article>
        `;
    }

    async handleToggle(recording, btn) {
        // Unpublishing takes something away from students, so confirm it.
        if (recording.is_published) {
            const confirmed = await confirmAction({
                title: 'Unpublish this recording?',
                message: `Students will lose access to "${recording.title}" until it is published again.`,
                confirmText: 'Unpublish',
                isDangerous: true,
            });
            if (!confirmed) return;
        }

        btn.disabled = true;
        const original = btn.textContent;
        btn.textContent = '…';

        try {
            const res = await recordingsApi.togglePublish(recording.id);
            window.toast.success(res.message || 'Recording updated.');
            this.fetch();
        } catch (err) {
            window.toast.error(err.message || 'Unable to update the recording.');
            btn.disabled = false;
            btn.textContent = original;
        }
    }
}
