import { assignmentsApi } from '../../api/assignments.js';
import { getTableSkeletonHTML } from '../../components/LoadingState.js';
import { getEmptyStateHTML } from '../../components/EmptyState.js';
import { renderPagination, bindPagination } from '../../components/Pagination.js';
import { Drawer } from '../../components/Drawer.js';
import { debounce } from '../../utils/debounce.js';
import { formatDate } from '../../utils/date.js';
import { hydrateFilters, writeQuery } from '../../utils/urlState.js';

const FILTERS = [
    { key: '', label: 'All' },
    { key: 'pending', label: 'To do' },
    { key: 'submitted', label: 'Submitted' },
    { key: 'graded', label: 'Graded' },
    { key: 'overdue', label: 'Overdue' },
];

/**
 * Student assignments: filter chips, submission drawer with inline validation
 * and an unsaved-changes guard (§17, §18).
 */
export class StudentAssignmentsPage {
    constructor() {
        this.container = document.getElementById('learning-content');
        this.filters = hydrateFilters({ filter: '', search: '', page: 1 });
        this.assignments = [];
        this.meta = {};
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
                            <span class="material-symbols-outlined text-xl">assignment</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-100">Assignments</h2>
                            <p class="text-xs text-slate-400">Submit your work and track your marks.</p>
                        </div>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-base" aria-hidden="true">search</span>
                        <label for="asg-search" class="sr-only">Search assignments</label>
                        <input type="search" id="asg-search" value="${this.filters.search}" placeholder="Search assignments…"
                            class="w-full bg-slate-900 border border-slate-800 text-slate-200 text-xs rounded-xl pl-9 pr-3 py-2.5 outline-none focus:border-indigo-500 transition-colors">
                    </div>
                </header>

                <div id="asg-chips" class="flex flex-wrap items-center gap-2" role="tablist" aria-label="Assignment status"></div>

                <div id="asg-list">${getTableSkeletonHTML(5)}</div>
                <div id="asg-pagination"></div>
            </div>
        `;

        this.container.querySelector('#asg-search').addEventListener('input', debounce((e) => {
            this.filters.search = e.target.value;
            this.filters.page = 1;
            this.fetch();
        }, 350));

        this.paintChips();
    }

    paintChips() {
        const wrap = this.container.querySelector('#asg-chips');
        if (!wrap) return;

        const counts = this.meta.counts || {};

        wrap.innerHTML = FILTERS.map((f) => {
            const active = (this.filters.filter || '') === f.key;
            const count = f.key === '' ? counts.all : counts[f.key];

            return `
                <button type="button" role="tab" aria-selected="${active}" data-filter="${f.key}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold border transition-colors ${active
                        ? 'bg-indigo-600 border-indigo-500 text-white'
                        : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-slate-200 hover:border-slate-700'}">
                    ${f.label}
                    ${count !== undefined ? `<span class="text-[10px] font-bold ${active ? 'text-indigo-200' : 'text-slate-500'} tabular-nums">${count}</span>` : ''}
                </button>
            `;
        }).join('');

        wrap.querySelectorAll('[data-filter]').forEach((btn) => {
            btn.addEventListener('click', () => {
                this.filters.filter = btn.dataset.filter;
                this.filters.page = 1;
                this.fetch();
            });
        });
    }

    async fetch() {
        const list = this.container.querySelector('#asg-list');
        if (list) list.innerHTML = getTableSkeletonHTML(5);

        writeQuery(this.filters);

        try {
            const res = await assignmentsApi.listForStudent(this.filters);
            if (!res) return;

            this.assignments = res.data || [];
            this.meta = res.meta || {};
            this.paintChips();
            this.paintList();
        } catch (err) {
            if (list) {
                list.innerHTML = `
                    <div class="p-8 text-center bg-rose-950/20 border border-rose-500/30 rounded-2xl">
                        <p class="text-xs text-rose-300 mb-4">Unable to load assignments. ${err.message}</p>
                        <button id="asg-retry" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700">Try again</button>
                    </div>
                `;
                list.querySelector('#asg-retry')?.addEventListener('click', () => this.fetch());
            }
        }
    }

    paintList() {
        const list = this.container.querySelector('#asg-list');
        const pager = this.container.querySelector('#asg-pagination');

        if (this.assignments.length === 0) {
            list.innerHTML = getEmptyStateHTML({
                icon: 'assignment_turned_in',
                title: 'Nothing here',
                description: this.filters.filter === 'pending'
                    ? 'You are all caught up — no assignments waiting on you.'
                    : 'No assignments match this view.',
            });
            pager.innerHTML = '';
            return;
        }

        list.innerHTML = `
            <div class="space-y-3">
                ${this.assignments.map((a) => this.row(a)).join('')}
            </div>
        `;

        list.querySelectorAll('[data-open-assignment]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.openAssignment, 10);
                const assignment = this.assignments.find((a) => a.id === id);
                if (assignment) this.openSubmitDrawer(assignment);
            });
        });

        pager.innerHTML = renderPagination(this.meta);
        bindPagination(pager, (page) => {
            this.filters.page = page;
            this.fetch();
        });
    }

    /**
     * A submission's state is read off the eager-loaded `submissions` array,
     * which the API already scopes to the acting student.
     */
    stateOf(assignment) {
        const submission = (assignment.submissions || [])[0] || null;
        const overdue = assignment.due_date && new Date(assignment.due_date) < new Date();

        if (submission && submission.marks !== null && submission.marks !== undefined) {
            return { key: 'graded', label: `Graded ${submission.marks}/${assignment.max_marks || 100}`, tone: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20', submission };
        }
        if (submission && submission.status === 'late') {
            return { key: 'late', label: 'Submitted late', tone: 'text-amber-400 bg-amber-500/10 border-amber-500/20', submission };
        }
        if (submission) {
            return { key: 'submitted', label: 'Submitted', tone: 'text-indigo-400 bg-indigo-500/10 border-indigo-500/20', submission };
        }
        if (overdue) {
            return { key: 'overdue', label: 'Overdue', tone: 'text-rose-400 bg-rose-500/10 border-rose-500/20', submission: null };
        }
        return { key: 'pending', label: 'To do', tone: 'text-slate-300 bg-slate-800 border-slate-700', submission: null };
    }

    row(assignment) {
        const state = this.stateOf(assignment);
        const canSubmit = state.key !== 'graded';

        return `
            <article class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-4 hover:border-slate-700 transition-colors">
                <div class="flex-1 min-w-0 space-y-1.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-400">
                            ${assignment.course?.title || 'Course'}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border ${state.tone}">
                            ${state.label}
                        </span>
                    </div>

                    <h3 class="text-sm font-bold text-slate-100 truncate">${assignment.title}</h3>

                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-400">
                        <span class="inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">event</span>
                            ${assignment.due_date ? `Due ${formatDate(assignment.due_date)}` : 'No due date'}
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">grade</span>
                            ${assignment.max_marks || 100} marks
                        </span>
                        ${assignment.teacher?.name ? `
                            <span class="inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">person</span> ${assignment.teacher.name}
                            </span>
                        ` : ''}
                    </div>
                </div>

                <button type="button" data-open-assignment="${assignment.id}"
                    class="flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold transition-all ${canSubmit
                        ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/20'
                        : 'bg-slate-800 text-slate-300 border border-slate-700 hover:bg-slate-700'}">
                    ${canSubmit ? (state.submission ? 'Update' : 'Submit') : 'View feedback'}
                </button>
            </article>
        `;
    }

    openSubmitDrawer(assignment) {
        const state = this.stateOf(assignment);
        const submission = state.submission;
        const isGraded = state.key === 'graded';

        const drawer = new Drawer({
            title: assignment.title,
            subtitle: `${assignment.course?.title || 'Course'} • ${assignment.max_marks || 100} marks`,
            size: 'lg',
        });

        drawer.contentHTML = `
            <div class="space-y-5 text-xs">
                ${assignment.description ? `
                    <section>
                        <h4 class="font-bold text-slate-300 mb-1.5">Description</h4>
                        <p class="text-slate-400 leading-relaxed">${assignment.description}</p>
                    </section>
                ` : ''}

                ${assignment.instructions ? `
                    <section class="p-4 bg-slate-950/60 border border-slate-800 rounded-xl">
                        <h4 class="font-bold text-slate-300 mb-1.5">Instructions</h4>
                        <p class="text-slate-400 leading-relaxed whitespace-pre-line">${assignment.instructions}</p>
                    </section>
                ` : ''}

                ${isGraded ? `
                    <section class="p-4 bg-emerald-950/30 border border-emerald-500/30 rounded-xl space-y-2">
                        <div class="flex items-center justify-between">
                            <h4 class="font-bold text-emerald-300">Result</h4>
                            <span class="text-lg font-black text-emerald-400 tabular-nums">
                                ${submission.marks}/${assignment.max_marks || 100}
                            </span>
                        </div>
                        ${submission.teacher_feedback
                            ? `<p class="text-slate-300 leading-relaxed"><span class="font-semibold text-slate-400">Feedback:</span> ${submission.teacher_feedback}</p>`
                            : `<p class="text-slate-400">No written feedback was left.</p>`}
                    </section>
                ` : `
                    <form id="submit-form" class="space-y-4" novalidate>
                        ${submission ? `
                            <div class="p-3 bg-indigo-950/30 border border-indigo-500/30 rounded-xl text-indigo-200">
                                You already submitted this on ${formatDate(submission.submitted_at)}. Submitting again replaces it.
                            </div>
                        ` : ''}

                        <div class="space-y-1.5">
                            <label for="submission_text" class="font-semibold text-slate-300">Your answer</label>
                            <textarea id="submission_text" name="submission_text" rows="7"
                                class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500 resize-y"
                                placeholder="Type your answer, or attach a file below.">${submission?.submission_text || ''}</textarea>
                            <p class="field-error hidden text-[11px] text-rose-400" data-for="submission_text"></p>
                        </div>

                        <div class="space-y-1.5">
                            <label for="file" class="font-semibold text-slate-300">Attachment (optional)</label>
                            <input type="file" id="file" name="file"
                                class="w-full bg-slate-950 border border-slate-800 text-slate-300 text-xs rounded-xl px-3 py-2.5 outline-none focus:border-indigo-500 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-slate-800 file:text-slate-200 file:text-xs file:font-semibold">
                            <p class="text-[11px] text-slate-500">PDF, Office documents, images or archives. Up to 20 MB.</p>
                            <p class="field-error hidden text-[11px] text-rose-400" data-for="file"></p>
                            ${submission?.file_path ? `
                                <a href="/storage/${submission.file_path}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-400 hover:text-indigo-300">
                                    <span class="material-symbols-outlined text-sm">attach_file</span> Current attachment
                                </a>
                            ` : ''}
                        </div>

                        <div id="submit-error" class="hidden p-3 bg-rose-950/40 border border-rose-500/40 rounded-xl text-rose-200"></div>

                        <div class="pt-4 border-t border-slate-800">
                            <button type="submit" id="submit-btn"
                                class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all">
                                ${submission ? 'Replace submission' : 'Submit assignment'}
                            </button>
                        </div>
                    </form>
                `}
            </div>
        `;

        drawer.render();

        if (isGraded) return;

        const form = drawer.element.querySelector('#submit-form');
        const errorBox = drawer.element.querySelector('#submit-error');
        const textarea = form.querySelector('#submission_text');
        const fileInput = form.querySelector('#file');
        const submitBtn = form.querySelector('#submit-btn');

        // Unsaved-changes guard (§18): only nag once the student typed something new.
        const initialText = textarea.value;
        const isDirty = () => textarea.value !== initialText || fileInput.files.length > 0;

        const originalClose = drawer.close.bind(drawer);
        drawer.close = () => {
            if (isDirty() && !window.confirm('Unsaved changes will be lost. Leave this form?')) return;
            originalClose();
        };

        const beforeUnload = (e) => {
            if (isDirty()) {
                e.preventDefault();
                e.returnValue = '';
            }
        };
        window.addEventListener('beforeunload', beforeUnload);
        drawer.onClose = () => window.removeEventListener('beforeunload', beforeUnload);

        const showFieldError = (field, message) => {
            const el = form.querySelector(`.field-error[data-for="${field}"]`);
            if (!el) return;
            el.textContent = message;
            el.classList.toggle('hidden', !message);
        };

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorBox.classList.add('hidden');
            showFieldError('submission_text', '');
            showFieldError('file', '');

            // Client-side check is for feel only; the server validates again.
            if (!textarea.value.trim() && fileInput.files.length === 0) {
                showFieldError('submission_text', 'Write an answer or attach a file.');
                textarea.focus();
                return;
            }

            const data = new FormData();
            data.append('submission_text', textarea.value);
            if (fileInput.files[0]) data.append('file', fileInput.files[0]);

            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting…';

            try {
                const res = await assignmentsApi.submit(assignment.id, data);
                window.toast.success(res.message || 'Assignment submitted.');

                // Clean exit: bypass the dirty guard now that it is saved.
                window.removeEventListener('beforeunload', beforeUnload);
                drawer.close = originalClose;
                drawer.close();

                this.fetch();
            } catch (err) {
                // Surface Laravel field errors inline rather than in an alert (§17).
                const fieldErrors = err.errors || {};
                Object.entries(fieldErrors).forEach(([field, messages]) => {
                    showFieldError(field, Array.isArray(messages) ? messages[0] : messages);
                });

                if (Object.keys(fieldErrors).length === 0) {
                    errorBox.textContent = err.message || 'Submission failed.';
                    errorBox.classList.remove('hidden');
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = submission ? 'Replace submission' : 'Submit assignment';
            }
        });
    }
}
