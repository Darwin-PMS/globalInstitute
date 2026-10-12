/* ============================================================
   admin.js — Admin SPA engine

   Markup here uses the project's own design system (resources/css/admin.css),
   the same vocabulary as resources/views/layouts/admin.blade.php. It previously
   emitted Bootstrap 5 markup (row/col-md/table-striped/spinner-border) while no
   Bootstrap CSS was loaded, which is why every screen rendered unstyled.
   ============================================================ */

/* ----------------------------------------------------------
   1. API helper
   ---------------------------------------------------------- */
var Api = {
    csrfToken: (window.AdminConfig && window.AdminConfig.csrfToken) || '',

    baseUrl: (function () {
        var p = window.location.pathname;
        var idx = p.indexOf('/admin');
        return idx > 0 ? p.substring(0, idx) : '';
    })(),

    setCsrfToken: function (token) { Api.csrfToken = token; },

    getHeaders: function (custom) {
        var headers = Object.assign({
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }, custom || {});

        if (Api.csrfToken) headers['X-CSRF-TOKEN'] = Api.csrfToken;
        return headers;
    },

    /**
     * The SPA runs inside the authenticated admin session, so a 401/403 means
     * the session is gone rather than a bad token — send the user to login
     * instead of leaving a half-rendered screen.
     */
    handle: function (res) {
        if (res.status === 401 || res.status === 419) {
            showToast('Your session has expired. Redirecting to sign in…', 'warning');
            setTimeout(function () { window.location.href = Api.baseUrl + '/admin/login'; }, 1200);
            throw new Error('Session expired');
        }

        return res.json().catch(function () { return {}; }).then(function (data) {
            if (!res.ok) {
                var err = new Error(data.message || ('Request failed (' + res.status + ')'));
                err.status = res.status;
                err.errors = data.errors || {};
                throw err;
            }
            return data;
        });
    },

    request: function (method, url, data) {
        var opts = {
            method: method,
            headers: Api.getHeaders(data ? { 'Content-Type': 'application/json' } : null),
            credentials: 'same-origin'
        };
        if (data) opts.body = JSON.stringify(data);

        if (window.Loading) window.Loading.showPageLoading();

        return fetch(Api.baseUrl + '/api/admin' + url, opts)
            .then(Api.handle)
            .finally(function () {
                if (window.Loading) window.Loading.hidePageLoading();
            });
    },

    get: function (url) { return Api.request('GET', url); },
    post: function (url, data) { return Api.request('POST', url, data || {}); },
    put: function (url, data) { return Api.request('PUT', url, data || {}); },
    'delete': function (url) { return Api.request('DELETE', url); }
};

/* ----------------------------------------------------------
   2. Toast
   ---------------------------------------------------------- */
function showToast(message, type) {
    type = type || 'success';

    var container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.setAttribute('role', 'status');
        container.setAttribute('aria-live', 'polite');
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:10000;display:flex;flex-direction:column;gap:10px;max-width:360px;';
        document.body.appendChild(container);
    }

    var tone = {
        success: 'var(--color-success)',
        error: 'var(--color-danger)',
        warning: 'var(--color-warning)',
        info: 'var(--color-info)'
    }[type] || 'var(--color-success)';

    var icon = {
        success: 'check_circle', error: 'error', warning: 'warning', info: 'info'
    }[type] || 'check_circle';

    var toast = document.createElement('div');
    toast.style.cssText =
        'display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:var(--border-radius);' +
        'background:var(--color-surface);border:1px solid var(--color-border);border-left:3px solid ' + tone + ';' +
        'box-shadow:var(--shadow-md);font-size:13px;color:var(--color-text);opacity:0;transform:translateX(12px);' +
        'transition:opacity .25s,transform .25s;';

    toast.innerHTML =
        '<span class="material-symbols-outlined" style="font-size:18px;color:' + tone + ';flex-shrink:0;">' + icon + '</span>' +
        '<span style="flex:1;line-height:1.45;">' + escapeHtml(message) + '</span>';

    container.appendChild(toast);
    requestAnimationFrame(function () {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    });

    setTimeout(function () {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(12px)';
        setTimeout(function () { toast.remove(); }, 250);
    }, 3500);
}

/* ----------------------------------------------------------
   3. Helpers
   ---------------------------------------------------------- */
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(String(str)));
    return div.innerHTML;
}

function badge(text, variant) {
    return '<span class="badge badge-' + (variant || 'secondary') + '">' + escapeHtml(text) + '</span>';
}

/** Maps the many status vocabularies in this project onto the badge variants. */
function statusBadge(status) {
    if (!status) return '<span class="badge badge-secondary">—</span>';

    var key = String(status).toLowerCase();
    var variant = {
        active: 'success', published: 'success', completed: 'success', paid: 'success',
        converted: 'success', approved: 'success', present: 'success',
        draft: 'draft', inactive: 'secondary', closed: 'secondary',
        pending: 'warning', contacted: 'warning', upcoming: 'warning', partial: 'warning',
        cancelled: 'danger', rejected: 'danger', absent: 'danger', unpaid: 'danger',
        'new': 'info', live: 'info', scheduled: 'info', postponed: 'info'
    }[key] || 'secondary';

    var label = String(status).replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
    return '<span class="badge badge-' + variant + '">' + escapeHtml(label) + '</span>';
}

function money(value) {
    if (value === null || value === undefined || value === '') return '<span style="color:var(--color-text-muted);">—</span>';
    var n = Number(value);
    if (isNaN(n)) return escapeHtml(String(value));
    return '<span class="mono" style="font-weight:500;">₹' + n.toLocaleString('en-IN') + '</span>';
}

function dateCell(value) {
    if (!value) return '<span style="color:var(--color-text-muted);">—</span>';
    var d = new Date(value);
    if (isNaN(d.getTime())) return escapeHtml(String(value));
    return '<span style="font-size:13px;color:var(--color-text-secondary);">' +
        d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) + '</span>';
}

function muted(value) {
    return value
        ? '<span style="font-size:13px;color:var(--color-text-secondary);">' + escapeHtml(value) + '</span>'
        : '<span style="color:var(--color-text-muted);">—</span>';
}

/** Edit + delete controls for a table row, using the design system icon buttons. */
function rowActions(pageKey, route, id) {
    return '<div style="display:flex;gap:4px;justify-content:flex-end;">' +
        '<a href="#/' + route + '/edit/' + id + '" class="icon-btn icon-btn-edit" title="Edit" aria-label="Edit">' +
        '<span class="material-symbols-outlined" style="font-size:18px;">edit</span></a>' +
        '<button type="button" class="icon-btn icon-btn-delete" title="Delete" aria-label="Delete" ' +
        'onclick="GenericDelete(pages.' + pageKey + ',' + id + ')">' +
        '<span class="material-symbols-outlined" style="font-size:18px;">delete</span></button>' +
        '</div>';
}

/* ---- form field builders (design system: .form-group / .form-control) ---- */

/** A red asterisk next to a label when the field is marked `required`. */
function requiredMark(attrs) {
    return /\brequired\b/.test(attrs || '') ? '<span class="req-mark" title="Required">*</span>' : '';
}

function inputField(name, label, value, type, attrs) {
    type = type || 'text';
    var val = (value !== null && value !== undefined) ? escapeHtml(String(value)) : '';

    return '<div class="form-group">' +
        '<label for="f-' + name + '">' + escapeHtml(label) + requiredMark(attrs) + '</label>' +
        '<input type="' + type + '" id="f-' + name + '" class="form-control" name="' + name + '" value="' + val + '" ' + (attrs || '') + '>' +
        '<p class="form-error" data-error-for="' + name + '" style="display:none;"></p>' +
        '</div>';
}

function selectField(name, label, value, options, attrs) {
    var html = '<div class="form-group">' +
        '<label for="f-' + name + '">' + escapeHtml(label) + requiredMark(attrs) + '</label>' +
        '<select id="f-' + name + '" class="form-control" name="' + name + '" ' + (attrs || '') + '>' +
        '<option value="">— Select —</option>';

    (options || []).forEach(function (opt) {
        var optVal = (typeof opt === 'object') ? opt.value : opt;
        var optLabel = (typeof opt === 'object') ? opt.label : opt;
        var selected = String(value) === String(optVal) ? ' selected' : '';
        html += '<option value="' + escapeHtml(String(optVal)) + '"' + selected + '>' + escapeHtml(String(optLabel)) + '</option>';
    });

    return html + '</select>' +
        '<p class="form-error" data-error-for="' + name + '" style="display:none;"></p>' +
        '</div>';
}

function textareaField(name, label, value, attrs) {
    var val = (value !== null && value !== undefined) ? escapeHtml(String(value)) : '';

    return '<div class="form-group">' +
        '<label for="f-' + name + '">' + escapeHtml(label) + requiredMark(attrs) + '</label>' +
        '<textarea id="f-' + name + '" class="form-control" name="' + name + '" rows="4" ' + (attrs || '') + '>' + val + '</textarea>' +
        '<p class="form-error" data-error-for="' + name + '" style="display:none;"></p>' +
        '</div>';
}

function statusSelect(name, label, value, options) {
    return selectField(name, label || 'Status', value, options || [
        { value: 'active', label: 'Active' },
        { value: 'inactive', label: 'Inactive' },
        { value: 'pending', label: 'Pending' }
    ]);
}

/** Fills a <select> that was rendered empty, once its options have loaded. */
function fillSelect(name, items, valueKey, labelKey, current) {
    var sel = document.querySelector('[name="' + name + '"]');
    if (!sel) return;

    (items || []).forEach(function (item) {
        var opt = document.createElement('option');
        opt.value = item[valueKey];
        opt.textContent = item[labelKey] || item.name || item.title || ('#' + item[valueKey]);
        if (current !== undefined && current !== null && String(current) === String(item[valueKey])) {
            opt.selected = true;
        }
        sel.appendChild(opt);
    });
}

/** Unwraps either an envelope ({data:[...]}) or a bare array. */
function listOf(response) {
    if (!response) return [];
    if (Array.isArray(response)) return response;
    if (Array.isArray(response.data)) return response.data;
    return [];
}

/* ---- shared page chrome ---- */

/**
 * Skeletons come from the shared system in resources/js/ui/loading.js, exposed
 * as window.Loading. The inline fallback only matters if that bundle has not
 * loaded yet.
 */
function skeletonRows(rows) {
    if (window.Loading) {
        return window.Loading.skeletons.table({ rows: rows || 6, cols: 5 });
    }

    var html = '<div class="card"><div class="card-body-flush">';
    for (var r = 0; r < (rows || 6); r++) {
        html += '<div class="skeleton-table-row">' +
            '<div class="skeleton skeleton-thumb"></div>' +
            '<div class="skeleton-stack">' +
            '<div class="skeleton skeleton-text skeleton-w-50"></div>' +
            '<div class="skeleton skeleton-text skeleton-w-33" style="margin-top:7px"></div>' +
            '</div>' +
            '<div class="skeleton skeleton-badge"></div>' +
            '</div>';
    }
    return html + '</div></div>';
}

/** Stat tiles, for the dashboard. */
function skeletonStats(count) {
    return window.Loading
        ? window.Loading.skeletons.stats({ count: count || 4 })
        : skeletonRows(2);
}

/** Form placeholder, for create/edit screens. */
function skeletonForm(fields) {
    return window.Loading
        ? window.Loading.skeletons.form({ fields: fields || 6 })
        : skeletonRows(4);
}

function emptyState(icon, title, description, actionHtml) {
    return '<div class="card"><div class="empty-state">' +
        '<span class="material-symbols-outlined">' + (icon || 'inbox') + '</span>' +
        '<h3>' + escapeHtml(title) + '</h3>' +
        '<p>' + escapeHtml(description || '') + '</p>' +
        (actionHtml ? '<div class="empty-state-actions">' + actionHtml + '</div>' : '') +
        '</div></div>';
}

function errorState(message, retryFnName) {
    return '<div class="alert alert-danger">' +
        '<span class="material-symbols-outlined" style="font-size:18px;">error</span>' +
        '<div style="flex:1;">' + escapeHtml(message || 'Something went wrong.') + '</div>' +
        (retryFnName ? '<button type="button" class="btn btn-sm btn-outline" onclick="' + retryFnName + '()">Try again</button>' : '') +
        '</div>';
}

function pageHeader(title, description) {
    return '<div class="page-header"><div class="page-header-content">' +
        '<h1>' + escapeHtml(title) + '</h1>' +
        (description ? '<p class="page-header-desc">' + escapeHtml(description) + '</p>' : '') +
        '</div></div>';
}

/* ----------------------------------------------------------
   4. Navigation definition
   ---------------------------------------------------------- */

/**
 * Mirrors the sidebar in layouts/admin.blade.php.
 *
 * `spa: true`  — handled in-page by this SPA (a config exists in `pages`).
 * `spa: false` — no JSON API yet, so the link goes to the working Blade screen
 *                with a normal page load. Nothing in the sidebar is a dead end.
 *
 * NAV_SECTIONS is populated dynamically from the /api/admin/sidebar endpoint.
 * The hardcoded fallback below is used only if the API call fails.
 */
var NAV_SECTIONS = null;
var USER_PERMISSIONS = [];
var USER_IS_SUPER_ADMIN = false;

/** Check if user has a specific permission */
function userHasPermission(slug) {
    if (USER_IS_SUPER_ADMIN) return true;
    return USER_PERMISSIONS.indexOf(slug) !== -1;
}

/* ----------------------------------------------------------
   5. Router
   ---------------------------------------------------------- */

/**
 * Hash routes: #/page, #/page/create, #/page/edit/:id
 *
 * Dispatch looks the config up in `pages` at call time. The previous version
 * registered a closure per route inside a `for…in` loop using `var`, so every
 * handler shared the final loop value — which is why every screen rendered the
 * dashboard config. Resolving by key removes that class of bug entirely.
 */
var Router = {
    current: null,

    parse: function (hash) {
        var raw = (hash || '').replace(/^#\/?/, '');
        var parts = raw.split('/').filter(function (p) { return p !== ''; });

        return {
            page: parts[0] || 'dashboard',
            action: parts[1] || 'index',
            id: parts[2] || null
        };
    },

    resolve: function () {
        var target = Router.parse(window.location.hash);
        var config = pages[target.page];

        Router.current = target.page;
        Router.updateSidebar(target.page);
        AdminApp.setActions('');

        if (target.page === 'dashboard') {
            DashboardPage();
            return;
        }

        if (target.page === 'live-classroom') {
            LiveClassroomPage();
            return;
        }

        if (!config) {
            Router.renderUnknown(target.page);
            return;
        }

        if (target.action === 'create') {
            GenericCreatePage(config);
        } else if (target.action === 'edit' && target.id) {
            GenericEditPage(config, target.id);
        } else {
            GenericListPage(config);
        }
    },

    /** An unknown hash says so, rather than silently showing the dashboard. */
    renderUnknown: function (page) {
        AdminApp.setTitle('Not found');
        document.getElementById('admin-content').innerHTML =
            emptyState(
                'explore_off',
                'This screen does not exist',
                '"' + page + '" is not an admin section.',
                '<a href="#/dashboard" class="btn btn-primary btn-sm">' +
                '<span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back to dashboard</a>'
            );
    },

    navigate: function (path) { window.location.hash = '#/' + path; },

    updateSidebar: function (active) {
        var links = document.querySelectorAll('#sidebar-nav .nav-link');
        Array.prototype.forEach.call(links, function (link) {
            if (link.getAttribute('data-route') === active) {
                link.classList.add('active');
                link.setAttribute('aria-current', 'page');
            } else {
                link.classList.remove('active');
                link.removeAttribute('aria-current');
            }
        });
    },

    init: function () {
        window.removeEventListener('hashchange', Router.resolve);
        window.addEventListener('hashchange', Router.resolve);
        Router.resolve();
    }
};

/* ----------------------------------------------------------
   6. AdminApp
   ---------------------------------------------------------- */
var AdminApp = {
    init: function () {
        // Fetch sidebar data from API first, then render and resolve route
        var nav = document.getElementById('sidebar-nav');
        if (nav) {
            AdminApp.fetchSidebar(nav).then(function () {
                if (!window.location.hash || window.location.hash === '#/' || window.location.hash === '#') {
                    window.location.replace('#/dashboard');
                }
                Router.init();
            });
        } else {
            AdminApp.renderSidebar();
            if (!window.location.hash || window.location.hash === '#/' || window.location.hash === '#') {
                window.location.replace('#/dashboard');
            }
            Router.init();
        }
    },

    /** Same markup as the sidebar in layouts/admin.blade.php. */
    renderSidebar: function () {
        var nav = document.getElementById('sidebar-nav');
        if (!nav) return;

        // If sidebar data is loaded from API, use it
        if (NAV_SECTIONS) {
            var html = '';
            NAV_SECTIONS.forEach(function (section) {
                html += '<div class="nav-section">';
                html += '<div class="nav-section-label">' + escapeHtml(section.name) + '</div>';

                if (section.children) {
                    section.children.forEach(function (item) {
                        var href;
                        var external = '';
                        if (item.is_spa && !item.url) {
                            href = '#/' + item.slug;
                        } else if (item.url && item.url.indexOf('http') === 0) {
                            href = item.url;
                            external = ' target="_blank" rel="noopener"';
                        } else if (item.url) {
                            href = Api.baseUrl + item.url;
                            external = ' data-external="true"';
                        } else if (item.route_name) {
                            href = Api.baseUrl + '/' + item.route_name.replace(/\./g, '/');
                            external = ' data-external="true"';
                        } else {
                            href = '#/' + item.slug;
                        }

                        html += '<a href="' + href + '" class="nav-link" data-route="' + item.slug + '"' + external + '>' +
                            (item.icon ? '<span class="material-symbols-outlined">' + item.icon + '</span>' : '') +
                            '<span class="nav-text">' + escapeHtml(item.name) + '</span>' +
                            '</a>';
                    });
                }

                html += '</div>';
            });

            nav.innerHTML = html;
        } else {
            // Fallback: fetch sidebar from API
            AdminApp.fetchSidebar(nav);
            return;
        }

        // On mobile the sidebar overlays the content, so a chosen link closes it.
        Array.prototype.forEach.call(nav.querySelectorAll('.nav-link'), function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 1024px)').matches && typeof closeSidebar === 'function') {
                    closeSidebar();
                }
            });
        });
    },

    /** Fetch sidebar data from the API */
    fetchSidebar: function (nav) {
        if (!nav) nav = document.getElementById('sidebar-nav');
        if (!nav) return Promise.resolve();

        return Api.get('/sidebar').then(function (res) {
            if (res.success && res.data) {
                NAV_SECTIONS = res.data.menus || [];
                USER_PERMISSIONS = res.data.permissions || [];
                USER_IS_SUPER_ADMIN = res.data.is_super_admin || false;

                // Store permissions for use by page renderers
                window.USER_PERMISSIONS = USER_PERMISSIONS;
                window.USER_IS_SUPER_ADMIN = USER_IS_SUPER_ADMIN;

                AdminApp.renderSidebar();

                // Re-apply active state
                var target = Router.parse(window.location.hash);
                Router.updateSidebar(target.page);
            }
        }).catch(function () {
            // On error, show minimal sidebar
            nav.innerHTML = '<div class="nav-section"><div class="nav-section-label">Menu</div>' +
                '<a href="#/dashboard" class="nav-link" data-route="dashboard">' +
                '<span class="material-symbols-outlined">dashboard</span>' +
                '<span class="nav-text">Dashboard</span></a></div>';
        });
    },

    setTitle: function (title) {
        var el = document.getElementById('page-title');
        if (el) el.textContent = title;
        document.title = title + ' · Global Institute Admin';
    },

    /** Buttons for the page, rendered into the fixed header. */
    setActions: function (html) {
        var el = document.getElementById('page-actions');
        if (el) el.innerHTML = html || '';
    },

    openModal: function (title, bodyHtml, footerHtml) {
        var root = document.getElementById('modal-root');
        if (!root) return;

        root.innerHTML =
            '<div class="modal-overlay" id="active-modal">' +
            '<div class="modal">' +
            '<div class="modal-header">' +
            '<h3>' + escapeHtml(title) + '</h3>' +
            '<button type="button" class="modal-close" onclick="AdminApp.closeModal()" aria-label="Close">' +
            '<span class="material-symbols-outlined">close</span></button>' +
            '</div>' +
            '<div class="modal-body">' + bodyHtml + '</div>' +
            (footerHtml ? '<div class="modal-footer">' + footerHtml + '</div>' : '') +
            '</div></div>';

        var overlay = document.getElementById('active-modal');
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) AdminApp.closeModal();
        });

        AdminApp._modalKeyHandler = function (e) {
            if (e.key === 'Escape') AdminApp.closeModal();
        };
        document.addEventListener('keydown', AdminApp._modalKeyHandler);
    },

    closeModal: function () {
        var root = document.getElementById('modal-root');
        if (root) root.innerHTML = '';
        if (AdminApp._modalKeyHandler) {
            document.removeEventListener('keydown', AdminApp._modalKeyHandler);
            AdminApp._modalKeyHandler = null;
        }
    }
};

/* ----------------------------------------------------------
   7. Generic CRUD screens
   ---------------------------------------------------------- */

function GenericListPage(config) {
    var content = document.getElementById('admin-content');
    var state = { page: 1, search: '', filter: '', perPage: 15 };
    var searchTimer = null;

    AdminApp.setTitle(config.title);

    var singular = config.singular || config.title.replace(/ies$/, 'y').replace(/s$/, '');

    if (config.canCreate !== false) {
        AdminApp.setActions(
            '<a href="#/' + config.route + '/create" class="btn btn-primary btn-sm">' +
            '<span class="material-symbols-outlined" style="font-size:18px;">add</span> Add ' + escapeHtml(singular) +
            '</a>'
        );
    }

    var filterHtml = '';
    if (config.filterOptions) {
        filterHtml = '<select class="filter-select" id="filter-select" aria-label="Filter">' +
            '<option value="">All ' + escapeHtml(config.title) + '</option>' +
            config.filterOptions.map(function (o) {
                return '<option value="' + escapeHtml(o.value) + '">' + escapeHtml(o.label) + '</option>';
            }).join('') +
            '</select>';
    }

    content.innerHTML =
        pageHeader(config.title, config.description) +
        '<div class="toolbar">' +
        '<div class="toolbar-left">' +
        '<div class="search-wrap">' +
        '<span class="material-symbols-outlined">search</span>' +
        '<input type="search" class="search-input" id="search-input" placeholder="Search ' + escapeHtml(config.title.toLowerCase()) + '…" aria-label="Search">' +
        '<button type="button" class="search-clear" id="search-clear" style="display:none;" aria-label="Clear search">' +
        '<span class="material-symbols-outlined">close</span></button>' +
        '</div>' +
        filterHtml +
        '</div>' +
        '<div class="toolbar-right">' +
        '<span class="pagination-info" id="result-count"></span>' +
        '<button type="button" class="icon-btn" id="list-refresh" title="Refresh" aria-label="Refresh list">' +
        '<span class="material-symbols-outlined">refresh</span></button>' +
        '</div>' +
        '</div>' +
        '<div id="list-body">' + skeletonRows(6) + '</div>';

    var searchInput = document.getElementById('search-input');
    var searchClear = document.getElementById('search-clear');

    searchInput.addEventListener('input', function (e) {
        if (searchClear) searchClear.style.display = e.target.value ? 'flex' : 'none';
        clearTimeout(searchTimer);
        var value = e.target.value;
        searchTimer = setTimeout(function () {
            state.search = value;
            state.page = 1;
            load();
        }, 350);
    });

    if (searchClear) {
        searchClear.addEventListener('click', function () {
            searchInput.value = '';
            state.search = '';
            state.page = 1;
            searchClear.style.display = 'none';
            searchInput.focus();
            load();
        });
    }

    document.getElementById('list-refresh').addEventListener('click', function () {
        load();
    });

    if (config.filterOptions) {
        document.getElementById('filter-select').addEventListener('change', function (e) {
            state.filter = e.target.value;
            state.page = 1;
            load();
        });
    }

    function load() {
        var body = document.getElementById('list-body');
        if (!body) return;

        // First paint gets a skeleton; a refresh (search, filter, page) overlays
        // the existing rows so the screen never flashes empty.
        if (body.dataset.loadedOnce === '1' && window.Loading) {
            window.Loading.showLoader(body, 'Loading…');
        } else {
            body.innerHTML = skeletonRows(6);
        }

        var url = config.api + '?page=' + state.page + '&per_page=' + state.perPage;
        if (state.search) url += '&search=' + encodeURIComponent(state.search);
        if (state.filter) url += '&' + (config.filterKey || 'status') + '=' + encodeURIComponent(state.filter);

        Api.get(url).then(function (response) {
            var items = listOf(response);
            var meta = response.meta || response;

            var countEl = document.getElementById('result-count');
            if (countEl) {
                var total = meta.total != null ? meta.total : items.length;
                countEl.textContent = total + ' ' + (total === 1 ? 'record' : 'records');
            }

            if (!items.length) {
                body.innerHTML = emptyState(
                    config.icon || 'inbox',
                    state.search || state.filter ? 'Nothing matches your filters' : 'No ' + config.title.toLowerCase() + ' yet',
                    state.search || state.filter
                        ? 'Try a different search term or clear the filter.'
                        : (config.emptyHint || 'Records you add will appear here.'),
                    config.canCreate === false ? '' :
                        '<a href="#/' + config.route + '/create" class="btn btn-primary btn-sm">' +
                        '<span class="material-symbols-outlined" style="font-size:18px;">add</span> Add ' + escapeHtml(singular) + '</a>'
                );
                return;
            }

            var html = '<div class="card"><div class="card-body-flush"><div class="table-responsive">' +
                '<table class="admin-table"><thead><tr>';

            config.columns.forEach(function (col) {
                html += '<th' + (col.width ? ' style="width:' + col.width + ';"' : '') + '>' + escapeHtml(col.label) + '</th>';
            });
            html += '<th style="width:90px;"></th></tr></thead><tbody>';

            items.forEach(function (row) {
                html += '<tr>';
                config.columns.forEach(function (col) {
                    var value = row[col.key];
                    html += '<td>' + (col.render ? col.render(value, row) : (value != null ? escapeHtml(String(value)) : '<span style="color:var(--color-text-muted);">—</span>')) + '</td>';
                });
                html += '<td>' + (config.actions ? config.actions(row) : '') + '</td></tr>';
            });

            html += '</tbody></table></div></div>';

            if (meta.last_page && meta.last_page > 1) {
                html += '<div class="card-footer">' + paginationHtml(meta) + '</div>';
            }
            html += '</div>';

            if (window.Loading) window.Loading.hideLoader(body);
            body.innerHTML = html;
            body.dataset.loadedOnce = '1';
            bindPagination(body);
        }).catch(function (err) {
            if (err.message === 'Session expired') return;
            if (window.Loading) window.Loading.hideLoader(body);
            body.innerHTML = errorState('Unable to load ' + config.title.toLowerCase() + '. ' + err.message, 'GenericListPage._retry');
        });
    }

    function paginationHtml(meta) {
        var current = meta.current_page || 1;
        var last = meta.last_page || 1;
        var from = meta.from || ((current - 1) * (meta.per_page || state.perPage) + 1);
        var to = meta.to || Math.min(current * (meta.per_page || state.perPage), meta.total || 0);

        var html = '<div class="pagination-wrap" style="margin-top:0;border-top:none;padding-top:0;">' +
            '<div class="pagination-info">Showing ' + from + '–' + to + ' of ' + (meta.total || 0) + '</div>' +
            '<div class="pagination">';

        html += '<button type="button" class="btn btn-sm btn-outline" data-page="' + (current - 1) + '"' +
            (current <= 1 ? ' disabled' : '') + ' aria-label="Previous page">' +
            '<span class="material-symbols-outlined" style="font-size:16px;">chevron_left</span></button>';

        var start = Math.max(1, current - 2);
        var end = Math.min(last, current + 2);
        for (var p = start; p <= end; p++) {
            html += '<button type="button" class="btn btn-sm ' + (p === current ? 'btn-primary' : 'btn-outline') +
                '" data-page="' + p + '"' + (p === current ? ' aria-current="page"' : '') + '>' + p + '</button>';
        }

        html += '<button type="button" class="btn btn-sm btn-outline" data-page="' + (current + 1) + '"' +
            (current >= last ? ' disabled' : '') + ' aria-label="Next page">' +
            '<span class="material-symbols-outlined" style="font-size:16px;">chevron_right</span></button>';

        return html + '</div></div>';
    }

    function bindPagination(root) {
        Array.prototype.forEach.call(root.querySelectorAll('[data-page]'), function (btn) {
            btn.addEventListener('click', function () {
                if (btn.disabled) return;
                var page = parseInt(btn.getAttribute('data-page'), 10);
                if (!isNaN(page) && page >= 1) {
                    state.page = page;
                    load();
                }
            });
        });
    }

    GenericListPage._retry = load;
    load();
}

/** Shared chrome + submit handling for the create and edit forms. */
function renderForm(config, item, mode, id) {
    var content = document.getElementById('admin-content');
    var singular = config.singular || config.title.replace(/ies$/, 'y').replace(/s$/, '');
    var heading = (mode === 'create' ? 'Add ' : 'Edit ') + singular;

    AdminApp.setTitle(heading);
    AdminApp.setActions(
        '<a href="#/' + config.route + '" class="btn btn-outline btn-sm">' +
        '<span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back</a>'
    );

    content.innerHTML =
        pageHeader(heading, config.description) +
        '<div class="card" style="overflow:visible;">' +
        '<form id="resource-form" novalidate>' +
        '<div class="card-body">' +
        '<div class="form-grid-2">' + config.formFields(item) + '</div>' +
        '<div id="form-alert" style="display:none;margin-top:16px;"></div>' +
        '</div>' +
        '<div class="form-actionbar">' +
        '<a href="#/' + config.route + '" class="btn btn-outline btn-sm">Cancel</a>' +
        '<button type="submit" class="btn btn-primary btn-sm" id="form-submit">' +
        '<span class="material-symbols-outlined" style="font-size:18px;">check</span> ' +
        (mode === 'create' ? 'Save' : 'Update') +
        '</button>' +
        '</div>' +
        '</form></div>';

    function attach() {
        var form = document.getElementById('resource-form');
        var submitBtn = document.getElementById('form-submit');
        var alertBox = document.getElementById('form-alert');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            Array.prototype.forEach.call(form.querySelectorAll('.form-error'), function (el) {
                el.style.display = 'none';
                el.textContent = '';
            });
            alertBox.style.display = 'none';

            var payload = {};
            Array.prototype.forEach.call(form.querySelectorAll('input, select, textarea'), function (input) {
                if (!input.name) return;
                payload[input.name] = (input.type === 'checkbox') ? (input.checked ? 1 : 0) : input.value;
            });

            if (window.Loading && window.Loading.isButtonLoading(submitBtn)) return;

            var originalHtml = submitBtn.innerHTML;
            if (window.Loading) {
                window.Loading.setButtonLoading(submitBtn, true, mode === 'create' ? 'Saving…' : 'Updating…');
            } else {
                submitBtn.disabled = true;
                submitBtn.innerHTML = mode === 'create' ? 'Saving…' : 'Updating…';
            }

            var request = (mode === 'create')
                ? Api.post(config.api, payload)
                : Api.put(config.api + '/' + id, payload);

            request.then(function () {
                showToast(singular + (mode === 'create' ? ' created' : ' updated') + ' successfully');
                Router.navigate(config.route);
            }).catch(function (err) {
                if (err.message === 'Session expired') return;

                // Field errors go next to their field; anything else in the banner.
                var fieldErrors = err.errors || {};
                var handled = 0;
                Object.keys(fieldErrors).forEach(function (field) {
                    var el = form.querySelector('[data-error-for="' + field + '"]');
                    if (el) {
                        el.textContent = Array.isArray(fieldErrors[field]) ? fieldErrors[field][0] : fieldErrors[field];
                        el.style.display = 'block';
                        handled++;
                    }
                });

                if (!handled) {
                    alertBox.innerHTML = errorState(err.message);
                    alertBox.style.display = 'block';
                }

                if (window.Loading) {
                    window.Loading.setButtonLoading(submitBtn, false);
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
            });
        });
    }

    // Dependent selects are rendered empty and filled once their data lands.
    if (config.formDataProvider) {
        config.formDataProvider(item).then(attach).catch(attach);
    } else {
        attach();
    }
}

function GenericCreatePage(config) {
    renderForm(config, null, 'create', null);
}

function GenericEditPage(config, id) {
    var content = document.getElementById('admin-content');
    AdminApp.setTitle('Edit');
    content.innerHTML = skeletonForm(6);

    Api.get(config.api + '/' + id).then(function (response) {
        var item = response.data || response;
        renderForm(config, item, 'edit', id);
    }).catch(function (err) {
        if (err.message === 'Session expired') return;
        content.innerHTML = errorState('Unable to load this record. ' + err.message);
    });
}

function GenericDelete(config, id) {
    var singular = (config.singular || config.title.replace(/ies$/, 'y').replace(/s$/, '')).toLowerCase();

    AdminApp.openModal(
        'Delete this ' + singular + '?',
        '<p style="font-size:13px;color:var(--color-text-secondary);line-height:1.6;">' +
        'This permanently removes the ' + escapeHtml(singular) + '. This cannot be undone.</p>',
        '<button type="button" class="btn btn-outline btn-sm" onclick="AdminApp.closeModal()">Cancel</button>' +
        '<button type="button" class="btn btn-danger btn-sm" id="confirm-delete">' +
        '<span class="material-symbols-outlined" style="font-size:18px;">delete</span> Delete</button>'
    );

    document.getElementById('confirm-delete').addEventListener('click', function () {
        var btn = this;
        if (window.Loading) {
            if (window.Loading.isButtonLoading(btn)) return;
            window.Loading.setButtonLoading(btn, true, 'Deleting…');
        } else {
            btn.disabled = true;
            btn.textContent = 'Deleting…';
        }

        Api['delete'](config.api + '/' + id).then(function () {
            AdminApp.closeModal();
            showToast('Deleted successfully');
            Router.resolve();
        }).catch(function (err) {
            if (err.message === 'Session expired') return;
            AdminApp.closeModal();
            showToast(err.message || 'Unable to delete this record', 'error');
        });
    });
}

/* ----------------------------------------------------------
   8. Resource configs
   ---------------------------------------------------------- */
var pages = {

    dashboard: { title: 'Dashboard', api: '/dashboard', route: 'dashboard', columns: [] },

    /* ---------- Content ---------- */
    courses: {
        title: 'Courses', api: '/courses', route: 'courses', singular: 'Course', icon: 'school',
        description: 'Manage courses, fees, duration and publishing status.',
        columns: [
            { key: 'id', label: 'ID', width: '60px', render: function (v) { return '<span class="mono" style="font-size:12px;color:var(--color-text-muted);">#' + v + '</span>'; } },
            { key: 'title', label: 'Course', render: function (v) { return '<div style="font-weight:600;font-size:14px;">' + escapeHtml(v) + '</div>'; } },
            { key: 'category', label: 'Category', render: function (v) { return muted(v ? v.name : null); } },
            { key: 'duration', label: 'Duration', render: function (v) { return muted(v); } },
            { key: 'fees', label: 'Fees', render: function (v) { return money(v); } },
            { key: 'status', label: 'Status', render: function (v) { return statusBadge(v); } }
        ],
        actions: function (row) { return rowActions('courses', 'courses', row.id); },
        formFields: function (item) {
            var c = item || {};
            return inputField('title', 'Course title', c.title, 'text', 'required') +
                selectField('category_id', 'Category', c.category_id, []) +
                inputField('duration', 'Duration', c.duration) +
                inputField('fees', 'Fees (₹)', c.fees, 'number') +
                textareaField('short_description', 'Short description', c.short_description) +
                textareaField('description', 'Full description', c.description) +
                statusSelect('status', 'Status', c.status, [
                    { value: 'published', label: 'Published' },
                    { value: 'draft', label: 'Draft' }
                ]);
        },
        formDataProvider: function (item) {
            return Api.get('/categories?per_page=100').then(function (res) {
                fillSelect('category_id', listOf(res), 'id', 'name', item ? item.category_id : null);
            });
        }
    },

    categories: {
        title: 'Categories', api: '/categories', route: 'categories', singular: 'Category', icon: 'category',
        description: 'Group courses so they are easier to browse.',
        columns: [
            { key: 'id', label: 'ID', width: '60px', render: function (v) { return '<span class="mono" style="font-size:12px;color:var(--color-text-muted);">#' + v + '</span>'; } },
            { key: 'name', label: 'Name', render: function (v) { return '<div style="font-weight:600;font-size:14px;">' + escapeHtml(v) + '</div>'; } },
            { key: 'slug', label: 'Slug', render: function (v) { return '<span class="mono" style="font-size:12px;color:var(--color-text-muted);">' + escapeHtml(v) + '</span>'; } },
            { key: 'status', label: 'Status', render: function (v) { return statusBadge(v); } }
        ],
        actions: function (row) { return rowActions('categories', 'categories', row.id); },
        formFields: function (item) {
            var c = item || {};
            return inputField('name', 'Category name', c.name, 'text', 'required') +
                inputField('slug', 'Slug', c.slug) +
                textareaField('description', 'Description', c.description) +
                statusSelect('status', 'Status', c.status, [
                    { value: 'published', label: 'Published' },
                    { value: 'draft', label: 'Draft' }
                ]);
        }
    },

    gallery: {
        title: 'Gallery', api: '/gallery', route: 'gallery', singular: 'Image', icon: 'photo_library',
        description: 'Photos shown on the public website.',
        canCreate: false,
        emptyHint: 'Upload images from the Blade gallery screen.',
        columns: [
            { key: 'id', label: 'ID', width: '60px', render: function (v) { return '<span class="mono" style="font-size:12px;color:var(--color-text-muted);">#' + v + '</span>'; } },
            { key: 'title', label: 'Title', render: function (v) { return '<div style="font-weight:600;font-size:14px;">' + escapeHtml(v || 'Untitled') + '</div>'; } },
            { key: 'category', label: 'Category', render: function (v) { return muted(v ? v.name : null); } },
            { key: 'is_active', label: 'Visible', render: function (v) { return v ? badge('Visible', 'success') : badge('Hidden', 'secondary'); } },
            { key: 'created_at', label: 'Added', render: dateCell }
        ],
        actions: function (row) { return rowActions('gallery', 'gallery', row.id); },
        formFields: function (item) {
            var c = item || {};
            return inputField('title', 'Title', c.title) +
                textareaField('description', 'Description', c.description);
        }
    },

    /* ---------- CRM ---------- */
    enquiries: {
        title: 'Enquiries', api: '/enquiries', route: 'enquiries', singular: 'Enquiry', icon: 'contact_page',
        description: 'Incoming enquiries from the website and walk-ins.',
        canCreate: false,
        filterOptions: [
            { value: 'new', label: 'New' },
            { value: 'contacted', label: 'Contacted' },
            { value: 'converted', label: 'Converted' },
            { value: 'closed', label: 'Closed' }
        ],
        columns: [
            { key: 'name', label: 'Name', render: function (v) { return '<div style="font-weight:600;font-size:14px;">' + escapeHtml(v) + '</div>'; } },
            { key: 'phone', label: 'Phone', render: function (v) { return '<span class="mono" style="font-size:13px;">' + escapeHtml(v) + '</span>'; } },
            { key: 'email', label: 'Email', render: function (v) { return muted(v); } },
            { key: 'subject', label: 'Subject', render: function (v) { return muted(v); } },
            { key: 'status', label: 'Status', render: function (v) { return statusBadge(v); } },
            { key: 'created_at', label: 'Received', render: dateCell }
        ],
        actions: function (row) { return rowActions('enquiries', 'enquiries', row.id); },
        formFields: function (item) {
            var c = item || {};
            return inputField('name', 'Name', c.name) +
                inputField('phone', 'Phone', c.phone, 'tel') +
                inputField('email', 'Email', c.email, 'email') +
                inputField('subject', 'Subject', c.subject) +
                textareaField('message', 'Message', c.message) +
                statusSelect('status', 'Status', c.status, [
                    { value: 'new', label: 'New' },
                    { value: 'contacted', label: 'Contacted' },
                    { value: 'converted', label: 'Converted' },
                    { value: 'closed', label: 'Closed' }
                ]);
        }
    },

    /* ---------- Academic ---------- */
    teachers: {
        title: 'Teachers', api: '/teachers', route: 'teachers', singular: 'Teacher', icon: 'groups',
        description: 'Trainers and their specialisations.',
        columns: [
            { key: 'id', label: 'ID', width: '60px', render: function (v) { return '<span class="mono" style="font-size:12px;color:var(--color-text-muted);">#' + v + '</span>'; } },
            { key: 'name', label: 'Name', render: function (v) { return '<div style="font-weight:600;font-size:14px;">' + escapeHtml(v) + '</div>'; } },
            { key: 'email', label: 'Email', render: function (v) { return muted(v); } },
            { key: 'phone', label: 'Phone', render: function (v) { return muted(v); } },
            { key: 'specialization', label: 'Specialisation', render: function (v) { return muted(v); } },
            { key: 'status', label: 'Status', render: function (v) { return statusBadge(v); } }
        ],
        actions: function (row) { return rowActions('teachers', 'teachers', row.id); },
        formFields: function (item) {
            var c = item || {};
            return inputField('name', 'Full name', c.name, 'text', 'required') +
                inputField('email', 'Email', c.email, 'email') +
                inputField('phone', 'Phone', c.phone, 'tel') +
                inputField('designation', 'Designation', c.designation) +
                inputField('qualification', 'Qualification', c.qualification) +
                inputField('specialization', 'Specialisation', c.specialization) +
                textareaField('bio', 'Bio', c.bio) +
                statusSelect('status', 'Status', c.status);
        }
    },

    batches: {
        title: 'Batches', api: '/batches', route: 'batches', singular: 'Batch', icon: 'calendar_month',
        description: 'Scheduled batches, their timings and capacity.',
        columns: [
            { key: 'batch_name', label: 'Batch', render: function (v) { return '<div style="font-weight:600;font-size:14px;">' + escapeHtml(v) + '</div>'; } },
            { key: 'course', label: 'Course', render: function (v) { return muted(v ? v.title : null); } },
            { key: 'start_date', label: 'Starts', render: dateCell },
            { key: 'end_date', label: 'Ends', render: dateCell },
            { key: 'capacity', label: 'Capacity', render: function (v, row) { return muted((row.available_seats != null ? row.available_seats + ' / ' : '') + (v || '—')); } },
            { key: 'status', label: 'Status', render: function (v) { return statusBadge(v); } }
        ],
        actions: function (row) { return rowActions('batches', 'batches', row.id); },
        formFields: function (item) {
            var c = item || {};
            return inputField('batch_name', 'Batch name', c.batch_name, 'text', 'required') +
                selectField('course_id', 'Course', c.course_id, []) +
                selectField('trainer_id', 'Trainer', c.trainer_id, []) +
                inputField('start_date', 'Start date', c.start_date, 'date') +
                inputField('end_date', 'End date', c.end_date, 'date') +
                inputField('start_time', 'Start time', c.start_time, 'time') +
                inputField('end_time', 'End time', c.end_time, 'time') +
                inputField('days', 'Days', c.days, 'text', 'placeholder="Monday,Wednesday,Friday"') +
                inputField('capacity', 'Capacity', c.capacity, 'number') +
                statusSelect('status', 'Status', c.status, [
                    { value: 'upcoming', label: 'Upcoming' },
                    { value: 'active', label: 'Active' },
                    { value: 'completed', label: 'Completed' },
                    { value: 'cancelled', label: 'Cancelled' }
                ]);
        },
        formDataProvider: function (item) {
            return Promise.all([
                Api.get('/courses?per_page=100'),
                Api.get('/teachers?per_page=100')
            ]).then(function (res) {
                fillSelect('course_id', listOf(res[0]), 'id', 'title', item ? item.course_id : null);
                fillSelect('trainer_id', listOf(res[1]), 'id', 'name', item ? item.trainer_id : null);
            });
        }
    },

    /* ---------- Students ---------- */
    students: {
        title: 'Students', api: '/students', route: 'students', singular: 'Student', icon: 'person',
        description: 'Enrolled students and their profiles.',
        canCreate: false,
        emptyHint: 'Students are created through the admissions flow.',
        columns: [
            { key: 'student_id', label: 'Code', render: function (v) { return '<span class="mono" style="font-size:12px;color:var(--color-text-muted);">' + escapeHtml(v) + '</span>'; } },
            { key: 'user', label: 'Name', render: function (v, row) { return '<div style="font-weight:600;font-size:14px;">' + escapeHtml(v ? v.name : (row.name || '—')) + '</div>'; } },
            { key: 'city', label: 'City', render: function (v) { return muted(v); } },
            { key: 'qualification', label: 'Qualification', render: function (v) { return muted(v); } },
            { key: 'status', label: 'Status', render: function (v) { return statusBadge(v); } }
        ],
        actions: function (row) {
            return '<div style="display:flex;gap:4px;justify-content:flex-end;">' +
                '<a href="#/students/edit/' + row.id + '" class="icon-btn icon-btn-edit" title="View">' +
                '<span class="material-symbols-outlined" style="font-size:18px;">visibility</span></a></div>';
        },
        formFields: function (item) {
            var c = item || {};
            return inputField('student_id', 'Student code', c.student_id, 'text', 'readonly') +
                inputField('city', 'City', c.city) +
                inputField('qualification', 'Qualification', c.qualification) +
                inputField('whatsapp', 'WhatsApp', c.whatsapp) +
                statusSelect('status', 'Status', c.status);
        }
    },

    /* ---------- Online learning ---------- */
    'online-classes': {
        title: 'Live Classes', api: '/online-classes', route: 'online-classes', singular: 'Class', icon: 'videocam',
        description: 'Scheduled live sessions and their meeting rooms.',
        columns: [
            { key: 'title', label: 'Class', render: function (v) { return '<div style="font-weight:600;font-size:14px;">' + escapeHtml(v) + '</div>'; } },
            { key: 'course', label: 'Course', render: function (v) { return muted(v ? v.title : null); } },
            { key: 'teacher', label: 'Instructor', render: function (v) { return muted(v ? v.name : null); } },
            { key: 'class_date', label: 'Date', render: dateCell },
            { key: 'start_time', label: 'Time', render: function (v, row) { return muted((v || '') + (row.end_time ? ' – ' + row.end_time : '')); } },
            { key: 'status', label: 'Status', render: function (v) { return statusBadge(v); } }
        ],
        actions: function (row) { return rowActions('online-classes', 'online-classes', row.id); },
        formFields: function (item) {
            var c = item || {};
            return inputField('title', 'Class title', c.title, 'text', 'required') +
                selectField('course_id', 'Course', c.course_id, []) +
                selectField('batch_id', 'Batch', c.batch_id, []) +
                selectField('teacher_id', 'Instructor', c.teacher_id, []) +
                inputField('class_date', 'Date', c.class_date ? String(c.class_date).substring(0, 10) : '', 'date') +
                inputField('start_time', 'Start time', c.start_time, 'time') +
                inputField('end_time', 'End time', c.end_time, 'time') +
                selectField('meeting_provider', 'Provider', c.meeting_provider, [
                    { value: 'google_meet', label: 'Google Meet' },
                    { value: 'zoom', label: 'Zoom' },
                    { value: 'teams', label: 'Microsoft Teams' },
                    { value: 'other', label: 'External link' }
                ]) +
                inputField('meeting_url', 'Meeting URL', c.meeting_url, 'url') +
                textareaField('description', 'Description', c.description) +
                statusSelect('status', 'Status', c.status, [
                    { value: 'scheduled', label: 'Scheduled' },
                    { value: 'starting_soon', label: 'Starting soon' },
                    { value: 'live', label: 'Live' },
                    { value: 'completed', label: 'Completed' },
                    { value: 'cancelled', label: 'Cancelled' },
                    { value: 'postponed', label: 'Postponed' }
                ]);
        },
        formDataProvider: function (item) {
            return Api.get('/online-classes/form-data').then(function (res) {
                fillSelect('course_id', res.courses || [], 'id', 'title', item ? item.course_id : null);
                fillSelect('batch_id', res.batches || [], 'id', 'batch_name', item ? item.batch_id : null);
                fillSelect('teacher_id', res.teachers || [], 'id', 'name', item ? item.teacher_id : null);
            });
        }
    },

    lessons: {
        title: 'Lessons', api: '/lessons', route: 'lessons', singular: 'Lesson', icon: 'menu_book',
        description: 'Course lesson plan and sequencing.',
        columns: [
            { key: 'title', label: 'Lesson', render: function (v) { return '<div style="font-weight:600;font-size:14px;">' + escapeHtml(v) + '</div>'; } },
            { key: 'course', label: 'Course', render: function (v) { return muted(v ? v.title : null); } },
            { key: 'sequence', label: 'Order', width: '80px', render: function (v) { return muted(v); } },
            { key: 'duration', label: 'Duration', render: function (v) { return muted(v ? v + ' min' : null); } },
            { key: 'is_published', label: 'Published', render: function (v) { return v ? badge('Published', 'success') : badge('Draft', 'draft'); } }
        ],
        actions: function (row) { return rowActions('lessons', 'lessons', row.id); },
        formFields: function (item) {
            var c = item || {};
            return inputField('title', 'Lesson title', c.title, 'text', 'required') +
                selectField('course_id', 'Course', c.course_id, []) +
                inputField('sequence', 'Order', c.sequence, 'number') +
                inputField('duration', 'Duration (minutes)', c.duration, 'number') +
                textareaField('description', 'Description', c.description);
        },
        formDataProvider: function (item) {
            return Api.get('/courses?per_page=100').then(function (res) {
                fillSelect('course_id', listOf(res), 'id', 'title', item ? item.course_id : null);
            });
        }
    },

    materials: {
        title: 'Materials', api: '/materials', route: 'materials', singular: 'Material', icon: 'folder_open',
        description: 'Files and links shared with students.',
        columns: [
            { key: 'title', label: 'Material', render: function (v) { return '<div style="font-weight:600;font-size:14px;">' + escapeHtml(v) + '</div>'; } },
            { key: 'course', label: 'Course', render: function (v) { return muted(v ? v.title : null); } },
            { key: 'file_type', label: 'Type', render: function (v) { return v ? badge(String(v).toUpperCase(), 'info') : muted(null); } },
            { key: 'download_count', label: 'Downloads', render: function (v) { return muted(v != null ? String(v) : '0'); } },
            { key: 'is_published', label: 'Published', render: function (v) { return v ? badge('Published', 'success') : badge('Draft', 'draft'); } }
        ],
        actions: function (row) { return rowActions('materials', 'materials', row.id); },
        formFields: function (item) {
            var c = item || {};
            return inputField('title', 'Title', c.title, 'text', 'required') +
                selectField('course_id', 'Course', c.course_id, []) +
                selectField('file_type', 'File type', c.file_type, [
                    { value: 'pdf', label: 'PDF' }, { value: 'ppt', label: 'Presentation' },
                    { value: 'doc', label: 'Document' }, { value: 'xls', label: 'Spreadsheet' },
                    { value: 'zip', label: 'Archive' }, { value: 'image', label: 'Image' },
                    { value: 'link', label: 'Link' }, { value: 'other', label: 'Other' }
                ]) +
                inputField('external_url', 'External URL', c.external_url, 'url') +
                textareaField('description', 'Description', c.description);
        },
        formDataProvider: function (item) {
            return Api.get('/courses?per_page=100').then(function (res) {
                fillSelect('course_id', listOf(res), 'id', 'title', item ? item.course_id : null);
            });
        }
    },

    assignments: {
        title: 'Assignments', api: '/assignments', route: 'assignments', singular: 'Assignment', icon: 'assignment',
        description: 'Work set for students, with due dates and marks.',
        columns: [
            { key: 'title', label: 'Assignment', render: function (v) { return '<div style="font-weight:600;font-size:14px;">' + escapeHtml(v) + '</div>'; } },
            { key: 'course', label: 'Course', render: function (v) { return muted(v ? v.title : null); } },
            { key: 'due_date', label: 'Due', render: dateCell },
            { key: 'max_marks', label: 'Marks', width: '90px', render: function (v) { return muted(v != null ? String(v) : null); } },
            { key: 'status', label: 'Status', render: function (v) { return statusBadge(v); } }
        ],
        actions: function (row) { return rowActions('assignments', 'assignments', row.id); },
        formFields: function (item) {
            var c = item || {};
            return inputField('title', 'Assignment title', c.title, 'text', 'required') +
                selectField('course_id', 'Course', c.course_id, []) +
                selectField('teacher_id', 'Set by', c.teacher_id, []) +
                inputField('due_date', 'Due date', c.due_date ? String(c.due_date).substring(0, 10) : '', 'date') +
                inputField('max_marks', 'Maximum marks', c.max_marks, 'number') +
                textareaField('description', 'Description', c.description) +
                textareaField('instructions', 'Instructions', c.instructions) +
                statusSelect('status', 'Status', c.status, [
                    { value: 'draft', label: 'Draft' },
                    { value: 'published', label: 'Published' },
                    { value: 'closed', label: 'Closed' }
                ]);
        },
        formDataProvider: function (item) {
            return Promise.all([
                Api.get('/courses?per_page=100'),
                Api.get('/teachers?per_page=100')
            ]).then(function (res) {
                fillSelect('course_id', listOf(res[0]), 'id', 'title', item ? item.course_id : null);
                fillSelect('teacher_id', listOf(res[1]), 'id', 'name', item ? item.teacher_id : null);
            });
        }
    },

    quizzes: {
        title: 'Quizzes', api: '/quizzes', route: 'quizzes', singular: 'Quiz', icon: 'quiz',
        description: 'Assessments, their pass marks and time limits.',
        columns: [
            { key: 'title', label: 'Quiz', render: function (v) { return '<div style="font-weight:600;font-size:14px;">' + escapeHtml(v) + '</div>'; } },
            { key: 'course', label: 'Course', render: function (v) { return muted(v ? v.title : null); } },
            { key: 'total_marks', label: 'Total', width: '90px', render: function (v) { return muted(v != null ? String(v) : null); } },
            { key: 'passing_marks', label: 'Pass', width: '90px', render: function (v) { return muted(v != null ? String(v) : null); } },
            { key: 'status', label: 'Status', render: function (v) { return statusBadge(v); } }
        ],
        actions: function (row) { return rowActions('quizzes', 'quizzes', row.id); },
        formFields: function (item) {
            var c = item || {};
            return inputField('title', 'Quiz title', c.title, 'text', 'required') +
                selectField('course_id', 'Course', c.course_id, []) +
                inputField('total_marks', 'Total marks', c.total_marks, 'number') +
                inputField('passing_marks', 'Passing marks', c.passing_marks, 'number') +
                inputField('duration', 'Time limit (minutes)', c.duration, 'number') +
                textareaField('description', 'Description', c.description) +
                statusSelect('status', 'Status', c.status, [
                    { value: 'draft', label: 'Draft' },
                    { value: 'published', label: 'Published' },
                    { value: 'closed', label: 'Closed' }
                ]);
        },
        formDataProvider: function (item) {
            return Api.get('/courses?per_page=100').then(function (res) {
                fillSelect('course_id', listOf(res), 'id', 'title', item ? item.course_id : null);
            });
        }
    },

    recordings: {
        title: 'Recordings', api: '/recordings', route: 'recordings', singular: 'Recording', icon: 'video_library',
        description: 'Session recordings and who can watch them.',
        columns: [
            { key: 'title', label: 'Recording', render: function (v) { return '<div style="font-weight:600;font-size:14px;">' + escapeHtml(v) + '</div>'; } },
            { key: 'duration', label: 'Length', render: function (v) { return muted(v ? v + ' min' : null); } },
            { key: 'view_count', label: 'Views', width: '90px', render: function (v) { return muted(v != null ? String(v) : '0'); } },
            { key: 'visibility', label: 'Visibility', render: function (v) { return badge(v === 'public' ? 'Public' : 'Enrolled', v === 'public' ? 'info' : 'secondary'); } },
            { key: 'is_published', label: 'Published', render: function (v) { return v ? badge('Published', 'success') : badge('Draft', 'draft'); } }
        ],
        actions: function (row) { return rowActions('recordings', 'recordings', row.id); },
        formFields: function (item) {
            var c = item || {};
            return inputField('title', 'Recording title', c.title, 'text', 'required') +
                inputField('recording_url', 'Recording URL', c.recording_url, 'url', 'required') +
                inputField('duration', 'Duration (minutes)', c.duration, 'number') +
                selectField('visibility', 'Visibility', c.visibility, [
                    { value: 'enrolled', label: 'Enrolled students only' },
                    { value: 'public', label: 'Public' }
                ]) +
                textareaField('description', 'Description', c.description);
        }
    }
};

/* ----------------------------------------------------------
   9. Live Classroom Dashboard
   ---------------------------------------------------------- */
function LiveClassroomPage() {
    var content = document.getElementById('admin-content');
    AdminApp.setTitle('Live Classroom');
    AdminApp.setActions('');

    content.innerHTML =
        '<div class="page-header" style="margin-bottom:24px;">' +
        '<h2 style="font-size:20px;font-weight:700;color:#f1f5f9;margin:0 0 4px;">Live Classroom</h2>' +
        '<p style="font-size:13px;color:#94a3b8;margin:0;">Join and manage live class sessions with real-time video.</p>' +
        '</div>' +
        '<div id="live-classes-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px;">' +
        '<div class="skeleton" style="height:180px;border-radius:16px;"></div>' +
        '<div class="skeleton" style="height:180px;border-radius:16px;"></div>' +
        '<div class="skeleton" style="height:180px;border-radius:16px;"></div>' +
        '</div>';

    Api.get('/online-classes').then(function (res) {
        var classes = (res && res.data) ? (res.data.data || res.data) : [];
        renderLiveClassGrid(classes);
    }).catch(function () {
        content.innerHTML =
            '<div style="text-align:center;padding:60px 20px;">' +
            '<span class="material-symbols-outlined" style="font-size:48px;color:#475569;">error</span>' +
            '<p style="color:#94a3b8;margin-top:12px;font-size:14px;">Failed to load classes.</p>' +
            '<button onclick="LiveClassroomPage()" style="margin-top:12px;padding:8px 16px;border-radius:8px;background:#6366f1;color:white;border:none;cursor:pointer;font-size:13px;">Retry</button>' +
            '</div>';
    });
}

function renderLiveClassGrid(classes) {
    var grid = document.getElementById('live-classes-grid');
    if (!grid) return;

    if (!classes || classes.length === 0) {
        grid.innerHTML =
            '<div style="grid-column:1/-1;text-align:center;padding:60px 20px;">' +
            '<span class="material-symbols-outlined" style="font-size:48px;color:#475569;">videocam_off</span>' +
            '<p style="color:#94a3b8;margin-top:12px;font-size:14px;">No classes scheduled.</p>' +
            '<a href="#/online-classes/create" style="display:inline-block;margin-top:12px;padding:8px 16px;border-radius:8px;background:#6366f1;color:white;text-decoration:none;font-size:13px;">Schedule a Class</a>' +
            '</div>';
        return;
    }

    var html = '';
    classes.forEach(function (cls) {
        var statusColors = {
            live: { bg: '#059669', text: '#ecfdf5', label: 'LIVE', pulse: true },
            scheduled: { bg: '#1e40af', text: '#dbeafe', label: 'Scheduled', pulse: false },
            starting_soon: { bg: '#d97706', text: '#fef3c7', label: 'Starting Soon', pulse: true },
            completed: { bg: '#475569', text: '#e2e8f0', label: 'Completed', pulse: false },
            cancelled: { bg: '#991b1b', text: '#fee2e2', label: 'Cancelled', pulse: false },
            postponed: { bg: '#6b21a8', text: '#f3e8ff', label: 'Postponed', pulse: false }
        };
        var sc = statusColors[cls.status] || statusColors.scheduled;
        var isLive = cls.status === 'live';
        var isJoinable = cls.status === 'live' || cls.status === 'starting_soon' || cls.status === 'scheduled';
        var dateStr = cls.class_date ? new Date(cls.class_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
        var timeStr = cls.start_time || '';
        if (cls.end_time) timeStr += ' – ' + cls.end_time;

        html +=
            '<div class="live-class-card" style="background:#0f172a;border:1px solid ' + (isLive ? '#059669' : '#1e293b') + ';border-radius:16px;overflow:hidden;transition:all 0.2s;' + (isLive ? 'box-shadow:0 0 20px rgba(5,150,105,0.15);' : '') + '">' +
            '<div style="padding:20px;">' +
            '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">' +
            '<span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;background:' + sc.bg + ';color:' + sc.text + ';">' +
            (sc.pulse ? '<span style="width:6px;height:6px;border-radius:50%;background:currentColor;animation:pulse 1.5s infinite;"></span>' : '') +
            sc.label +
            '</span>' +
            '<span style="font-size:11px;color:#64748b;">' + escapeHtml(dateStr) + '</span>' +
            '</div>' +
            '<h3 style="font-size:16px;font-weight:700;color:#f1f5f9;margin:0 0 6px;line-height:1.3;">' + escapeHtml(cls.title || 'Untitled') + '</h3>' +
            '<div style="font-size:12px;color:#94a3b8;margin-bottom:4px;">' +
            '<span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">schedule</span> ' + escapeHtml(timeStr) +
            '</div>' +
            (cls.course ? '<div style="font-size:12px;color:#94a3b8;margin-bottom:4px;"><span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">school</span> ' + escapeHtml(cls.course.title || '') + '</div>' : '') +
            (cls.teacher ? '<div style="font-size:12px;color:#94a3b8;margin-bottom:12px;"><span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">person</span> ' + escapeHtml(cls.teacher.name || '') + '</div>' : '') +
            '<div style="display:flex;gap:8px;margin-top:16px;">' +
            (isJoinable ?
                '<button onclick="joinLiveClassroom(' + cls.id + ')" style="flex:1;padding:10px;border-radius:10px;border:none;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all 0.15s;' +
                (isLive ? 'background:#059669;color:white;' : 'background:#6366f1;color:white;') + '">' +
                '<span class="material-symbols-outlined" style="font-size:18px;">' + (isLive ? 'videocam' : 'login') + '</span>' +
                (isLive ? 'Join Now' : 'Enter Lobby') +
                '</button>' : '') +
            '<a href="#/online-classes/edit/' + cls.id + '" style="padding:10px 14px;border-radius:10px;border:1px solid #334155;background:transparent;color:#94a3b8;font-size:13px;text-decoration:none;display:flex;align-items:center;justify-content:center;transition:all 0.15s;" onmouseover="this.style.borderColor=\'#6366f1\';this.style.color=\'#a5b4fc\'" onmouseout="this.style.borderColor=\'#334155\';this.style.color=\'#94a3b8\'">' +
            '<span class="material-symbols-outlined" style="font-size:18px;">edit</span>' +
            '</a>' +
            '</div>' +
            '</div></div>';
    });

    grid.innerHTML = html;
}

function joinLiveClassroom(classId) {
    var baseUrl = (window.AdminConfig && window.AdminConfig.baseUrl) || '';
    window.open(baseUrl + '/learning/room/resolve/' + classId, '_blank');
}

/* ----------------------------------------------------------
   10. Dashboard
   ---------------------------------------------------------- */
function DashboardPage() {
    var content = document.getElementById('admin-content');
    AdminApp.setTitle('Dashboard');
    AdminApp.setActions('');

    var userName = (window.AdminConfig && window.AdminConfig.user && window.AdminConfig.user.name) || 'Admin';
    var today = new Date().toLocaleDateString('en-IN', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });

    content.innerHTML =
        '<div class="dashboard-welcome"><div class="dashboard-welcome-content">' +
        '<div class="dashboard-welcome-head">' +
        '<div>' +
        '<h1>Welcome back, ' + escapeHtml(userName) + '</h1>' +
        '<p>Here\'s what\'s happening with your institute today.</p>' +
        '</div>' +
        '<span class="dashboard-welcome-date">' +
        '<span class="material-symbols-outlined">calendar_today</span>' + escapeHtml(today) +
        '</span>' +
        '</div>' +
        '</div></div>' +
        '<div id="dash-stats">' + skeletonStats(6) + '</div>' +
        '<div id="dash-panels" class="dash-grid"></div>';

    Api.get('/dashboard').then(function (data) {
        var stats = [
            { icon: 'person', tone: 'primary', value: data.totalStudents || 0, label: 'Total Students', link: '#/students' },
            { icon: 'groups', tone: 'warning', value: data.totalTeachers || 0, label: 'Total Teachers', link: '#/teachers' },
            { icon: 'calendar_month', tone: 'info', value: data.activeBatches || 0, label: 'Active Batches', link: '#/batches' },
            { icon: 'paid', tone: 'success', value: '₹' + Number(data.totalRevenue || 0).toLocaleString('en-IN'), label: 'Total Revenue' },
            { icon: 'how_to_reg', tone: 'primary', value: data.thisMonthAdmissions || 0, label: 'Admissions This Month', link: '#/students' },
            { icon: 'pending', tone: 'warning', value: data.pendingEnquiries || 0, label: 'Pending Enquiries', link: '#/enquiries' }
        ];

        var statsHost = document.getElementById('dash-stats');
        if (statsHost) {
            statsHost.className = 'stats-bar';
            statsHost.innerHTML = stats.map(function (s) {
                var body =
                    '<div class="stat-item-icon ' + s.tone + '"><span class="material-symbols-outlined">' + s.icon + '</span></div>' +
                    '<div><div class="stat-item-value">' + escapeHtml(String(s.value)) + '</div>' +
                    '<div class="stat-item-label">' + escapeHtml(s.label) + '</div></div>' +
                    (s.link ? '<span class="material-symbols-outlined stat-arrow">arrow_forward</span>' : '');
                return s.link
                    ? '<a class="stat-item" href="' + s.link + '">' + body + '</a>'
                    : '<div class="stat-item">' + body + '</div>';
            }).join('');
        }

        var payments = data.recentPayments || data.recent_payments || [];
        var enquiries = data.recentEnquiries || data.recent_enquiries || [];

        // Only surface shortcuts the signed-in user can actually reach.
        var quickActions = [
            { href: '#/courses/create', icon: 'library_add', label: 'Add Course', slug: 'courses' },
            { href: '#/teachers/create', icon: 'person_add', label: 'Add Teacher', slug: 'teachers' },
            { href: '#/batches/create', icon: 'event_available', label: 'New Batch', slug: 'batches' },
            { href: '#/online-classes/create', icon: 'videocam', label: 'Schedule Class', slug: 'online-classes' },
            { href: '#/students', icon: 'groups', label: 'View Students', slug: 'students' },
            { href: '#/enquiries', icon: 'mark_email_unread', label: 'Check Enquiries', slug: 'enquiries' }
        ].filter(function (a) { return sidebarHas(a.slug); });

        var panelsHost = document.getElementById('dash-panels');
        if (panelsHost) {
            panelsHost.innerHTML =
                quickActionsPanel(quickActions) +
                listPanel({
                    title: 'Recent Payments', icon: 'payments', items: payments,
                    emptyText: 'Payment records will appear here.',
                    rowFn: function (p) {
                        var name = (p.student && p.student.user && p.student.user.name) || p.name || 'N/A';
                        return listRow('paid', 'var(--color-success)', name, p.payment_id || '',
                            '<p style="font-size:14px;font-weight:600;color:var(--color-success);font-family:\'JetBrains Mono\',monospace;">₹' +
                            Number(p.amount || 0).toLocaleString('en-IN') + '</p>');
                    }
                }) +
                listPanel({
                    title: 'Recent Enquiries', icon: 'contact_page', items: enquiries,
                    emptyText: 'New enquiries will appear here.', viewHref: '#/enquiries',
                    rowFn: function (e) {
                        return listRow('contact_page', 'var(--color-primary)', e.name || 'N/A', e.phone || e.email || '',
                            statusBadge(e.status));
                    }
                });
        }
    }).catch(function (err) {
        if (err.message === 'Session expired') return;
        content.innerHTML = errorState('Unable to load the dashboard. ' + err.message, 'DashboardPage');
    });

    function quickActionsPanel(actions) {
        if (!actions.length) return '';
        return '<div class="card">' +
            '<div class="dashboard-section-header"><h3>Quick Actions</h3></div>' +
            '<div class="dash-quick-grid">' +
            actions.map(function (a) {
                return '<a href="' + a.href + '" class="dash-quick-tile">' +
                    '<span class="material-symbols-outlined">' + a.icon + '</span>' +
                    '<span>' + escapeHtml(a.label) + '</span></a>';
            }).join('') +
            '</div></div>';
    }

    function listPanel(opts) {
        var items = opts.items || [];
        var header = '<div class="dashboard-section-header"><h3>' + escapeHtml(opts.title) + '</h3>';
        if (opts.viewHref) {
            header += '<a href="' + opts.viewHref + '">View all ' +
                '<span class="material-symbols-outlined" style="font-size:15px;vertical-align:-3px;">arrow_forward</span></a>';
        } else if (items.length) {
            header += '<span class="dash-panel-count">' + items.length + '</span>';
        }
        header += '</div>';

        var html = '<div class="card">' + header;
        if (!items.length) {
            html += '<div class="empty-state" style="padding:32px;">' +
                '<span class="material-symbols-outlined" style="font-size:40px;">' + opts.icon + '</span>' +
                '<h3>Nothing yet</h3><p>' + escapeHtml(opts.emptyText) + '</p></div>';
        } else {
            items.slice(0, 5).forEach(function (item) { html += opts.rowFn(item); });
        }
        return html + '</div>';
    }

    function listRow(icon, tone, primary, secondary, rightHtml) {
        return '<div class="dashboard-list-item">' +
            '<div class="dashboard-list-icon" style="background:' + tone + ';">' +
            '<span class="material-symbols-outlined" style="font-size:18px;">' + icon + '</span></div>' +
            '<div class="dashboard-list-content">' +
            '<p>' + escapeHtml(primary) + '</p>' +
            '<p>' + escapeHtml(secondary) + '</p>' +
            '</div>' +
            '<div class="dashboard-list-right">' + (rightHtml || '') + '</div>' +
            '</div>';
    }
}

/**
 * True when the signed-in user's sidebar exposes `slug`. Before the sidebar
 * has loaded (NAV_SECTIONS === null) we optimistically allow the link so the
 * dashboard still offers shortcuts if the sidebar API is slow.
 */
function sidebarHas(slug) {
    if (!NAV_SECTIONS) return true;
    return NAV_SECTIONS.some(function (section) {
        return (section.children || []).some(function (child) { return child.slug === slug; });
    });
}

/* ----------------------------------------------------------
   10. Boot
   ---------------------------------------------------------- */

/**
 * Delegated account/dropdown menus. Delegated so it also covers menus inside
 * content swapped by the partial navigation. On Blade pages the same markup is
 * handled by public/js/admin-widgets.js; here it drives the SPA top bar.
 */
function closeSpaMenus() {
    Array.prototype.forEach.call(document.querySelectorAll('.action-dropdown.open'), function (dropdown) {
        dropdown.classList.remove('open');
        var menu = dropdown.closest('.action-menu');
        var trigger = menu ? menu.querySelector('.action-menu-trigger') : null;
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
    });
}

document.addEventListener('click', function (e) {
    var trigger = e.target.closest('.action-menu-trigger');

    if (trigger && !trigger.onclick) {
        var menu = trigger.closest('.action-menu');
        var dropdown = menu ? menu.querySelector('.action-dropdown') : null;
        if (dropdown) {
            var willOpen = !dropdown.classList.contains('open');
            closeSpaMenus();
            if (willOpen) dropdown.classList.add('open');
            trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            return;
        }
    }

    if (!e.target.closest('.action-menu')) closeSpaMenus();
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSpaMenus();
});

document.addEventListener('DOMContentLoaded', function () {
    AdminApp.init();
});
