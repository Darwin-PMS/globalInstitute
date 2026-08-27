import { materialsApi } from '../../api/materials.js';
import { classesApi } from '../../api/classes.js';
import { getTableSkeletonHTML } from '../../components/LoadingState.js';
import { getEmptyStateHTML } from '../../components/EmptyState.js';
import { renderPagination, bindPagination } from '../../components/Pagination.js';
import { confirmAction } from '../../components/ConfirmDialog.js';
import { debounce } from '../../utils/debounce.js';
import { hydrateFilters, writeQuery } from '../../utils/urlState.js';

const FILE_ICONS = {
    pdf: 'picture_as_pdf',
    ppt: 'slideshow',
    doc: 'description',
    xls: 'table_chart',
    zip: 'folder_zip',
    image: 'image',
    link: 'link',
};

/**
 * Admin materials library: search, filter by type, and control student access.
 */
export class AdminMaterialsPage {
    constructor() {
        this.container = document.getElementById('learning-content');
        this.filters = hydrateFilters({ search: '', course_id: '', file_type: '', published: '', page: 1 });
        this.materials = [];
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
                        <span class="material-symbols-outlined text-xl">folder_open</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-100">Materials Library</h2>
                        <p class="text-xs text-slate-400">Course files, slides and links shared with students.</p>
                    </div>
                </header>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 bg-slate-900/60 p-4 rounded-xl border border-slate-800">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-base" aria-hidden="true">search</span>
                        <label for="ml-search" class="sr-only">Search materials</label>
                        <input type="search" id="ml-search" value="${this.filters.search}" placeholder="Search title…"
                            class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl pl-9 pr-3 py-2 outline-none focus:border-indigo-500">
                    </div>

                    <label for="ml-course" class="sr-only">Filter by course</label>
                    <select id="ml-course" class="bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2 outline-none focus:border-indigo-500">
                        <option value="">All courses</option>
                    </select>

                    <label for="ml-type" class="sr-only">Filter by file type</label>
                    <select id="ml-type" class="bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2 outline-none focus:border-indigo-500">
                        <option value="">All types</option>
                        ${['pdf', 'ppt', 'doc', 'xls', 'zip', 'image', 'link', 'other'].map((t) => `
                            <option value="${t}" ${this.filters.file_type === t ? 'selected' : ''}>${t.toUpperCase()}</option>
                        `).join('')}
                    </select>

                    <label for="ml-published" class="sr-only">Filter by publish state</label>
                    <select id="ml-published" class="bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2 outline-none focus:border-indigo-500">
                        <option value="" ${this.filters.published === '' ? 'selected' : ''}>All states</option>
                        <option value="yes" ${this.filters.published === 'yes' ? 'selected' : ''}>Published</option>
                        <option value="no" ${this.filters.published === 'no' ? 'selected' : ''}>Unpublished</option>
                    </select>
                </div>

                <div id="ml-list">${getTableSkeletonHTML(6)}</div>
                <div id="ml-pagination"></div>
            </div>
        `;

        this.container.querySelector('#ml-search').addEventListener('input', debounce((e) => {
            this.filters.search = e.target.value;
            this.filters.page = 1;
            this.fetch();
        }, 350));

        [['#ml-course', 'course_id'], ['#ml-type', 'file_type'], ['#ml-published', 'published']]
            .forEach(([selector, key]) => {
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
            const select = this.container.querySelector('#ml-course');
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
        const list = this.container.querySelector('#ml-list');
        if (list) list.innerHTML = getTableSkeletonHTML(6);

        writeQuery(this.filters);

        try {
            const res = await materialsApi.listForAdmin(this.filters);
            if (!res) return;

            this.materials = res.data || [];
            this.meta = res.meta || {};
            this.paintList();
        } catch (err) {
            if (list) {
                list.innerHTML = `
                    <div class="p-8 text-center bg-rose-950/20 border border-rose-500/30 rounded-2xl">
                        <p class="text-xs text-rose-300 mb-4">Unable to load materials. ${err.message}</p>
                        <button id="ml-retry" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700">Try again</button>
                    </div>
                `;
                list.querySelector('#ml-retry')?.addEventListener('click', () => this.fetch());
            }
        }
    }

    paintList() {
        const list = this.container.querySelector('#ml-list');
        const pager = this.container.querySelector('#ml-pagination');

        if (this.materials.length === 0) {
            list.innerHTML = getEmptyStateHTML({
                icon: 'folder_off',
                title: 'No materials found',
                description: 'Nothing matches these filters. Materials uploaded against a course appear here.',
            });
            pager.innerHTML = '';
            return;
        }

        list.innerHTML = `<div class="space-y-3">${this.materials.map((m) => this.row(m)).join('')}</div>`;

        list.querySelectorAll('[data-toggle]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.toggle, 10);
                const material = this.materials.find((m) => m.id === id);
                if (material) this.handleToggle(material, btn);
            });
        });

        pager.innerHTML = renderPagination(this.meta);
        bindPagination(pager, (page) => {
            this.filters.page = page;
            this.fetch();
        });
    }

    row(material) {
        const published = !!material.is_published;
        const href = material.external_url || (material.file_path ? `/storage/${material.file_path}` : null);

        return `
            <article class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-4 hover:border-slate-700 transition-colors">
                <div class="w-11 h-11 flex-shrink-0 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-indigo-400">
                    <span class="material-symbols-outlined text-xl" aria-hidden="true">${FILE_ICONS[material.file_type] || 'attach_file'}</span>
                </div>

                <div class="flex-1 min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-xs font-bold text-slate-100 truncate">${material.title}</h3>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border ${published
                            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
                            : 'bg-slate-800 text-slate-400 border-slate-700'}">
                            <span class="w-1.5 h-1.5 rounded-full ${published ? 'bg-emerald-400' : 'bg-slate-500'}"></span>
                            ${published ? 'Published' : 'Unpublished'}
                        </span>
                    </div>

                    <p class="text-[11px] text-slate-400 truncate">${material.course?.title || 'Course'}</p>
                    <p class="text-[11px] text-slate-500 uppercase">
                        ${material.file_type || 'file'} • ${material.download_count || 0} downloads • ${material.visibility || 'enrolled'}
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    ${href ? `
                        <a href="${href}" target="_blank" rel="noopener noreferrer"
                           class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-semibold border border-slate-700 transition-colors">
                            Open
                        </a>
                    ` : ''}

                    <button type="button" data-toggle="${material.id}"
                        class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition-colors ${published
                            ? 'bg-slate-800 hover:bg-slate-700 text-amber-300 border border-slate-700'
                            : 'bg-indigo-600 hover:bg-indigo-500 text-white'}">
                        ${published ? 'Unpublish' : 'Publish'}
                    </button>
                </div>
            </article>
        `;
    }

    async handleToggle(material, btn) {
        if (material.is_published) {
            const confirmed = await confirmAction({
                title: 'Unpublish this material?',
                message: `Students will lose access to "${material.title}" until it is published again.`,
                confirmText: 'Unpublish',
                isDangerous: true,
            });
            if (!confirmed) return;
        }

        btn.disabled = true;
        const original = btn.textContent;
        btn.textContent = '…';

        try {
            const res = await materialsApi.togglePublish(material.id);
            window.toast.success(res.message || 'Material updated.');
            this.fetch();
        } catch (err) {
            window.toast.error(err.message || 'Unable to update the material.');
            btn.disabled = false;
            btn.textContent = original;
        }
    }
}
