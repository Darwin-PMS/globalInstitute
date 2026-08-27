/* ============================================================
   admin.js - Admin SPA Engine
   ============================================================ */

/* ----------------------------------------------------------
   1. API Helper
   ---------------------------------------------------------- */
var Api = {
    get: function(url) {
        return fetch('/api/admin' + url, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function(res) {
            if (res.status === 401) { AdminApp.showLogin(); throw new Error('Unauthorized'); }
            if (!res.ok) { return res.json().then(function(e) { throw e; }); }
            return res.json();
        });
    },
    post: function(url, data) {
        return fetch('/api/admin' + url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: JSON.stringify(data)
        }).then(function(res) {
            if (res.status === 401) { AdminApp.showLogin(); throw new Error('Unauthorized'); }
            if (!res.ok) { return res.json().then(function(e) { throw e; }); }
            return res.json();
        });
    },
    put: function(url, data) {
        return fetch('/api/admin' + url, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: JSON.stringify(data)
        }).then(function(res) {
            if (res.status === 401) { AdminApp.showLogin(); throw new Error('Unauthorized'); }
            if (!res.ok) { return res.json().then(function(e) { throw e; }); }
            return res.json();
        });
    },
    'delete': function(url) {
        return fetch('/api/admin' + url, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function(res) {
            if (res.status === 401) { AdminApp.showLogin(); throw new Error('Unauthorized'); }
            if (!res.ok) { return res.json().then(function(e) { throw e; }); }
            return res.json();
        });
    }
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
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:10000;display:flex;flex-direction:column;gap:10px;';
        document.body.appendChild(container);
    }
    var toast = document.createElement('div');
    var bgColor = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6';
    toast.style.cssText = 'padding:12px 20px;border-radius:8px;color:#fff;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,0.15);opacity:0;transition:opacity 0.3s;background:' + bgColor + ';';
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(function() { toast.style.opacity = '1'; }, 10);
    setTimeout(function() {
        toast.style.opacity = '0';
        setTimeout(function() { toast.remove(); }, 300);
    }, 3000);
}

/* ----------------------------------------------------------
   3. Helpers
   ---------------------------------------------------------- */
function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function badge(text, color) {
    color = color || '#6b7280';
    return '<span style="display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;background:' + color + ';color:#fff;">' + escapeHtml(String(text)) + '</span>';
}

function statusBadge(status) {
    var colors = {
        active: '#10b981', inactive: '#ef4444', pending: '#f59e0b',
        approved: '#10b981', rejected: '#ef4444', completed: '#3b82f6',
        cancelled: '#6b7280', paid: '#10b981', unpaid: '#ef4444',
        ongoing: '#3b82f6', scheduled: '#8b5cf6', published: '#10b981',
        draft: '#6b7280', featured: '#f59e0b', beginner: '#10b981',
        intermediate: '#f59e0b', advanced: '#ef4444', new: '#3b82f6',
        contacted: '#8b5cf6', converted: '#10b981', closed: '#6b7280'
    };
    var s = String(status).toLowerCase();
    return badge(status, colors[s] || '#6b7280');
}

function inputField(name, label, value, type, attrs) {
    type = type || 'text';
    attrs = attrs || '';
    var val = value !== null && value !== undefined ? escapeHtml(String(value)) : '';
    return '<div class="mb-3"><label class="form-label fw-semibold">' + escapeHtml(label) + '</label>' +
        '<input type="' + type + '" class="form-control" name="' + name + '" value="' + val + '" ' + attrs + '></div>';
}

function selectField(name, label, value, options, attrs) {
    attrs = attrs || '';
    var html = '<div class="mb-3"><label class="form-label fw-semibold">' + escapeHtml(label) + '</label>' +
        '<select class="form-select" name="' + name + '" ' + attrs + '>';
    html += '<option value="">-- Select --</option>';
    if (options && options.length) {
        for (var i = 0; i < options.length; i++) {
            var opt = options[i];
            var optVal = typeof opt === 'object' ? opt.value : opt;
            var optLabel = typeof opt === 'object' ? opt.label : opt;
            var selected = String(value) === String(optVal) ? ' selected' : '';
            html += '<option value="' + escapeHtml(String(optVal)) + '"' + selected + '>' + escapeHtml(String(optLabel)) + '</option>';
        }
    }
    html += '</select></div>';
    return html;
}

function textareaField(name, label, value, attrs) {
    attrs = attrs || '';
    var val = value !== null && value !== undefined ? escapeHtml(String(value)) : '';
    return '<div class="mb-3"><label class="form-label fw-semibold">' + escapeHtml(label) + '</label>' +
        '<textarea class="form-control" name="' + name + '" rows="4" ' + attrs + '>' + val + '</textarea></div>';
}

function statusSelect(name, label, value) {
    var statuses = [
        { value: 'active', label: 'Active' },
        { value: 'inactive', label: 'Inactive' },
        { value: 'pending', label: 'Pending' }
    ];
    return selectField(name, label || 'Status', value, statuses);
}
/* ----------------------------------------------------------
   4. Router
   ---------------------------------------------------------- */
var Router = {
    routes: {},
    current: null,

    add: function(path, handler) {
        Router.routes[path] = handler;
    },

    resolve: function() {
        var hash = window.location.hash || '#/';
        var raw = hash.replace(/^#\/?/, '');
        var parts = raw.split('/');
        var page = parts[0] || 'dashboard';
        var id = parts[1] || null;

        if (Router.routes[page]) {
            Router.current = page;
            if (id) {
                Router.routes[page](id);
            } else {
                Router.routes[page]();
            }
            Router.updateSidebar(page);
        } else if (Router.routes['dashboard']) {
            Router.current = 'dashboard';
            Router.routes['dashboard']();
            Router.updateSidebar('dashboard');
        }
    },

    navigate: function(path) {
        window.location.hash = '#/' + path;
    },

    updateSidebar: function(active) {
        var links = document.querySelectorAll('#sidebar .nav-link');
        for (var i = 0; i < links.length; i++) {
            var link = links[i];
            var route = link.getAttribute('data-route');
            if (route === active) {
                link.classList.add('active');
                link.style.background = 'rgba(255,255,255,0.1)';
            } else {
                link.classList.remove('active');
                link.style.background = 'transparent';
            }
        }
    },

    init: function() {
        window.addEventListener('hashchange', Router.resolve);
        Router.resolve();
    }
};

/* ----------------------------------------------------------
   5. AdminApp
   ---------------------------------------------------------- */
var AdminApp = {
    init: function() {
        var token = localStorage.getItem('admin_token');
        if (token) {
            AdminApp.showApp();
        } else {
            AdminApp.showLogin();
        }
    },

    showLogin: function() {
        var loginEl = document.getElementById('admin-login');
        var appEl = document.getElementById('admin-app');
        if (loginEl) loginEl.style.display = 'flex';
        if (appEl) appEl.style.display = 'none';
    },

    showApp: function() {
        var loginEl = document.getElementById('admin-login');
        var appEl = document.getElementById('admin-app');
        if (loginEl) loginEl.style.display = 'none';
        if (appEl) appEl.style.display = 'flex';
        AdminApp.renderSidebar();
        Router.init();
    },

    login: function(e) {
        e.preventDefault();
        var form = e.target;
        var email = form.querySelector('[name="email"]').value;
        var password = form.querySelector('[name="password"]').value;
        var btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Logging in...';

        fetch('/api/admin/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: JSON.stringify({ email: email, password: password })
        }).then(function(res) {
            return res.json().then(function(data) {
                if (!res.ok) throw data;
                return data;
            });
        }).then(function(data) {
            var token = data.token || (data.data && data.data.token);
            localStorage.setItem('admin_token', token);
            var user = data.admin || (data.data && data.data.admin);
            if (user) localStorage.setItem('admin_user', JSON.stringify(user));
            showToast('Login successful');
            AdminApp.showApp();
        }).catch(function(err) {
            showToast(err.message || 'Login failed', 'error');
        }).finally(function() {
            btn.disabled = false;
            btn.textContent = 'Login';
        });
    },

    logout: function() {
        localStorage.removeItem('admin_token');
        localStorage.removeItem('admin_user');
        window.location.hash = '#/';
        AdminApp.showLogin();
        showToast('Logged out');
    },

    toggleSidebar: function() {
        var sidebar = document.getElementById('sidebar');
        if (sidebar) sidebar.classList.toggle('collapsed');
    },

    renderSidebar: function() {
        var sidebar = document.getElementById('sidebar');
        if (!sidebar) return;

        var sections = [
            { title: 'Main', items: [
                { route: 'dashboard', label: 'Dashboard', icon: 'dashboard' }
            ]},
            { title: 'Content', items: [
                { route: 'courses', label: 'Courses', icon: 'school' },
                { route: 'categories', label: 'Categories', icon: 'category' },
                { route: 'lessons', label: 'Lessons', icon: 'menu_book' },
                { route: 'materials', label: 'Materials', icon: 'folder_open' },
                { route: 'assignments', label: 'Assignments', icon: 'assignment' },
                { route: 'quizzes', label: 'Quizzes', icon: 'quiz' }
            ]},
            { title: 'CRM', items: [
                { route: 'enquiries', label: 'Enquiries', icon: 'contact_page' }
            ]},
            { title: 'Academic', items: [
                { route: 'teachers', label: 'Teachers', icon: 'groups' },
                { route: 'batches', label: 'Batches', icon: 'calendar_month' }
            ]},
            { title: 'Online Learning', items: [
                { route: 'online-classes', label: 'Online Classes', icon: 'videocam' },
                { route: 'recordings', label: 'Recordings', icon: 'video_library' }
            ]},
            { title: 'Students', items: [
                { route: 'students', label: 'Students', icon: 'person' },
                { route: 'progress', label: 'Progress', icon: 'trending_up' }
            ]}
        ];

        var html = '<div class="sidebar-header d-flex align-items-center justify-content-between px-3 py-3">' +
            '<h5 class="mb-0 fw-bold text-white">Admin Panel</h5>' +
            '<button class="btn btn-sm btn-outline-secondary d-md-none" onclick="AdminApp.toggleSidebar()"><span class="material-symbols-outlined">close</span></button>' +
            '</div>';

        for (var s = 0; s < sections.length; s++) {
            var sec = sections[s];
            html += '<div class="sidebar-section">';
            html += '<div class="px-3 py-2 text-uppercase fw-bold" style="font-size:11px;color:#94a3b8;letter-spacing:1px;">' + sec.title + '</div>';
            html += '<ul class="nav flex-column mb-2">';
            for (var i = 0; i < sec.items.length; i++) {
                var item = sec.items[i];
                html += '<li class="nav-item">' +
                    '<a class="nav-link" data-route="' + item.route + '" href="#/' + item.route + '" style="color:#cbd5e1;text-decoration:none;padding:8px 16px;display:flex;align-items:center;gap:10px;font-size:14px;transition:background 0.2s;border-radius:6px;margin:2px 8px;">' +
                    '<span class="material-symbols-outlined">' + item.icon + '</span> ' + item.label +
                    '</a></li>';
            }
            html += '</ul></div>';
        }

        html += '<div class="mt-auto px-3 py-3">' +
            '<a href="javascript:void(0)" onclick="AdminApp.logout()" class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-box-arrow-left me-1"></i> Logout</a>' +
            '</div>';

        sidebar.innerHTML = html;
    },

    setTitle: function(title) {
        var el = document.getElementById('page-title');
        if (el) el.textContent = title;
    },

    openModal: function(title, bodyHtml) {
        var modal = document.getElementById('adminModal');
        if (!modal) return;
        modal.querySelector('.modal-title').textContent = title;
        modal.querySelector('.modal-body').innerHTML = bodyHtml;
        var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
        bsModal.show();
    },

    closeModal: function() {
        var modal = document.getElementById('adminModal');
        if (modal) {
            var bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
        }
    }
};
/* ----------------------------------------------------------
   6. Generic CRUD
   ---------------------------------------------------------- */
function GenericListPage(config) {
    var content = document.getElementById('admin-content');
    AdminApp.setTitle(config.title);
    var currentPage = 1;
    var searchTerm = '';
    var filterValue = '';
    var perPage = 15;

    function render() {
        var url = config.api + '?page=' + currentPage + '&per_page=' + perPage;
        if (searchTerm) url += '&search=' + encodeURIComponent(searchTerm);
        if (filterValue) url += '&filter=' + encodeURIComponent(filterValue);

        var filterHtml = '';
        if (config.filterOptions) {
            var filterOpts = '<option value="">All</option>';
            for (var fi = 0; fi < config.filterOptions.length; fi++) {
                var fo = config.filterOptions[fi];
                var foSel = filterValue === fo.value ? ' selected' : '';
                filterOpts += '<option value="' + fo.value + '"' + foSel + '>' + escapeHtml(fo.label) + '</option>';
            }
            filterHtml = '<div class="col-md-3"><select class="form-select" id="filter-select">' + filterOpts + '</select></div>';
        }

        var colSpan = config.filterOptions ? '5' : '8';
        var singular = config.title.replace(/s$/, '');

        content.innerHTML = '<div class="d-flex justify-content-between align-items-center mb-4">' +
            '<h4 class="mb-0">' + escapeHtml(config.title) + '</h4>' +
            '</div>' +
            '<div class="card shadow-sm">' +
            '<div class="card-body">' +
            '<div class="row mb-3 align-items-center">' +
            '<div class="col-md-4"><input type="text" class="form-control" id="search-input" placeholder="Search ' + escapeHtml(config.title) + '..."></div>' +
            filterHtml +
            '<div class="col-md-' + colSpan + ' text-end">' +
            '<button class="btn btn-primary" onclick="Router.navigate(\'' + config.route + '/create\')"><i class="bi bi-plus-lg me-1"></i> Add ' + escapeHtml(singular) + '</button>' +
            '</div></div>' +
            '<div id="table-container" class="table-responsive"><div class="text-center py-4"><div class="spinner-border text-primary"></div></div></div>' +
            '<div id="pagination-container" class="d-flex justify-content-between align-items-center mt-3"></div>' +
            '</div></div>';

        var searchInput = document.getElementById('search-input');
        var debounceTimer;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                searchTerm = e.target.value;
                currentPage = 1;
                loadData();
            }, 400);
        });

        if (config.filterOptions) {
            var filterSelect = document.getElementById('filter-select');
            filterSelect.addEventListener('change', function(e) {
                filterValue = e.target.value;
                currentPage = 1;
                loadData();
            });
        }

        loadData();
    }

    function loadData() {
        var tableContainer = document.getElementById('table-container');
        if (!tableContainer) return;
        tableContainer.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

        Api.get(url).then(function(response) {
            var items = response.data || response;
            var meta = response.meta || response.pagination || null;

            if (!items || !items.length) {
                tableContainer.innerHTML = '<div class="text-center py-4 text-muted">No records found</div>';
                document.getElementById('pagination-container').innerHTML = '';
                return;
            }

            var cols = config.columns;
            var tableHtml = '<table class="table table-hover table-striped align-middle">' +
                '<thead class="table-dark"><tr>';
            for (var c = 0; c < cols.length; c++) {
                tableHtml += '<th>' + escapeHtml(cols[c].label) + '</th>';
            }
            tableHtml += '<th style="width:150px;">Actions</th></tr></thead><tbody>';

            for (var r = 0; r < items.length; r++) {
                var row = items[r];
                tableHtml += '<tr>';
                for (var c2 = 0; c2 < cols.length; c2++) {
                    var col = cols[c2];
                    var cellVal = row[col.key];
                    if (col.render) {
                        tableHtml += '<td>' + col.render(cellVal, row) + '</td>';
                    } else {
                        tableHtml += '<td>' + escapeHtml(cellVal != null ? String(cellVal) : '') + '</td>';
                    }
                }
                var actionsHtml = config.actions ? config.actions(row) : '';
                tableHtml += '<td>' + actionsHtml + '</td></tr>';
            }
            tableHtml += '</tbody></table>';
            tableContainer.innerHTML = tableHtml;

            if (meta && meta.last_page > 1) {
                var pagHtml = '<div><small class="text-muted">Showing ' +
                    ((meta.current_page - 1) * meta.per_page + 1) + '-' +
                    Math.min(meta.current_page * meta.per_page, meta.total) +
                    ' of ' + meta.total + '</small></div><div class="btn-group">';
                if (meta.current_page > 1) {
                    pagHtml += '<button class="btn btn-sm btn-outline-secondary" onclick="GenericListPage._pageFunc(' + (meta.current_page - 1) + ')"><i class="bi bi-chevron-left"></i></button>';
                }
                var startPage = Math.max(1, meta.current_page - 2);
                var endPage = Math.min(meta.last_page, meta.current_page + 2);
                for (var p = startPage; p <= endPage; p++) {
                    var pActive = p === meta.current_page ? ' active' : '';
                    pagHtml += '<button class="btn btn-sm btn-outline-primary' + pActive + '" onclick="GenericListPage._pageFunc(' + p + ')">' + p + '</button>';
                }
                if (meta.current_page < meta.last_page) {
                    pagHtml += '<button class="btn btn-sm btn-outline-secondary" onclick="GenericListPage._pageFunc(' + (meta.current_page + 1) + ')"><i class="bi bi-chevron-right"></i></button>';
                }
                pagHtml += '</div>';
                document.getElementById('pagination-container').innerHTML = pagHtml;
            } else {
                document.getElementById('pagination-container').innerHTML = '';
            }
        }).catch(function(err) {
            tableContainer.innerHTML = '<div class="alert alert-danger">Error loading data: ' + escapeHtml(err.message || 'Unknown error') + '</div>';
        });
    }

    GenericListPage._pageFunc = function(page) {
        currentPage = page;
        loadData();
    };

    render();
}

function GenericCreatePage(config) {
    var content = document.getElementById('admin-content');
    AdminApp.setTitle('Add ' + config.title.replace(/s$/, ''));

    var formHtml = '<div class="d-flex justify-content-between align-items-center mb-4">' +
        '<h4 class="mb-0">Add ' + escapeHtml(config.title.replace(/s$/, '')) + '</h4>' +
        '<button class="btn btn-outline-secondary" onclick="Router.navigate(\'' + config.route + '\')"><i class="bi bi-arrow-left me-1"></i> Back</button>' +
        '</div>' +
        '<div class="card shadow-sm"><div class="card-body">';

    if (config.formFields) {
        formHtml += '<form id="create-form">';
        formHtml += '<div class="row">';
        formHtml += config.formFields();
        formHtml += '</div>';
        formHtml += '<div class="mt-3">' +
            '<button type="submit" class="btn btn-primary me-2"><i class="bi bi-check-lg me-1"></i> Save</button>' +
            '<button type="button" class="btn btn-outline-secondary" onclick="Router.navigate(\'' + config.route + '\')">Cancel</button>' +
            '</div></form>';
    }

    formHtml += '</div></div>';
    content.innerHTML = formHtml;

    if (config.formDataProvider) {
        config.formDataProvider().then(function() {
            attachSubmit();
        }).catch(function() {
            attachSubmit();
        });
    } else {
        attachSubmit();
    }

    function attachSubmit() {
        var form = document.getElementById('create-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = {};
                var inputs = form.querySelectorAll('input, select, textarea');
                for (var i = 0; i < inputs.length; i++) {
                    var inp = inputs[i];
                    if (inp.name) {
                        if (inp.type === 'checkbox') {
                            formData[inp.name] = inp.checked ? 1 : 0;
                        } else {
                            formData[inp.name] = inp.value;
                        }
                    }
                }
                var btn = form.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

                Api.post(config.api, formData).then(function(data) {
                    showToast('Created successfully');
                    Router.navigate(config.route);
                }).catch(function(err) {
                    showToast(err.message || 'Error creating record', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save';
                });
            });
        }
    }
}

function GenericEditPage(config, id) {
    var content = document.getElementById('admin-content');
    AdminApp.setTitle('Edit ' + config.title.replace(/s$/, ''));

    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';

    Api.get(config.api + '/' + id).then(function(response) {
        var item = response.data || response;
        var formHtml = '<div class="d-flex justify-content-between align-items-center mb-4">' +
            '<h4 class="mb-0">Edit ' + escapeHtml(config.title.replace(/s$/, '')) + '</h4>' +
            '<button class="btn btn-outline-secondary" onclick="Router.navigate(\'' + config.route + '\')"><i class="bi bi-arrow-left me-1"></i> Back</button>' +
            '</div>' +
            '<div class="card shadow-sm"><div class="card-body">';

        if (config.formFields) {
            formHtml += '<form id="edit-form">';
            formHtml += '<div class="row">';
            formHtml += config.formFields(item);
            formHtml += '</div>';
            formHtml += '<div class="mt-3">' +
                '<button type="submit" class="btn btn-primary me-2"><i class="bi bi-check-lg me-1"></i> Update</button>' +
                '<button type="button" class="btn btn-outline-secondary" onclick="Router.navigate(\'' + config.route + '\')">Cancel</button>' +
                '</div></form>';
        }

        formHtml += '</div></div>';
        content.innerHTML = formHtml;

        if (config.formDataProvider) {
            config.formDataProvider().then(function() {
                attachSubmit();
            }).catch(function() {
                attachSubmit();
            });
        } else {
            attachSubmit();
        }

        function attachSubmit() {
            var form = document.getElementById('edit-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var formData = {};
                    var inputs = form.querySelectorAll('input, select, textarea');
                    for (var i = 0; i < inputs.length; i++) {
                        var inp = inputs[i];
                        if (inp.name) {
                            if (inp.type === 'checkbox') {
                                formData[inp.name] = inp.checked ? 1 : 0;
                            } else {
                                formData[inp.name] = inp.value;
                            }
                        }
                    }
                    formData._method = 'PUT';
                    var btn = form.querySelector('button[type="submit"]');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Updating...';

                    Api.put(config.api + '/' + id, formData).then(function(data) {
                        showToast('Updated successfully');
                        Router.navigate(config.route);
                    }).catch(function(err) {
                        showToast(err.message || 'Error updating record', 'error');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Update';
                    });
                });
            }
        }
    }).catch(function(err) {
        content.innerHTML = '<div class="alert alert-danger">Error loading record: ' + escapeHtml(err.message || 'Unknown error') + '</div>';
    });
}

function GenericDelete(config, id) {
    if (!confirm('Are you sure you want to delete this ' + (config.title.replace(/s$/, '')) + '? This action cannot be undone.')) {
        return;
    }

    Api['delete'](config.api + '/' + id).then(function(data) {
        showToast('Deleted successfully');
        Router.navigate(config.route);
    }).catch(function(err) {
        showToast(err.message || 'Error deleting record', 'error');
    });
}
/* ----------------------------------------------------------
   7. Page Configs
   ---------------------------------------------------------- */
var pages = {

    /* ---------- COURSES ---------- */
    courses: {
        title: 'Courses',
        api: '/courses',
        route: 'courses',
        columns: [
            { key: 'id', label: 'ID' },
            { key: 'title', label: 'Title', render: function(v) { return escapeHtml(v); } },
            { key: 'category', label: 'Category', render: function(v) { return v ? escapeHtml(v.name) : '-'; } },
            { key: 'teacher', label: 'Teacher', render: function(v) { return v ? escapeHtml(v.name) : '-'; } },
            { key: 'price', label: 'Price', render: function(v) { return v != null ? '?' + escapeHtml(String(v)) : '-'; } },
            { key: 'status', label: 'Status', render: function(v) { return statusBadge(v); } },
            { key: 'created_at', label: 'Created', render: function(v) { return v ? new Date(v).toLocaleDateString() : '-'; } }
        ],
        actions: function(row) {
            return '<a href="#/courses/' + row.id + '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>' +
                '<button class="btn btn-sm btn-outline-danger" onclick="GenericDelete(pages.courses,' + row.id + ')" title="Delete"><i class="bi bi-trash"></i></button>';
        },
        formFields: function(item) {
            var c = item || {};
            return inputField('title', 'Course Title', c.title) +
                textareaField('description', 'Description', c.description) +
                inputField('price', 'Price', c.price, 'number') +
                inputField('duration', 'Duration', c.duration) +
                selectField('category_id', 'Category', c.category_id, []) +
                selectField('teacher_id', 'Teacher', c.teacher_id, []) +
                statusSelect('status', 'Status', c.status);
        },
        formDataProvider: function() {
            return Promise.all([
                Api.get('/categories?per_page=100'),
                Api.get('/teachers?per_page=100')
            ]).then(function(responses) {
                var cats = responses[0].data || responses[0] || [];
                var teachers = responses[1].data || responses[1] || [];
                var catOpts = cats.map(function(c) { return { value: c.id, label: c.name }; });
                var teacherOpts = teachers.map(function(t) { return { value: t.id, label: t.name }; });
                var catSel = document.querySelector('[name="category_id"]');
                var teacherSel = document.querySelector('[name="teacher_id"]');
                if (catSel) {
                    for (var i = 0; i < catOpts.length; i++) {
                        var opt = document.createElement('option');
                        opt.value = catOpts[i].value;
                        opt.textContent = catOpts[i].label;
                        catSel.appendChild(opt);
                    }
                }
                if (teacherSel) {
                    for (var j = 0; j < teacherOpts.length; j++) {
                        var opt2 = document.createElement('option');
                        opt2.value = teacherOpts[j].value;
                        opt2.textContent = teacherOpts[j].label;
                        teacherSel.appendChild(opt2);
                    }
                }
            }).catch(function() {});
        }
    },

    /* ---------- CATEGORIES ---------- */
    categories: {
        title: 'Categories',
        api: '/categories',
        route: 'categories',
        columns: [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Name', render: function(v) { return escapeHtml(v); } },
            { key: 'slug', label: 'Slug', render: function(v) { return escapeHtml(v); } },
            { key: 'status', label: 'Status', render: function(v) { return statusBadge(v); } },
            { key: 'created_at', label: 'Created', render: function(v) { return v ? new Date(v).toLocaleDateString() : '-'; } }
        ],
        actions: function(row) {
            return '<a href="#/categories/' + row.id + '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>' +
                '<button class="btn btn-sm btn-outline-danger" onclick="GenericDelete(pages.categories,' + row.id + ')" title="Delete"><i class="bi bi-trash"></i></button>';
        },
        formFields: function(item) {
            var c = item || {};
            return inputField('name', 'Category Name', c.name) +
                textareaField('description', 'Description', c.description) +
                statusSelect('status', 'Status', c.status);
        },
        formDataProvider: function() { return Promise.resolve(); }
    },

    /* ---------- TEACHERS ---------- */
    teachers: {
        title: 'Teachers',
        api: '/teachers',
        route: 'teachers',
        columns: [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Name', render: function(v) { return escapeHtml(v); } },
            { key: 'email', label: 'Email', render: function(v) { return escapeHtml(v); } },
            { key: 'phone', label: 'Phone', render: function(v) { return escapeHtml(v); } },
            { key: 'qualification', label: 'Qualification', render: function(v) { return escapeHtml(v || '-'); } },
            { key: 'status', label: 'Status', render: function(v) { return statusBadge(v); } }
        ],
        actions: function(row) {
            return '<a href="#/teachers/' + row.id + '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>' +
                '<button class="btn btn-sm btn-outline-danger" onclick="GenericDelete(pages.teachers,' + row.id + ')" title="Delete"><i class="bi bi-trash"></i></button>';
        },
        formFields: function(item) {
            var c = item || {};
            return inputField('name', 'Full Name', c.name) +
                inputField('email', 'Email', c.email, 'email') +
                inputField('phone', 'Phone', c.phone, 'tel') +
                inputField('qualification', 'Qualification', c.qualification) +
                textareaField('bio', 'Bio', c.bio) +
                statusSelect('status', 'Status', c.status);
        },
        formDataProvider: function() { return Promise.resolve(); }
    },

    /* ---------- STUDENTS ---------- */
    students: {
        title: 'Students',
        api: '/students',
        route: 'students',
        columns: [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Name', render: function(v) { return escapeHtml(v); } },
            { key: 'email', label: 'Email', render: function(v) { return escapeHtml(v); } },
            { key: 'phone', label: 'Phone', render: function(v) { return escapeHtml(v); } },
            { key: 'batch', label: 'Batch', render: function(v) { return v ? escapeHtml(v.name) : '-'; } },
            { key: 'status', label: 'Status', render: function(v) { return statusBadge(v); } }
        ],
        actions: function(row) {
            return '<a href="#/students/' + row.id + '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>' +
                '<button class="btn btn-sm btn-outline-danger" onclick="GenericDelete(pages.students,' + row.id + ')" title="Delete"><i class="bi bi-trash"></i></button>';
        },
        formFields: function(item) {
            var c = item || {};
            return inputField('name', 'Full Name', c.name) +
                inputField('email', 'Email', c.email, 'email') +
                inputField('phone', 'Phone', c.phone, 'tel') +
                inputField('address', 'Address', c.address) +
                selectField('batch_id', 'Batch', c.batch_id, []) +
                statusSelect('status', 'Status', c.status);
        },
        formDataProvider: function() {
            return Api.get('/batches?per_page=100').then(function(res) {
                var batches = res.data || res || [];
                var sel = document.querySelector('[name="batch_id"]');
                if (sel) {
                    for (var i = 0; i < batches.length; i++) {
                        var opt = document.createElement('option');
                        opt.value = batches[i].id;
                        opt.textContent = batches[i].name;
                        sel.appendChild(opt);
                    }
                }
            }).catch(function() {});
        }
    },

    /* ---------- BATCHES ---------- */
    batches: {
        title: 'Batches',
        api: '/batches',
        route: 'batches',
        columns: [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Name', render: function(v) { return escapeHtml(v); } },
            { key: 'course', label: 'Course', render: function(v) { return v ? escapeHtml(v.title) : '-'; } },
            { key: 'start_date', label: 'Start Date', render: function(v) { return v ? escapeHtml(v) : '-'; } },
            { key: 'end_date', label: 'End Date', render: function(v) { return v ? escapeHtml(v) : '-'; } },
            { key: 'status', label: 'Status', render: function(v) { return statusBadge(v); } }
        ],
        actions: function(row) {
            return '<a href="#/batches/' + row.id + '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>' +
                '<button class="btn btn-sm btn-outline-danger" onclick="GenericDelete(pages.batches,' + row.id + ')" title="Delete"><i class="bi bi-trash"></i></button>';
        },
        formFields: function(item) {
            var c = item || {};
            return inputField('name', 'Batch Name', c.name) +
                selectField('course_id', 'Course', c.course_id, []) +
                selectField('teacher_id', 'Teacher', c.teacher_id, []) +
                inputField('start_date', 'Start Date', c.start_date, 'date') +
                inputField('end_date', 'End Date', c.end_date, 'date') +
                inputField('max_students', 'Max Students', c.max_students, 'number') +
                statusSelect('status', 'Status', c.status);
        },
        formDataProvider: function() {
            return Promise.all([
                Api.get('/courses?per_page=100'),
                Api.get('/teachers?per_page=100')
            ]).then(function(responses) {
                var courses = responses[0].data || responses[0] || [];
                var teachers = responses[1].data || responses[1] || [];
                var cSel = document.querySelector('[name="course_id"]');
                var tSel = document.querySelector('[name="teacher_id"]');
                if (cSel) {
                    for (var i = 0; i < courses.length; i++) {
                        var opt = document.createElement('option');
                        opt.value = courses[i].id;
                        opt.textContent = courses[i].title;
                        cSel.appendChild(opt);
                    }
                }
                if (tSel) {
                    for (var j = 0; j < teachers.length; j++) {
                        var opt2 = document.createElement('option');
                        opt2.value = teachers[j].id;
                        opt2.textContent = teachers[j].name;
                        tSel.appendChild(opt2);
                    }
                }
            }).catch(function() {});
        }
    },

    /* ---------- ENQUIRIES ---------- */
    enquiries: {
        title: 'Enquiries',
        api: '/enquiries',
        route: 'enquiries',
        filterOptions: [
            { value: 'new', label: 'New' },
            { value: 'contacted', label: 'Contacted' },
            { value: 'converted', label: 'Converted' },
            { value: 'closed', label: 'Closed' }
        ],
        columns: [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Name', render: function(v) { return escapeHtml(v); } },
            { key: 'email', label: 'Email', render: function(v) { return escapeHtml(v); } },
            { key: 'phone', label: 'Phone', render: function(v) { return escapeHtml(v); } },
            { key: 'course', label: 'Course', render: function(v) { return v ? escapeHtml(v.title) : '-'; } },
            { key: 'message', label: 'Message', render: function(v) { return v ? '<span title="' + escapeHtml(v) + '">' + escapeHtml(v.substring(0, 40)) + (v.length > 40 ? '...' : '') + '</span>' : '-'; } },
            { key: 'status', label: 'Status', render: function(v) { return statusBadge(v); } },
            { key: 'created_at', label: 'Date', render: function(v) { return v ? new Date(v).toLocaleDateString() : '-'; } }
        ],
        actions: function(row) {
            return '<a href="#/enquiries/' + row.id + '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>' +
                '<button class="btn btn-sm btn-outline-danger" onclick="GenericDelete(pages.enquiries,' + row.id + ')" title="Delete"><i class="bi bi-trash"></i></button>';
        },
        formFields: function(item) {
            var c = item || {};
            return inputField('name', 'Name', c.name) +
                inputField('email', 'Email', c.email, 'email') +
                inputField('phone', 'Phone', c.phone, 'tel') +
                selectField('course_id', 'Course', c.course_id, []) +
                textareaField('message', 'Message', c.message) +
                selectField('status', 'Status', c.status, [
                    { value: 'new', label: 'New' },
                    { value: 'contacted', label: 'Contacted' },
                    { value: 'converted', label: 'Converted' },
                    { value: 'closed', label: 'Closed' }
                ]);
        },
        formDataProvider: function() {
            return Api.get('/courses?per_page=100').then(function(res) {
                var courses = res.data || res || [];
                var sel = document.querySelector('[name="course_id"]');
                if (sel) {
                    for (var i = 0; i < courses.length; i++) {
                        var opt = document.createElement('option');
                        opt.value = courses[i].id;
                        opt.textContent = courses[i].title;
                        sel.appendChild(opt);
                    }
                }
            }).catch(function() {});
        }
    },
