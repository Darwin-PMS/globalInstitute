import { skeletonResources, emptyState, errorState, escapeHtml, formatDateTime, panelHeading } from '../lib/ui.js';

/**
 * Materials, recordings and assignments.
 *
 * These three panels differ only in what they fetch and how a card reads, so
 * they share one implementation rather than being copied three times. Each is
 * still its own lazy-loaded module export.
 */
const KINDS = {
    materials: {
        icon: 'folder_open',
        title: 'Class materials',
        fetch: (api) => api.materials(),
        pick: (data) => data.materials || [],
        empty: {
            icon: 'folder_off',
            title: 'No materials shared',
            description: 'Files your instructor shares for this class will appear here.',
        },
        card: (item) => ({
            icon: fileIcon(item.file_type),
            title: item.title,
            subtitle: [item.file_type ? String(item.file_type).toUpperCase() : null,
                item.download_count ? `${item.download_count} downloads` : null]
                .filter(Boolean).join(' · '),
            description: item.description,
            href: item.url,
            actionLabel: 'Open',
            badge: item.is_published ? null : 'Draft',
        }),
    },

    recordings: {
        icon: 'smart_display',
        title: 'Recordings',
        fetch: (api) => api.recordings(),
        pick: (data) => data.recordings || [],
        empty: {
            icon: 'video_library',
            title: 'No recording yet',
            description: 'Once this class is recorded and published it will show up here.',
        },
        card: (item) => ({
            icon: 'play_circle',
            title: item.title,
            subtitle: [item.duration ? `${item.duration} min` : null,
                `${item.view_count || 0} views`].filter(Boolean).join(' · '),
            description: item.description,
            href: item.url,
            actionLabel: 'Watch',
            badge: item.is_published ? null : 'Draft',
        }),
    },

    assignments: {
        icon: 'assignment',
        title: 'Assignments',
        fetch: (api) => api.assignments(),
        pick: (data) => data.assignments || [],
        empty: {
            icon: 'assignment_turned_in',
            title: 'No assignment set',
            description: 'Work set from this class will be listed here.',
        },
        card: (item) => ({
            icon: 'assignment',
            title: item.title,
            subtitle: [item.due_date ? `Due ${formatDateTime(item.due_date)}` : 'No due date',
                `${item.max_marks ?? 100} marks`].join(' · '),
            description: item.description,
            href: null,
            actionLabel: null,
            badge: item.status === 'published' ? null : item.status,
        }),
    },
};

function fileIcon(type) {
    return {
        pdf: 'picture_as_pdf',
        ppt: 'slideshow',
        doc: 'description',
        xls: 'table_chart',
        zip: 'folder_zip',
        image: 'image',
        link: 'link',
    }[type] || 'attach_file';
}

/** Builds a panel class for one resource kind. */
export function makeResourcePanel(kind) {
    const spec = KINDS[kind];

    return class ResourcePanel {
        constructor(ctx) {
            this.api = ctx.api;
            this.mount = ctx.mount;
        }

        async render() {
            this.mount.innerHTML = skeletonResources(3);

            let res;
            try {
                res = await spec.fetch(this.api);
            } catch (err) {
                this.mount.innerHTML = errorState(err.message || `Unable to load ${kind}.`, `${kind}-retry`);
                this.mount.querySelector(`#${kind}-retry`)?.addEventListener('click', () => this.render());
                return;
            }
            if (!res) return; // superseded by a newer request

            const items = spec.pick(res.data || {});

            if (!items.length) {
                this.mount.innerHTML = panelHeading(spec.icon, spec.title) + emptyState(spec.empty);
                return;
            }

            this.mount.innerHTML =
                panelHeading(spec.icon, spec.title,
                    `<span class="text-[11px] text-slate-500">${items.length} item${items.length === 1 ? '' : 's'}</span>`) +
                `<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    ${items.map((item) => this.card(spec.card(item))).join('')}
                </div>`;
        }

        card(c) {
            return `
                <article class="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-col gap-3 hover:border-slate-700 transition-colors">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-xl text-indigo-400 flex-shrink-0" aria-hidden="true">${c.icon}</span>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-xs font-bold text-slate-100 leading-snug line-clamp-2">${escapeHtml(c.title)}</h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">${escapeHtml(c.subtitle)}</p>
                        </div>
                        ${c.badge ? `<span class="flex-shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700 capitalize">${escapeHtml(c.badge)}</span>` : ''}
                    </div>

                    ${c.description ? `<p class="text-[11px] text-slate-400 leading-relaxed line-clamp-3">${escapeHtml(c.description)}</p>` : ''}

                    ${c.href && c.actionLabel ? `
                        <a href="${escapeHtml(c.href)}" target="_blank" rel="noopener noreferrer"
                           class="mt-auto inline-flex items-center justify-center gap-1.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-bold transition-colors">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">open_in_new</span> ${escapeHtml(c.actionLabel)}
                        </a>
                    ` : ''}
                </article>
            `;
        }

        destroy() {}
    };
}

export const MaterialsPanel = makeResourcePanel('materials');
export const RecordingsPanel = makeResourcePanel('recordings');
export const AssignmentsPanel = makeResourcePanel('assignments');
