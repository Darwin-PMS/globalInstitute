/**
 * Auto-wiring for server-rendered (Blade) pages.
 *
 * Delegated from the document, so every existing admin screen gets loading
 * feedback without touching its template. Anything opting out marks itself with
 * `data-no-loading`.
 *
 * Covers: form submits, delete confirmations, filter/search/sort, pagination,
 * and normal link navigation.
 */
import {
    setButtonLoading,
    isButtonLoading,
    showLoader,
    showPageLoading,
    hidePageLoading,
} from './loading.js';

/**
 * The element a filter/search/pagination action should overlay.
 *
 * Resolved by convention so no template needs editing: an explicit
 * `data-loading-section` wins, otherwise we take the card that wraps the page's
 * data table (which is how every admin index page is built), then fall back to
 * the other known content containers.
 */
const SECTION_SELECTORS = [
    '[data-loading-section]',
    '.table-responsive',
    '.gallery-grid',
    '.gallery-list',
    '.grid-view',
    '.skeleton-grid',
];

function closestSection(el) {
    // Explicit opt-in on an ancestor always wins.
    const explicit = el.closest('[data-loading-section]');
    if (explicit) return explicit;

    // The card holding the data table is the right visual unit to dim.
    const table = document.querySelector('.admin-table, table.table');
    if (table) {
        const card = table.closest('.card') || table.closest('.card-body-flush');
        if (card) return card;
    }

    for (const selector of SECTION_SELECTORS) {
        const found = el.closest(selector) || document.querySelector(selector);
        if (found) return found;
    }

    return null;
}

function optedOut(el) {
    return !!el.closest('[data-no-loading]');
}

/* ------------------------------------------------------------ form submits */

function wireForms() {
    document.addEventListener(
        'submit',
        (e) => {
            const form = e.target;
            if (!(form instanceof HTMLFormElement) || optedOut(form)) return;

            // A cancelled confirm() leaves the event cancelled — do not latch on.
            if (e.defaultPrevented) return;

            const submitter = e.submitter || form.querySelector('[type="submit"]');

            // Guard against a second submit while the first is in flight. This is
            // what stops duplicate records from an impatient double-click.
            if (submitter && isButtonLoading(submitter)) {
                e.preventDefault();
                return;
            }

            const isDelete = form.querySelector('input[name="_method"][value="DELETE"]');
            const isUpload = !!form.querySelector('input[type="file"]');

            if (submitter) {
                setButtonLoading(
                    submitter,
                    true,
                    isDelete ? 'Deleting…' : isUpload ? 'Uploading…' : null
                );
            }

            // A GET form is a filter; anything else is a write.
            const method = (form.getAttribute('method') || 'get').toLowerCase();
            if (method === 'get') {
                const section = closestSection(form);
                if (section) showLoader(section, 'Loading…');
            }

            // Whether a real navigation follows is only known once every other
            // handler has run: spa.js cancels admin submits and posts them over
            // axios instead, and the axios interceptor owns the bar in that case.
            setTimeout(() => {
                if (!e.defaultPrevented) showPageLoading();
            }, 0);
        },
        true // capture, so we see the event before a handler can stop it
    );
}

/* ------------------------------------------------- filters, search, sorting */

function wireFilters() {
    // Selects that submit their form on change (the design system's pattern).
    document.addEventListener('change', (e) => {
        const el = e.target;
        if (!el.matches('select.filter-select, select[data-filter]') || optedOut(el)) return;

        const section = closestSection(el);
        if (section) showLoader(section, 'Filtering…');
        showPageLoading();
    });

    // Debounced search inputs submit their own form; show feedback immediately so
    // the delay does not read as nothing happening.
    document.addEventListener('input', (e) => {
        const el = e.target;
        if (!el.matches('input.search-input, input[type="search"]') || optedOut(el)) return;

        clearTimeout(el._loadingTimer);
        el._loadingTimer = setTimeout(() => {
            const section = closestSection(el);
            if (section) showLoader(section, 'Searching…');
        }, 350);
    });
}

/* ------------------------------------------------- pagination & navigation */

function wireNavigation() {
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[href]');
        if (!link || optedOut(link)) return;

        // Leave modified clicks, new tabs, downloads and in-page anchors alone.
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
        if (link.target === '_blank' || link.hasAttribute('download')) return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
        if (href.startsWith('http') && !href.startsWith(window.location.origin)) return;

        // Pagination reloads a section's worth of rows: overlay that section too.
        if (link.closest('.pagination, .pagination-wrap, .gallery-pagination')) {
            const section = closestSection(link);
            if (section) showLoader(section, 'Loading…');
        }

        // Same reasoning as forms: only claim the bar if the browser is actually
        // going to navigate. An intercepted click is handled over axios.
        setTimeout(() => {
            if (!e.defaultPrevented) showPageLoading();
        }, 0);
    });

    /*
     | Partial navigation (resources/js/spa.js) swaps regions without a reload,
     | so nothing would otherwise release the loading state. spa.js announces
     | completion with `spa:load`; treat that as the end of the navigation.
     */
    document.addEventListener('spa:load', () => {
        hidePageLoading(true);

        document
            .querySelectorAll('[data-btn-busy="1"]')
            .forEach((btn) => setButtonLoading(btn, false));

        // Any section overlay belonged to the screen that just went away.
        document.querySelectorAll('.section-loader').forEach((n) => n.remove());
        document.querySelectorAll('.is-loading').forEach((n) => {
            n.classList.remove('is-loading');
            n.removeAttribute('aria-busy');
        });
    });

    // Coming back via the cache should not leave a stale bar or a dead button.
    window.addEventListener('pageshow', (e) => {
        if (e.persisted) {
            hidePageLoading(true);
            document
                .querySelectorAll('[data-btn-busy="1"]')
                .forEach((btn) => setButtonLoading(btn, false));
        }
    });

    // A cancelled navigation (validation, a blocked download) must release the bar.
    window.addEventListener('pagehide', () => hidePageLoading(true));
}

/* ------------------------------------------------------- action buttons ---- */

/**
 * Buttons that trigger an async action can opt in declaratively:
 *   <button data-loading-text="Saving…">Save</button>
 */
function wireActionButtons() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-loading-text]');
        if (!btn || optedOut(btn)) return;

        if (isButtonLoading(btn)) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return;
        }

        // A submit button is handled by the form handler, which knows whether the
        // submit actually proceeded.
        if (btn.type !== 'submit') {
            setButtonLoading(btn, true, btn.dataset.loadingText || null);
        }
    });
}

/* --------------------------------------------------------------- axios hook */

/**
 * Attach the progress bar to axios, which bootstrap.js already configures.
 * Any AJAX on a Blade page then reports itself without extra code.
 */
function wireAxios() {
    const axios = window.axios;
    if (!axios?.interceptors) return;

    axios.interceptors.request.use(
        (config) => {
            if (!config.silent) showPageLoading();
            return config;
        },
        (error) => {
            hidePageLoading();
            return Promise.reject(error);
        }
    );

    axios.interceptors.response.use(
        (response) => {
            if (!response.config?.silent) hidePageLoading();
            return response;
        },
        (error) => {
            hidePageLoading();
            return Promise.reject(error);
        }
    );
}

/* -------------------------------------------------------------------- boot */

export function initBladeLoading() {
    wireForms();
    wireFilters();
    wireNavigation();
    wireActionButtons();
    wireAxios();
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBladeLoading, { once: true });
    } else {
        initBladeLoading();
    }
}
