import { escapeHtml, formatDateTime, emptyState } from '../lib/ui.js';

/**
 * Resource Panels — Materials, Recordings, Assignments for the sidebar.
 *
 * Shared factory implementation. Each panel fetches its own data and
 * renders compact cards suitable for the sidebar width.
 */
const KINDS = {
    materials: {
        icon: 'folder_open',
        title: 'Resources',
        fetch: (api) => api.materials(),
        pick: (data) => data.materials || [],
        empty: {
            icon: 'folder_off',
            title: 'No resources shared',
            description: 'Files your instructor shares will appear here.',
        },
        card: (item) => ({
            icon: fileIcon(item.file_type),
            title: item.title,
            subtitle: [item.file_type ? String(item.file_type).toUpperCase() : null,
                item.download_count ? `${item.download_count} downloads` : null]
                .filter(Boolean).join(' · '),
            href: item.url,
            actionLabel: 'Open',
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
            description: 'Class recordings will appear here.',
        },
        card: (item) => ({
            icon: 'play_circle',
            title: item.title,
            subtitle: [item.duration ? `${item.duration} min` : null,
                `${item.view_count || 0} views`].filter(Boolean).join(' · '),
            href: item.url,
            actionLabel: 'Watch',
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
            href: null,
            actionLabel: null,
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
            let res;
            try {
                res = await spec.fetch(this.api);
            } catch (err) {
                this.mount.innerHTML = this.renderError(err.message || `Unable to load ${kind}.`);
                this.mount.querySelector(`#${kind}-retry`)?.addEventListener('click', () => this.render());
                return;
            }
            if (!res) return;

            const items = spec.pick(res.data || {});

            if (!items.length) {
                this.mount.innerHTML = `
                    <div class="cr-panel" style="height:100%;display:flex;flex-direction:column">
                        <div class="cr-panel-header">
                            <h2 class="cr-panel-title">
                                <span class="material-symbols-outlined" aria-hidden="true">${spec.icon}</span>
                                ${spec.title}
                            </h2>
                        </div>
                        <div class="cr-empty">
                            <div class="cr-empty-icon">
                                <span class="material-symbols-outlined">${spec.empty.icon}</span>
                            </div>
                            <p class="cr-empty-title">${spec.empty.title}</p>
                            <p class="cr-empty-desc">${spec.empty.description}</p>
                        </div>
                    </div>
                `;
                return;
            }

            this.mount.innerHTML = `
                <div class="cr-panel" style="height:100%;display:flex;flex-direction:column">
                    <div class="cr-panel-header">
                        <h2 class="cr-panel-title">
                            <span class="material-symbols-outlined" aria-hidden="true">${spec.icon}</span>
                            ${spec.title}
                        </h2>
                        <span class="cr-panel-count">${items.length}</span>
                    </div>

                    <div id="${kind}-list" class="flex-1 overflow-y-auto" style="margin:0 calc(var(--cr-space-3) * -1);padding:0 var(--cr-space-3)">
                        ${items.map((item) => this.card(spec.card(item))).join('')}
                    </div>
                </div>
            `;
        }

        card(c) {
            return `
                <div class="cr-resource-card">
                    <div class="cr-resource-icon">
                        <span class="material-symbols-outlined" aria-hidden="true">${c.icon}</span>
                    </div>
                    <div class="cr-resource-body">
                        <div class="cr-resource-title">${escapeHtml(c.title)}</div>
                        <div class="cr-resource-meta">${escapeHtml(c.subtitle)}</div>
                        ${c.href && c.actionLabel ? `
                            <a href="${escapeHtml(c.href)}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-1 text-[11px] text-indigo-400 font-semibold mt-1 hover:text-indigo-300 transition-colors">
                                <span class="material-symbols-outlined text-[13px]" aria-hidden="true">open_in_new</span>
                                ${escapeHtml(c.actionLabel)}
                            </a>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        renderError(message) {
            return `
                <div class="cr-panel" style="height:100%;display:flex;flex-direction:column">
                    <div class="cr-panel-header">
                        <h2 class="cr-panel-title">
                            <span class="material-symbols-outlined" aria-hidden="true">${spec.icon}</span>
                            ${spec.title}
                        </h2>
                    </div>
                    <div class="cr-error">
                        <div class="cr-error-icon">
                            <span class="material-symbols-outlined text-[22px] text-red-400">error</span>
                        </div>
                        <p class="text-xs text-red-200 mt-2 mb-4">${escapeHtml(message)}</p>
                        <button type="button" id="${kind}-retry" class="cr-modal-btn secondary">Try again</button>
                    </div>
                </div>
            `;
        }

        destroy() {}
    };
}

export const MaterialsPanel = makeResourcePanel('materials');
export const RecordingsPanel = makeResourcePanel('recordings');
export const AssignmentsPanel = makeResourcePanel('assignments');
