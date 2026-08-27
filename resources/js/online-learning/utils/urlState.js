/**
 * URL query-state helpers.
 *
 * Filters, search, view mode and pagination live in the query string so that
 * a refresh restores them and a URL can be shared (§13, §36).
 */

/**
 * Read the current query string into a plain object.
 */
export function readQuery() {
    const params = new URLSearchParams(window.location.search);
    const out = {};
    for (const [key, value] of params.entries()) {
        if (value !== '') out[key] = value;
    }
    return out;
}

/**
 * Merge `state` into the query string without adding a history entry.
 * Empty / null values are removed so URLs stay short.
 */
export function writeQuery(state = {}, { replace = true } = {}) {
    const params = new URLSearchParams(window.location.search);

    Object.entries(state).forEach(([key, value]) => {
        const isDefaultPage = key === 'page' && Number(value) === 1;

        if (value === null || value === undefined || value === '' || isDefaultPage) {
            params.delete(key);
        } else {
            params.set(key, String(value));
        }
    });

    const query = params.toString();
    const url = window.location.pathname + (query ? `?${query}` : '');

    if (replace) {
        window.history.replaceState(window.history.state, '', url);
    } else {
        window.history.pushState(null, '', url);
    }
}

/**
 * Seed a filter object from the URL, keeping the declared defaults for
 * anything the URL does not carry. Numeric-looking keys are coerced.
 */
export function hydrateFilters(defaults = {}, numericKeys = ['page', 'per_page']) {
    const query = readQuery();
    const merged = { ...defaults };

    Object.keys(defaults).forEach((key) => {
        if (query[key] !== undefined) {
            merged[key] = numericKeys.includes(key)
                ? parseInt(query[key], 10) || defaults[key]
                : query[key];
        }
    });

    return merged;
}

/**
 * Strip empty values before sending filters to the API, so the query string
 * we build never contains `status=&course_id=`.
 */
export function compactParams(params = {}) {
    return Object.fromEntries(
        Object.entries(params).filter(([, v]) => v !== null && v !== undefined && v !== '')
    );
}
