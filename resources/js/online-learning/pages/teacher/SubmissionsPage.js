import { assignmentsApi } from '../../api/assignments.js';
import { getTableSkeletonHTML } from '../../components/LoadingState.js';
import { getEmptyStateHTML } from '../../components/EmptyState.js';
import { renderPagination, bindPagination } from '../../components/Pagination.js';
import { Drawer } from '../../components/Drawer.js';
import { debounce } from '../../utils/debounce.js';
import { formatDate } from '../../utils/date.js';
import { hydrateFilters, writeQuery } from '../../utils/urlState.js';

const TABS = [
    { key: 'pending', label: 'Awaiting marks' },
    { key: '', label: 'All' },
    { key: 'reviewed', label: 'Reviewed' },
];

/**
 * Teacher grading queue: pick a submission, read it, award marks, leave feedback.
 */
export class TeacherSubmissionsPage {
    constructor() {
        this.container = document.getElementById('learning-content');
        this.filters = hydrateFilters({ status: 'pending', search: '', page: 1 });
        this.submissions = [];
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
                            <span class="material-symbols-outlined text-xl">assignment_turned_in</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-100">Submissions</h2>
                            <p class="text-xs text-slate-400">Grade student work for your assignments.</p>
                        </div>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-base" aria-hidden="true">search</span>
                        <label for="sub-search" class="sr-only">Search by student name</label>
                        <input type="search" id="sub-search" value="${this.filters.search}" placeholder="Search by student…"
                            class="w-full bg-slate-900 border border-slate-800 text-slate-200 text-xs rounded-xl pl-9 pr-3 py-2.5 outline-none focus:border-indigo-500">
                    </div>
                </header>

                <div id="sub-tabs" class="flex flex-wrap items-center gap-2" role="tablist" aria-label="Submission status"></div>
                <div id="sub-list">${getTableSkeletonHTML(5)}</div>
                <div id="sub-pagination"></div>
            </div>
        `;

        this.container.querySelector('#sub-search').addEventListener('input', debounce((e) => {
            this.filters.search = e.target.value;
            this.filters.page = 1;
            this.fetch();
        }, 350));

        this.paintTabs();
    }

    paintTabs() {
        const wrap = this.container.querySelector('#sub-tabs');
        if (!wrap) return;

        const counts = this.meta.counts || {};

        wrap.innerHTML = TABS.map((t) => {
            const active = (this.filters.status || '') === t.key;
            const count = t.key === '' ? counts.all : counts[t.key];

            return `
                <button type="button" role="tab" aria-selected="${active}" data-status="${t.key}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold border transition-colors ${active
                        ? 'bg-indigo-600 border-indigo-500 text-white'
                        : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-slate-200 hover:border-slate-700'}">
                    ${t.label}
                    ${count !== undefined ? `<span class="text-[10px] font-bold ${active ? 'text-indigo-200' : 'text-slate-500'} tabular-nums">${count}</span>` : ''}
                </button>
            `;
        }).join('');

        wrap.querySelectorAll('[data-status]').forEach((btn) => {
            btn.addEventListener('click', () => {
                this.filters.status = btn.dataset.status;
                this.filters.page = 1;
                this.fetch();
            });
        });
    }

    async fetch() {
        const list = this.container.querySelector('#sub-list');
        if (list) list.innerHTML = getTableSkeletonHTML(5);

        writeQuery(this.filters);

        try {
            const res = await assignmentsApi.listSubmissions(this.filters);
            if (!res) return;

            this.submissions = res.data || [];
            this.meta = res.meta || {};
            this.paintTabs();
            this.paintList();
        } catch (err) {
            if (list) {
                list.innerHTML = `
                    <div class="p-8 text-center bg-rose-950/20 border border-rose-500/30 rounded-2xl">
                        <p class="text-xs text-rose-300 mb-4">Unable to load submissions. ${err.message}</p>
                        <button id="sub-retry" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700">Try again</button>
                    </div>
                `;
                list.querySelector('#sub-retry')?.addEventListener('click', () => this.fetch());
            }
        }
    }

    paintList() {
        const list = this.container.querySelector('#sub-list');
        const pager = this.container.querySelector('#sub-pagination');

        if (this.submissions.length === 0) {
            list.innerHTML = getEmptyStateHTML({
                icon: 'inbox',
                title: this.filters.status === 'pending' ? 'Nothing to grade' : 'No submissions',
                description: this.filters.status === 'pending'
                    ? 'Every submission has been marked. Nice work.'
                    : 'No submissions match this view.',
            });
            pager.innerHTML = '';
            return;
        }

        list.innerHTML = `<div class="space-y-3">${this.submissions.map((s) => this.row(s)).join('')}</div>`;

        list.querySelectorAll('[data-grade]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.grade, 10);
                const submission = this.submissions.find((s) => s.id === id);
                if (submission) this.openGradeDrawer(submission);
            });
        });

        pager.innerHTML = renderPagination(this.meta);
        bindPagination(pager, (page) => {
            this.filters.page = page;
            this.fetch();
        });
    }

    row(submission) {
        const assignment = submission.assignment || {};
        const student = submission.student || {};
        const studentName = student.user?.name || `Student #${submission.student_id}`;
        const graded = submission.marks !== null && submission.marks !== undefined;
        const isLate = submission.status === 'late';

        return `
            <article class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-4 hover:border-slate-700 transition-colors">
                <div class="w-9 h-9 flex-shrink-0 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-indigo-400 font-bold text-xs uppercase">
                    ${studentName.charAt(0)}
                </div>

                <div class="flex-1 min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-xs font-bold text-slate-100">${studentName}</h3>
                        ${isLate ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">Late</span>` : ''}
                        ${graded ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">${submission.marks}/${assignment.max_marks || 100}</span>` : ''}
                    </div>
                    <p class="text-[11px] text-slate-300 truncate">${assignment.title || 'Assignment'}</p>
                    <p class="text-[11px] text-slate-500">
                        ${assignment.course?.title || ''} ${submission.submitted_at ? `• submitted ${formatDate(submission.submitted_at)}` : ''}
                    </p>
                </div>

                <button type="button" data-grade="${submission.id}"
                    class="flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold transition-all ${graded
                        ? 'bg-slate-800 text-slate-300 border border-slate-700 hover:bg-slate-700'
                        : 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/20'}">
                    ${graded ? 'Edit marks' : 'Grade'}
                </button>
            </article>
        `;
    }

    openGradeDrawer(submission) {
        const assignment = submission.assignment || {};
        const student = submission.student || {};
        const studentName = student.user?.name || `Student #${submission.student_id}`;
        const maxMarks = assignment.max_marks || 100;

        const drawer = new Drawer({
            title: `Grade — ${studentName}`,
            subtitle: assignment.title || 'Assignment',
            size: 'lg',
        });

        drawer.contentHTML = `
            <div class="space-y-5 text-xs">
                <section class="p-4 bg-slate-950/60 border border-slate-800 rounded-xl space-y-2">
                    <h4 class="font-bold text-slate-300">Submitted answer</h4>
                    ${submission.submission_text
                        ? `<p class="text-slate-300 leading-relaxed whitespace-pre-line">${submission.submission_text}</p>`
                        : `<p class="text-slate-500 italic">No written answer.</p>`}

                    ${submission.file_path ? `
                        <a href="/storage/${submission.file_path}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1.5 mt-2 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-semibold border border-slate-700 transition-colors">
                            <span class="material-symbols-outlined text-sm">download</span> Open attachment
                        </a>
                    ` : ''}
                </section>

                <form id="grade-form" class="space-y-4" novalidate>
                    <div class="space-y-1.5">
                        <label for="marks" class="font-semibold text-slate-300">Marks (out of ${maxMarks}) *</label>
                        <input type="number" id="marks" name="marks" min="0" max="${maxMarks}" required
                            value="${submission.marks ?? ''}"
                            class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500">
                        <p class="field-error hidden text-[11px] text-rose-400" data-for="marks"></p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="teacher_feedback" class="font-semibold text-slate-300">Feedback</label>
                        <textarea id="teacher_feedback" name="teacher_feedback" rows="5"
                            class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-indigo-500 resize-y"
                            placeholder="What did they do well, and what should they improve?">${submission.teacher_feedback || ''}</textarea>
                        <p class="field-error hidden text-[11px] text-rose-400" data-for="teacher_feedback"></p>
                    </div>

                    <div id="grade-error" class="hidden p-3 bg-rose-950/40 border border-rose-500/40 rounded-xl text-rose-200"></div>

                    <div class="pt-4 border-t border-slate-800">
                        <button type="submit" id="grade-submit"
                            class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all">
                            Save evaluation
                        </button>
                    </div>
                </form>
            </div>
        `;

        drawer.render();

        const form = drawer.element.querySelector('#grade-form');
        const marksInput = form.querySelector('#marks');
        const feedbackInput = form.querySelector('#teacher_feedback');
        const errorBox = form.querySelector('#grade-error');
        const submitBtn = form.querySelector('#grade-submit');

        const showFieldError = (field, message) => {
            const el = form.querySelector(`.field-error[data-for="${field}"]`);
            if (!el) return;
            el.textContent = message;
            el.classList.toggle('hidden', !message);
        };

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorBox.classList.add('hidden');
            showFieldError('marks', '');

            const marks = parseInt(marksInput.value, 10);
            if (Number.isNaN(marks) || marks < 0 || marks > maxMarks) {
                showFieldError('marks', `Enter a whole number between 0 and ${maxMarks}.`);
                marksInput.focus();
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving…';

            try {
                const res = await assignmentsApi.evaluate(submission.id, {
                    marks,
                    teacher_feedback: feedbackInput.value || null,
                });

                window.toast.success(res.message || 'Evaluation saved.');
                drawer.close();
                this.fetch();
            } catch (err) {
                Object.entries(err.errors || {}).forEach(([field, messages]) => {
                    showFieldError(field, Array.isArray(messages) ? messages[0] : messages);
                });

                if (Object.keys(err.errors || {}).length === 0) {
                    errorBox.textContent = err.message || 'Unable to save the evaluation.';
                    errorBox.classList.remove('hidden');
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save evaluation';
            }
        });
    }
}
