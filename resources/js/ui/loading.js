/**
 * Loading & skeleton utilities.
 *
 * One implementation shared by the Blade admin, the admin SPA, the Online
 * Learning SPA and the Live Class Room. Markup uses the design-system classes in
 * resources/css/admin.css, so nothing here carries its own styling.
 *
 * Usage as a module:   import { setButtonLoading } from '.../ui/loading.js'
 * Usage from Blade:    window.Loading.setButtonLoading(btn, true)
 */

/* ------------------------------------------------------------------ helpers */

function resolve(target) {
    if (!target) return null;
    if (typeof target === 'string') return document.querySelector(target);
    return target;
}

/** Skeletons must never be announced or reachable while they stand in for content. */
function markDecorative(html) {
    return `<div class="skeleton-shell" aria-hidden="true">${html}</div>`;
}

function repeat(count, fn) {
    return Array.from({ length: Math.max(0, count) }, (_, i) => fn(i)).join('');
}

/** Varied widths read as text rather than as identical grey bars. */
function lineWidth(i) {
    return ['skeleton-w-100', 'skeleton-w-75', 'skeleton-w-66', 'skeleton-w-50'][i % 4];
}

/* --------------------------------------------------------------- skeletons */

/**
 * Skeleton templates, keyed by the component they stand in for.
 *
 * Each mirrors the real layout's box model so swapping in the loaded content
 * does not shift the page.
 */
export const skeletons = {
    /** Lines of text. */
    text: ({ lines = 3 } = {}) =>
        `<div class="skeleton-group">${repeat(lines, (i) =>
            `<div class="skeleton skeleton-line ${lineWidth(i)}"></div>`)}</div>`,

    /** The stat tiles above most index pages. */
    stats: ({ count = 4 } = {}) =>
        `<div class="skeleton-stats">${repeat(count, () => `
            <div class="skeleton-card skeleton-card-sm">
                <div class="skeleton-row">
                    <div class="skeleton skeleton-thumb"></div>
                    <div class="skeleton-stack">
                        <div class="skeleton skeleton-title skeleton-w-50"></div>
                        <div class="skeleton skeleton-text skeleton-w-75" style="margin-top:8px"></div>
                    </div>
                </div>
            </div>`)}</div>`,

    /** A data table, including its header row. */
    table: ({ rows = 6, cols = 5, header = true } = {}) => `
        <div class="card">
            <div class="card-body-flush">
                ${header ? `
                    <div class="skeleton-table-row" style="background:var(--gray-50)">
                        ${repeat(cols, (i) => `<div class="skeleton skeleton-text ${i === 0 ? 'skeleton-w-15' : 'skeleton-w-50'}" style="flex:${i === 1 ? 2 : 1}"></div>`)}
                    </div>` : ''}
                ${repeat(rows, () => `
                    <div class="skeleton-table-row">
                        <div class="skeleton skeleton-thumb"></div>
                        <div class="skeleton-stack">
                            <div class="skeleton skeleton-text skeleton-w-50"></div>
                            <div class="skeleton skeleton-text skeleton-w-33" style="margin-top:7px;height:9px"></div>
                        </div>
                        ${repeat(Math.max(0, cols - 3), () => `<div class="skeleton skeleton-text skeleton-w-100" style="flex:1"></div>`)}
                        <div class="skeleton skeleton-badge"></div>
                    </div>`)}
            </div>
        </div>`,

    /** A vertical list of rows (enquiries, submissions, activity). */
    list: ({ rows = 5, avatar = true } = {}) =>
        `<div class="skeleton-group">${repeat(rows, () => `
            <div class="skeleton-card skeleton-card-sm">
                <div class="skeleton-row">
                    ${avatar ? '<div class="skeleton skeleton-avatar"></div>' : ''}
                    <div class="skeleton-stack">
                        <div class="skeleton skeleton-text skeleton-w-50"></div>
                        <div class="skeleton skeleton-text skeleton-w-33" style="margin-top:7px;height:9px"></div>
                    </div>
                    <div class="skeleton skeleton-badge"></div>
                </div>
            </div>`)}</div>`,

    /** A responsive grid of cards (courses, gallery, recordings). */
    cards: ({ count = 6, media = true } = {}) =>
        `<div class="skeleton-grid">${repeat(count, () => `
            <div class="skeleton-card">
                ${media ? '<div class="skeleton" style="height:130px;margin:-20px -20px 16px;border-radius:var(--border-radius) var(--border-radius) 0 0"></div>' : ''}
                <div class="skeleton skeleton-title skeleton-w-75"></div>
                <div class="skeleton skeleton-text skeleton-w-50" style="margin-top:10px"></div>
                <div class="skeleton-row" style="margin-top:16px">
                    <div class="skeleton skeleton-badge"></div>
                    <div class="skeleton skeleton-text skeleton-w-25" style="margin-left:auto"></div>
                </div>
            </div>`)}</div>`,

    /** A form, laid out on the design system's two-column grid. */
    form: ({ fields = 6, columns = 2, actions = true } = {}) => `
        <div class="skeleton-card">
            <div class="${columns === 2 ? 'form-grid-2' : ''}">
                ${repeat(fields, () => `
                    <div class="form-group">
                        <div class="skeleton skeleton-text skeleton-w-33" style="margin-bottom:8px"></div>
                        <div class="skeleton skeleton-input skeleton-w-100"></div>
                    </div>`)}
            </div>
            ${actions ? `
                <div class="skeleton-row" style="justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid var(--color-border)">
                    <div class="skeleton skeleton-btn" style="width:90px"></div>
                    <div class="skeleton skeleton-btn"></div>
                </div>` : ''}
        </div>`,

    /** Bars rising to different heights, so it reads as a chart. */
    chart: ({ bars = 8 } = {}) => `
        <div class="skeleton-card">
            <div class="skeleton skeleton-title skeleton-w-33"></div>
            <div class="skeleton-chart">
                ${repeat(bars, (i) => `<div class="skeleton" style="height:${35 + ((i * 17) % 60)}%"></div>`)}
            </div>
        </div>`,

    /** A profile / detail header. */
    profile: () => `
        <div class="skeleton-card">
            <div class="skeleton-row" style="gap:20px">
                <div class="skeleton skeleton-circle" style="width:72px;height:72px"></div>
                <div class="skeleton-stack">
                    <div class="skeleton skeleton-heading skeleton-w-33"></div>
                    <div class="skeleton skeleton-text skeleton-w-50" style="margin-top:10px"></div>
                    <div class="skeleton-row" style="margin-top:14px">
                        <div class="skeleton skeleton-badge"></div>
                        <div class="skeleton skeleton-badge"></div>
                    </div>
                </div>
            </div>
        </div>`,

    /** A month grid, for the class calendar. */
    calendar: ({ weeks = 5 } = {}) => `
        <div class="card">
            <div class="card-body">
                <div class="skeleton-row" style="margin-bottom:14px">
                    <div class="skeleton skeleton-title skeleton-w-25"></div>
                    <div class="skeleton skeleton-btn" style="width:80px;margin-left:auto"></div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:6px">
                    ${repeat(7, () => '<div class="skeleton skeleton-text" style="height:14px"></div>')}
                    ${repeat(weeks * 7, () => '<div class="skeleton" style="height:74px"></div>')}
                </div>
            </div>
        </div>`,

    /** A chat transcript, alternating sides. */
    chat: ({ rows = 5 } = {}) =>
        `<div class="skeleton-group" style="gap:14px">${repeat(rows, (i) => `
            <div style="display:flex;${i % 3 === 0 ? 'justify-content:flex-end' : ''}">
                <div class="skeleton-stack" style="max-width:70%">
                    <div class="skeleton skeleton-text skeleton-w-25" style="height:9px"></div>
                    <div class="skeleton" style="height:34px;width:${140 + ((i * 43) % 160)}px;margin-top:6px;border-radius:14px"></div>
                </div>
            </div>`)}</div>`,

    /** The roster / attendance list, with a status control on each row. */
    roster: ({ rows = 6 } = {}) =>
        `<div class="skeleton-group">${repeat(rows, () => `
            <div class="skeleton-card skeleton-card-sm">
                <div class="skeleton-row">
                    <div class="skeleton skeleton-avatar"></div>
                    <div class="skeleton-stack">
                        <div class="skeleton skeleton-text skeleton-w-50"></div>
                        <div class="skeleton skeleton-text skeleton-w-25" style="margin-top:7px;height:9px"></div>
                    </div>
                    <div class="skeleton skeleton-input" style="width:110px"></div>
                </div>
            </div>`)}</div>`,

    /** The dashboard: hero, tiles, then two panels. */
    dashboard: () => `
        <div class="skeleton-group" style="gap:24px">
            <div class="skeleton" style="height:120px;border-radius:var(--border-radius-lg)"></div>
            ${skeletons.stats({ count: 4 })}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                ${skeletons.list({ rows: 3 })}
                ${skeletons.list({ rows: 3 })}
            </div>
        </div>`,

    /** Inside a modal or drawer body. */
    modal: ({ lines = 4 } = {}) => `
        <div class="skeleton-group">
            <div class="skeleton skeleton-title skeleton-w-50"></div>
            ${skeletons.text({ lines })}
            <div class="skeleton-row" style="justify-content:flex-end;margin-top:12px">
                <div class="skeleton skeleton-btn" style="width:80px"></div>
                <div class="skeleton skeleton-btn"></div>
            </div>
        </div>`,
};

/**
 * Render a skeleton into a target.
 *
 * The previous content is stashed so hideSkeleton() can put it back, which is
 * what makes this safe to use on a section that already has markup.
 *
 * @param {string|Element} target
 * @param {string} type   a key of `skeletons`
 * @param {object} options passed to the template
 */
export function showSkeleton(target, type = 'text', options = {}) {
    const el = resolve(target);
    if (!el) return null;

    const template = skeletons[type] || skeletons.text;

    if (el.dataset.skeletonActive !== '1') {
        el.dataset.skeletonPrevious = el.innerHTML;
        el.dataset.skeletonActive = '1';
    }

    el.setAttribute('aria-busy', 'true');
    el.innerHTML = markDecorative(template(options));

    return el;
}

/**
 * Replace a skeleton with real content.
 *
 * Pass `html` to swap in new markup, or omit it to restore what was there before
 * the skeleton was shown.
 */
export function hideSkeleton(target, html = null) {
    const el = resolve(target);
    if (!el) return null;

    if (html !== null) {
        el.innerHTML = html;
    } else if (el.dataset.skeletonActive === '1') {
        el.innerHTML = el.dataset.skeletonPrevious || '';
    }

    delete el.dataset.skeletonActive;
    delete el.dataset.skeletonPrevious;
    el.removeAttribute('aria-busy');

    // Soft fade so loading -> loaded / empty / error is not a hard jump.
    el.classList.add('content-enter');
    el.addEventListener('animationend', () => el.classList.remove('content-enter'), { once: true });

    return el;
}

/* --------------------------------------------------- section (component) loader */

/**
 * Overlay a single section while it refreshes, leaving the rest of the page
 * usable. Preferred over a page loader for filtering, sorting and pagination.
 */
export function showLoader(target, label = '') {
    const el = resolve(target);
    if (!el || el.querySelector(':scope > .section-loader')) return el;

    el.classList.add('is-loading');
    el.setAttribute('aria-busy', 'true');

    const overlay = document.createElement('div');
    overlay.className = 'section-loader';
    overlay.innerHTML = `<span class="spinner" aria-hidden="true"></span>${
        label ? `<span class="section-loader-label">${label}</span>` : ''
    }`;
    el.appendChild(overlay);

    return el;
}

export function hideLoader(target) {
    const el = resolve(target);
    if (!el) return null;

    el.querySelectorAll(':scope > .section-loader').forEach((n) => n.remove());
    el.classList.remove('is-loading');
    el.removeAttribute('aria-busy');

    return el;
}

/* ------------------------------------------------------------ button loading */

/**
 * Put a button into its busy state and block re-entry.
 *
 * Disabling is the point: it is what prevents the double-submits that create
 * duplicate records.
 */
export function setButtonLoading(button, loading = true, loadingText = null) {
    const btn = resolve(button);
    if (!btn) return null;

    if (loading) {
        if (btn.dataset.btnBusy === '1') return btn; // already busy — ignore
        btn.dataset.btnBusy = '1';
        btn.dataset.btnOriginal = btn.innerHTML;
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');

        if (loadingText) {
            btn.innerHTML = `<span class="spinner spinner-sm spinner-light" aria-hidden="true"></span> ${loadingText}`;
        } else {
            // .btn-loading hides the label and spins in place, so the button
            // keeps its width and nothing around it moves.
            btn.classList.add('btn-loading');
        }
    } else {
        if (btn.dataset.btnOriginal !== undefined) btn.innerHTML = btn.dataset.btnOriginal;
        btn.classList.remove('btn-loading');
        btn.disabled = false;
        btn.removeAttribute('aria-busy');
        delete btn.dataset.btnBusy;
        delete btn.dataset.btnOriginal;
    }

    return btn;
}

export function isButtonLoading(button) {
    const btn = resolve(button);
    return !!btn && btn.dataset.btnBusy === '1';
}

/**
 * Run an async action with the button busy for its duration.
 *
 * Returns undefined immediately if the button is already busy, which is the
 * duplicate-click guard for anything routed through here.
 */
export async function withButtonLoading(button, action, loadingText = null) {
    const btn = resolve(button);
    if (btn && isButtonLoading(btn)) return undefined;

    setButtonLoading(btn, true, loadingText);
    try {
        return await action();
    } finally {
        setButtonLoading(btn, false);
    }
}

/* --------------------------------------------------------------- page level */

let progressEl = null;
let progressTimer = null;
let progressDepth = 0;

function progressBar() {
    if (progressEl) return progressEl;

    progressEl = document.createElement('div');
    progressEl.className = 'page-progress';
    progressEl.setAttribute('role', 'progressbar');
    progressEl.setAttribute('aria-hidden', 'true');
    progressEl.innerHTML = '<div class="page-progress-bar"></div>';
    document.body.appendChild(progressEl);

    return progressEl;
}

/**
 * A slim top bar for navigation and fetches.
 *
 * Ref-counted, so overlapping requests do not fight over it. It creeps toward
 * 90% rather than claiming a real percentage we cannot know.
 */
export function showPageLoading() {
    progressDepth += 1;
    if (progressDepth > 1) return;

    const el = progressBar();
    const bar = el.firstElementChild;

    el.classList.add('active');
    bar.style.width = '8%';

    let width = 8;
    clearInterval(progressTimer);
    progressTimer = setInterval(() => {
        width = Math.min(90, width + Math.max(0.4, (90 - width) * 0.08));
        bar.style.width = `${width}%`;
    }, 180);
}

export function hidePageLoading(force = false) {
    progressDepth = force ? 0 : Math.max(0, progressDepth - 1);
    if (progressDepth > 0) return;

    clearInterval(progressTimer);
    progressTimer = null;

    if (!progressEl) return;
    const bar = progressEl.firstElementChild;

    bar.style.width = '100%';
    setTimeout(() => {
        progressEl.classList.remove('active');
        setTimeout(() => { bar.style.width = '0'; }, 200);
    }, 150);
}

/**
 * Blocking full-screen loader.
 *
 * Deliberately separate from showPageLoading: reserve this for critical,
 * application-level operations where the user genuinely must wait.
 */
let overlayEl = null;

export function showPageOverlay(label = 'Working…') {
    if (!overlayEl) {
        overlayEl = document.createElement('div');
        overlayEl.className = 'page-overlay';
        overlayEl.setAttribute('role', 'alert');
        overlayEl.setAttribute('aria-live', 'assertive');
        document.body.appendChild(overlayEl);
    }

    overlayEl.innerHTML = `<span class="spinner spinner-lg" aria-hidden="true"></span><span class="page-overlay-label">${label}</span>`;
    requestAnimationFrame(() => overlayEl.classList.add('active'));
    document.body.style.overflow = 'hidden';

    return overlayEl;
}

export function hidePageOverlay() {
    if (!overlayEl) return;
    overlayEl.classList.remove('active');
    document.body.style.overflow = '';
    setTimeout(() => { overlayEl?.remove(); overlayEl = null; }, 220);
}

/* ------------------------------------------------------------- fetch wrapper */

/**
 * fetch() with the page progress bar attached.
 *
 * Any request routed through here contributes to the bar and cleans up after
 * itself, including on failure.
 */
export async function fetchWithProgress(input, init = {}) {
    showPageLoading();
    try {
        return await fetch(input, init);
    } finally {
        hidePageLoading();
    }
}

/* ------------------------------------------------------------------- exports */

export const Loading = {
    skeletons,
    showSkeleton,
    hideSkeleton,
    showLoader,
    hideLoader,
    setButtonLoading,
    isButtonLoading,
    withButtonLoading,
    showPageLoading,
    hidePageLoading,
    showPageOverlay,
    hidePageOverlay,
    fetchWithProgress,
};

// Blade templates call these inline, so expose them globally too.
if (typeof window !== 'undefined') {
    window.Loading = Loading;
}

export default Loading;
