import { Sidebar } from './Sidebar.js';
import { role, isAdmin } from '../utils/permissions.js';

/**
 * Topbar: page identity, breadcrumb, mobile nav toggle and the exit link.
 *
 * Re-rendered on every route change, so all listeners are attached fresh here
 * rather than delegated from the document.
 */
export class Topbar {
    constructor() {
        this.container = document.getElementById('learning-topbar');
    }

    render(title = 'Online Learning', breadcrumbs = []) {
        if (!this.container) return;

        this.container.innerHTML = `
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" id="topbar-menu" aria-label="Open navigation" aria-controls="learning-sidebar"
                    class="lg:hidden p-2 -ml-1 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 transition-colors flex-shrink-0">
                    <span class="material-symbols-outlined text-xl" aria-hidden="true">menu</span>
                </button>

                <div class="min-w-0">
                    <h1 class="text-sm sm:text-base font-bold text-slate-100 leading-tight truncate">${title}</h1>

                    ${breadcrumbs.length > 0 ? `
                        <nav aria-label="Breadcrumb" class="hidden sm:flex items-center gap-1.5 text-[11px] text-slate-400 mt-0.5">
                            ${breadcrumbs.map((b, i) => `
                                ${i > 0 ? '<span class="text-slate-600" aria-hidden="true">/</span>' : ''}
                                ${b.href
                                    ? `<a href="${b.href}" data-spa-link class="hover:text-indigo-400 transition-colors">${b.label}</a>`
                                    : `<span class="text-slate-300 font-medium" aria-current="page">${b.label}</span>`}
                            `).join('')}
                        </nav>
                    ` : ''}
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                <span class="hidden xl:flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-800/80 border border-slate-700/60 text-[11px] text-slate-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-500" aria-hidden="true"></span>
                    <span class="font-medium capitalize">${role()} session</span>
                </span>

                <a href="${isAdmin() ? '/admin' : '/'}" data-spa="false"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium border border-slate-700 transition-colors">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">logout</span>
                    <span class="hidden md:inline">Exit LMS</span>
                </a>
            </div>
        `;

        this.container.querySelector('#topbar-menu')?.addEventListener('click', () => Sidebar.toggle());
    }
}
