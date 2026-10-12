/**
 * Admin page widgets (Blade admin).
 *
 * The server-rendered admin is navigated by the partial-navigation layer in
 * resources/js/spa.js: it fetches the next page and swaps `.admin-content` via
 * innerHTML. Scripts that arrive inside that swapped markup (inline <script>
 * tags or @push('scripts') blocks, which live outside `.admin-content`) are
 * never executed, so every widget below is wired exactly once here with
 * delegated listeners on `document`, then re-synced whenever spa.js announces a
 * completed navigation via the `spa:load` event.
 */
(function () {
    'use strict';

    function all(root, sel) {
        return Array.prototype.slice.call(root.querySelectorAll(sel));
    }

    function one(root, sel) {
        return root.querySelector(sel);
    }

    /* ------------------------------------------------------------ permissions */

    function permCard() {
        return document.getElementById('permissions-card');
    }

    function updatePermModule(mod) {
        var boxes = all(mod, '.perm-check');
        var checked = all(mod, '.perm-check:checked').length;
        var badge = one(mod, '.perm-count');
        if (badge) badge.textContent = checked + '/' + boxes.length;

        var toggle = one(mod, '.module-toggle');
        if (toggle) {
            toggle.checked = boxes.length > 0 && checked === boxes.length;
            toggle.indeterminate = checked > 0 && checked < boxes.length;
        }
    }

    function updatePermsAll() {
        var card = permCard();
        if (!card) return;

        var modules = all(card, '.perm-module');
        modules.forEach(updatePermModule);

        var total = all(card, '.perm-check').length;
        var checked = all(card, '.perm-check:checked').length;

        var selectAll = document.getElementById('select-all-perms');
        if (selectAll) {
            selectAll.checked = total > 0 && checked === total;
            selectAll.indeterminate = checked > 0 && checked < total;
        }

        var sum = document.getElementById('sum-perms');
        if (sum) sum.textContent = checked + ' / ' + total;
    }

    function setPermsCollapsed(collapsed) {
        var card = permCard();
        if (!card) return;
        all(card, '.perm-module').forEach(function (mod) {
            mod.classList.toggle('collapsed', collapsed);
            var toggle = one(mod, '.perm-toggle');
            if (toggle) toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        });
    }

    function filterPerms(term) {
        var card = permCard();
        if (!card) return;
        term = (term || '').trim().toLowerCase();
        var any = false;

        all(card, '.perm-module').forEach(function (mod) {
            var visible = 0;
            all(mod, '.perm-item').forEach(function (item) {
                var input = one(item, '.perm-check');
                var label = input ? (input.getAttribute('data-label') || '') : (item.getAttribute('data-label') || '');
                var match = !term || label.toLowerCase().indexOf(term) !== -1;
                item.classList.toggle('is-hidden', !match);
                if (match) visible++;
            });

            mod.classList.toggle('is-hidden', visible === 0);
            if (term && visible > 0) {
                mod.classList.remove('collapsed');
                var toggle = one(mod, '.perm-toggle');
                if (toggle) toggle.setAttribute('aria-expanded', 'true');
            }
            if (visible > 0) any = true;
        });

        var empty = document.getElementById('perm-empty');
        if (empty) empty.classList.toggle('visible', !any);
    }

    /* ----------------------------------------------------------------- menus */

    function menuCard() {
        return document.getElementById('menus-card');
    }

    function updateMenuCard(cardEl) {
        var badge = one(cardEl, '.menu-count');
        if (badge) {
            var total = all(cardEl, '.menu-check-child').length;
            var checked = all(cardEl, '.menu-check-child:checked').length;
            badge.textContent = checked + '/' + total;
        }
    }

    function updateMenusAll() {
        var card = menuCard();
        if (!card) return;

        all(card, '[data-menu-card]').forEach(updateMenuCard);

        var total = all(card, '.menu-check').length;
        var checked = all(card, '.menu-check:checked').length;

        var selectAll = document.getElementById('select-all-menus');
        if (selectAll) {
            selectAll.checked = total > 0 && checked === total;
            selectAll.indeterminate = checked > 0 && checked < total;
        }

        var sum = document.getElementById('sum-menus');
        if (sum) sum.textContent = checked + ' / ' + total;
    }

    function setMenusCollapsed(collapsed) {
        var card = menuCard();
        if (!card) return;
        all(card, '[data-menu-card]').forEach(function (cardEl) {
            cardEl.classList.toggle('collapsed', collapsed);
            var toggle = one(cardEl, '.menu-toggle');
            if (toggle) toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        });
    }

    function filterMenus(term) {
        var card = menuCard();
        if (!card) return;
        term = (term || '').trim().toLowerCase();
        var any = false;

        all(card, '[data-menu-card]').forEach(function (cardEl) {
            var visible = 0;
            all(cardEl, '.menu-child-item').forEach(function (item) {
                var match = !term || (item.getAttribute('data-label') || '').toLowerCase().indexOf(term) !== -1;
                item.classList.toggle('is-hidden', !match);
                if (match) visible++;
            });

            var nameEl = one(cardEl, '.menu-card-name');
            var parentMatch = !term || (nameEl ? nameEl.textContent.toLowerCase().indexOf(term) !== -1 : false);
            var show = visible > 0 || parentMatch;
            cardEl.classList.toggle('is-hidden', !show);

            if (term && show) {
                cardEl.classList.remove('collapsed');
                var toggle = one(cardEl, '.menu-toggle');
                if (toggle) toggle.setAttribute('aria-expanded', 'true');
            }
            if (show) any = true;
        });

        var empty = document.getElementById('menu-empty');
        if (empty) empty.classList.toggle('visible', !any);
    }

    /* --------------------------------------------------------- roles index list */

    function applyRoleFilter() {
        var search = document.getElementById('role-search');
        var filter = document.getElementById('role-filter');
        if (!search || !filter) return;

        var rows = all(document, '[data-role-row]');
        var term = (search.value || '').trim().toLowerCase();
        var type = filter.value;
        var visible = 0;

        rows.forEach(function (row) {
            var data = row.dataset;
            var match = !term ||
                (data.name || '').indexOf(term) !== -1 ||
                (data.slug || '').indexOf(term) !== -1 ||
                (data.desc || '').indexOf(term) !== -1;

            if (match) {
                if (type === 'system') match = data.system === '1';
                else if (type === 'custom') match = data.system === '0';
                else if (type === 'active') match = data.active === '1';
                else if (type === 'inactive') match = data.active === '0';
            }

            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        var countEl = document.getElementById('role-visible-count');
        if (countEl) countEl.textContent = visible;

        var emptyEl = document.getElementById('roles-empty-filter');
        if (emptyEl) emptyEl.classList.toggle('visible', visible === 0 && rows.length > 0);
    }

    /* ------------------------------------------------------- create role slug */

    function slugify(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    /* ------------------------------------------------------------ rich editor */

    // CKEditor 5 (classic build) is pulled from the CDN on first use, matching
    // the other CDN assets the admin layout already relies on. It is only
    // fetched on screens that actually contain a rich textarea.
    var CK_URL = 'https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js';
    var ckPromise = null;
    var richEditors = [];

    function loadCKEditor() {
        if (window.ClassicEditor) return Promise.resolve(window.ClassicEditor);
        if (ckPromise) return ckPromise;

        ckPromise = new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            script.src = CK_URL;
            script.async = true;
            script.onload = function () {
                if (window.ClassicEditor) resolve(window.ClassicEditor);
                else reject(new Error('ClassicEditor is not defined'));
            };
            script.onerror = function () { reject(new Error('CKEditor failed to load')); };
            document.head.appendChild(script);
        });

        return ckPromise;
    }

    function initRichEditors() {
        var fields = all(document, 'textarea[data-rich-editor]').filter(function (ta) {
            return ta.dataset.ckReady !== '1';
        });
        if (!fields.length) return;

        loadCKEditor().then(function (ClassicEditor) {
            fields.forEach(function (ta) {
                if (ta.dataset.ckReady === '1') return;
                ta.dataset.ckReady = '1';

                ClassicEditor.create(ta, {
                    toolbar: [
                        'heading', '|',
                        'bold', 'italic', 'underline', 'strikethrough', '|',
                        'bulletedList', 'numberedList', '|',
                        'outdent', 'indent', '|',
                        'blockQuote', 'link', 'insertTable', 'mediaEmbed', 'horizontalLine', '|',
                        'removeFormat', 'sourceEditing', '|',
                        'undo', 'redo'
                    ],
                    heading: {
                        options: [
                            { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                            { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                            { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                            { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' }
                        ]
                    }
                }).then(function (editor) {
                    richEditors.push({ editor: editor, textarea: ta });

                    // Keep the hidden source textarea in sync on every change so
                    // whatever reads the form (native submit or the AJAX handler
                    // in spa.js) always sees the current HTML.
                    editor.model.document.on('change:data', function () {
                        ta.value = editor.getData();
                    });
                }).catch(function (err) {
                    ta.dataset.ckReady = '';
                    console.warn('[admin] CKEditor init failed, using plain textarea.', err);
                });
            });
        }).catch(function (err) {
            // Offline / blocked CDN: the plain textarea stays fully usable.
            console.warn('[admin] CKEditor unavailable, using plain textarea.', err);
        });
    }

    // Editors attached to markup that spa.js has swapped away are destroyed so
    // their instance does not linger (and leak) after navigation.
    function cleanupRichEditors() {
        richEditors = richEditors.filter(function (pair) {
            if (pair.textarea && pair.textarea.isConnected) return true;
            try { pair.editor.destroy(); } catch (e) { /* already gone */ }
            return false;
        });
    }

    /* --------------------------------------------------------------- pages */

    // Once a search swap finishes we put the caret back in the box so typing
    // can continue uninterrupted; otherwise the re-rendered input would lose
    // focus on every debounce.
    var pagesRefocus = false;

    function pagesNavigate(changes) {
        var url = new URL(window.location.href);
        Object.keys(changes).forEach(function (key) {
            if (changes[key]) url.searchParams.set(key, changes[key]);
            else url.searchParams.delete(key);
        });
        url.searchParams.delete('page');

        var target = url.pathname + url.search;
        // replaceState keeps the filter in the URL without spamming history.
        if (window.history && window.history.replaceState) {
            window.history.replaceState({ url: target }, '', target);
        }
        if (typeof window.loadPage === 'function') window.loadPage(target);
        else window.location.href = target;
    }

    function initPagesToolbar() {
        var search = document.getElementById('pagesSearchInput');
        var timer;

        if (search && search.dataset.pagesWired !== '1') {
            search.dataset.pagesWired = '1';
            search.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    pagesRefocus = true;
                    pagesNavigate({ search: search.value.trim() });
                }, 400);
            });
            search.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(timer);
                    pagesRefocus = true;
                    pagesNavigate({ search: search.value.trim() });
                }
            });

            if (pagesRefocus) {
                pagesRefocus = false;
                search.focus();
                try { search.setSelectionRange(search.value.length, search.value.length); } catch (err) { /* noop */ }
            }
        }

        var status = document.getElementById('pagesStatusFilter');
        if (status && status.dataset.pagesWired !== '1') {
            status.dataset.pagesWired = '1';
            status.addEventListener('change', function () {
                pagesNavigate({ status: status.value });
            });
        }

        var clear = document.getElementById('pagesSearchClear');
        if (clear && clear.dataset.pagesWired !== '1') {
            clear.dataset.pagesWired = '1';
            clear.addEventListener('click', function () {
                pagesNavigate({ search: '' });
            });
        }
    }

    /* ------------------------------------------------------------ delegation */

    function closeActionMenus() {
        all(document, '.action-dropdown.open').forEach(function (d) {
            d.classList.remove('open');
            var menu = d.closest('.action-menu');
            var trigger = menu ? menu.querySelector('.action-menu-trigger') : null;
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
        });
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.action-menu-trigger');

        // Triggers that still carry an inline onclick keep their legacy handler;
        // markup without one (the modern Pages table) is handled here so the menu
        // also works after a partial navigation.
        if (trigger && !trigger.onclick) {
            var menu = trigger.closest('.action-menu');
            var dropdown = menu ? menu.querySelector('.action-dropdown') : null;
            if (dropdown) {
                var willOpen = !dropdown.classList.contains('open');
                closeActionMenus();
                if (willOpen) dropdown.classList.add('open');
                trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                return;
            }
        }

        if (!e.target.closest('.action-menu')) closeActionMenus();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeActionMenus();
    });

    document.addEventListener('click', function (e) {
        var target;

        if ((target = e.target.closest('.perm-toggle'))) {
            var mod = target.closest('.perm-module');
            if (mod) {
                mod.classList.toggle('collapsed');
                target.setAttribute('aria-expanded', mod.classList.contains('collapsed') ? 'false' : 'true');
            }
            return;
        }

        if ((target = e.target.closest('.menu-toggle'))) {
            var cardEl = target.closest('[data-menu-card]');
            if (cardEl) {
                cardEl.classList.toggle('collapsed');
                target.setAttribute('aria-expanded', cardEl.classList.contains('collapsed') ? 'false' : 'true');
            }
            return;
        }

        if (e.target.closest('#perm-expand-all')) { setPermsCollapsed(false); return; }
        if (e.target.closest('#perm-collapse-all')) { setPermsCollapsed(true); return; }
        if (e.target.closest('#menu-expand-all')) { setMenusCollapsed(false); return; }
        if (e.target.closest('#menu-collapse-all')) { setMenusCollapsed(true); }
    });

    document.addEventListener('change', function (e) {
        var el = e.target;

        if (el.matches('.module-toggle')) {
            var mod = el.closest('.perm-module');
            if (mod) all(mod, '.perm-check').forEach(function (cb) { cb.checked = el.checked; });
            updatePermsAll();
            return;
        }
        if (el.matches('.perm-check')) { updatePermsAll(); return; }
        if (el.id === 'select-all-perms') {
            var card = permCard();
            if (card) all(card, '.perm-check').forEach(function (cb) { cb.checked = el.checked; });
            updatePermsAll();
            return;
        }

        if (el.matches('.menu-check-parent')) {
            var cardEl = el.closest('[data-menu-card]');
            if (cardEl) all(cardEl, '.menu-check-child').forEach(function (cb) { cb.checked = el.checked; });
            updateMenusAll();
            return;
        }
        if (el.matches('.menu-check')) { updateMenusAll(); return; }
        if (el.id === 'select-all-menus') {
            var menuWrap = menuCard();
            if (menuWrap) all(menuWrap, '.menu-check').forEach(function (cb) { cb.checked = el.checked; });
            updateMenusAll();
            return;
        }

        if (el.id === 'role-filter') applyRoleFilter();
    });

    document.addEventListener('input', function (e) {
        var el = e.target;

        if (el.id === 'perm-search') { filterPerms(el.value); return; }
        if (el.id === 'menu-search') { filterMenus(el.value); return; }
        if (el.id === 'role-search') { applyRoleFilter(); return; }

        // Auto-fill the slug from the role name until it is edited by hand.
        if (el.id === 'name') {
            var form = el.closest('#role-form');
            if (!form) return;
            var slug = form.querySelector('#slug');
            if (!slug || slug.dataset.edited === '1') return;
            slug.value = slugify(el.value);
            return;
        }
        if (el.id === 'slug') { el.dataset.edited = '1'; }
    });

    /* ---------------------------------------------------------------- syncing */

    function sync() {
        updatePermsAll();
        updateMenusAll();
        applyRoleFilter();
        cleanupRichEditors();
        initRichEditors();
        initPagesToolbar();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', sync, { once: true });
    } else {
        sync();
    }

    document.addEventListener('spa:load', sync);
})();
