<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ config('app.name', 'Global Institute') }} Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{--
        Same stylesheet as layouts/admin.blade.php, through the same Vite build.
        The SPA previously linked a hand-copied /css/admin.css that had gone stale
        (28KB against the current 59KB), which is why it rendered unstyled.
    --}}
    {{-- Design system, plus the shared loading utilities (window.Loading). --}}
    @vite(['resources/css/app.css', 'resources/js/ui/index.js'])

    {{--
        SPA-only polish. Lives here (with the shell) rather than in admin.css so
        it needs no rebuild and survives the partial navigation in
        resources/js/spa.js, which replaces .admin-content via innerHTML.
    --}}
    <style>
        /* Dashboard: fluid multi-column panels instead of a fixed 1fr 1fr. */
        .admin-content .dash-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }

        .dashboard-welcome-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .dashboard-welcome-date {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            opacity: 0.65;
            white-space: nowrap;
        }

        .dashboard-welcome-date .material-symbols-outlined { font-size: 15px; }

        /* Stat tiles become links when the metric is backed by a list screen. */
        a.stat-item {
            text-decoration: none;
            color: inherit;
        }

        a.stat-item:hover {
            border-color: var(--color-primary);
            box-shadow: var(--shadow-sm);
            transform: translateY(-1px);
        }

        .stat-item .stat-arrow {
            margin-left: auto;
            font-size: 18px;
            color: var(--color-text-muted);
            opacity: 0;
            transition: var(--transition-fast);
        }

        a.stat-item:hover .stat-arrow { opacity: 1; transform: translateX(2px); }

        .dash-panel-count {
            font-size: 11px;
            font-weight: 600;
            color: var(--color-text-muted);
            background: var(--gray-100);
            border-radius: 999px;
            padding: 2px 8px;
        }

        .dash-quick-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 16px 20px;
        }

        .dash-quick-tile {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 14px;
            border: 1px solid var(--color-border);
            border-radius: var(--border-radius-sm);
            text-decoration: none;
            color: var(--color-text);
            font-size: 13px;
            font-weight: 500;
            transition: var(--transition-fast);
        }

        .dash-quick-tile .material-symbols-outlined {
            font-size: 22px;
            color: var(--color-primary);
        }

        .dash-quick-tile:hover {
            border-color: var(--color-primary);
            background: var(--gray-50);
            transform: translateY(-1px);
        }

        /* Forms: collapse the multi-column grids on small screens. */
        @media (max-width: 767px) {
            .admin-content .form-grid-2,
            .admin-content .form-grid-3 { grid-template-columns: 1fr; }
        }

        .req-mark {
            color: var(--color-danger);
            margin-left: 2px;
        }

        /* Forms: keep the Save/Cancel actions reachable on long forms. */
        .admin-content .form-actionbar {
            position: sticky;
            bottom: 0;
            z-index: 5;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 12px 20px;
            background: var(--color-surface);
            border-top: 1px solid var(--color-border);
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            box-shadow: 0 -6px 16px rgba(15, 23, 42, 0.05);
        }

        /* Top-bar account menu */
        .header-user { padding: 0; border: none; background: none; }
        .header-user:hover { background: none; border-color: transparent; }
        .header-user-trigger {
            display: flex;
            align-items: center;
            gap: 8px;
            width: auto;
            height: auto;
            padding: 5px 10px;
            border-radius: var(--border-radius-sm);
            border: 1px solid transparent;
            background: none;
            cursor: pointer;
            font: inherit;
            color: inherit;
        }
        .header-user-trigger:hover { background: var(--color-surface-hover); border-color: var(--color-border); }
        .header-user-trigger .material-symbols-outlined { transition: transform 0.15s ease; }
        .header-user-trigger[aria-expanded="true"] .material-symbols-outlined { transform: rotate(180deg); }
        .user-dropdown { min-width: 240px; }
        .user-dropdown-head { display: flex; align-items: center; gap: 10px; padding: 10px 10px 8px; }
        .user-dropdown-avatar { width: 38px; height: 38px; font-size: 14px; flex-shrink: 0; }
        .user-dropdown-meta { display: flex; flex-direction: column; min-width: 0; }
        .user-dropdown-name { font-size: 13px; font-weight: 600; color: var(--color-text); }
        .user-dropdown-email { font-size: 12px; color: var(--color-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-dropdown-form { margin: 0; }
    </style>
</head>
<body>
    {{--
        Structure mirrors layouts/admin.blade.php exactly so the SPA inherits the
        design system rather than inventing class names of its own.
    --}}
    <div class="admin-layout">
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1; color: var(--color-tertiary); font-size: 24px;">precision_manufacturing</span>
                </div>
                <div class="sidebar-brand">
                    <h2>Global Institute</h2>
                    <p>Admin Panel</p>
                </div>
            </div>

            <div class="sidebar-nav-scroll">
                {{-- Nav links are injected by admin.js, using this same markup --}}
                <nav class="sidebar-nav" id="sidebar-nav"></nav>
            </div>

            <div class="sidebar-footer">
                <a href="/" target="_blank" class="sidebar-footer-link">
                    <span class="material-symbols-outlined">open_in_new</span>
                    <span>View Website</span>
                </a>
                <div class="sidebar-divider"></div>
                <div class="sidebar-user">
                    <div class="sidebar-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
                    <div class="sidebar-user-info">
                        <span class="sidebar-user-name">{{ auth()->user()->name ?? 'Admin' }}</span>
                        <span class="sidebar-user-role">Administrator</span>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}" style="margin-left:auto;">
                        @csrf
                        <button type="submit" class="sidebar-logout-btn" title="Logout">
                            <span class="material-symbols-outlined">logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <div class="admin-header-left">
                    <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                        <span class="material-symbols-outlined" style="font-size:20px;">menu</span>
                    </button>
                    <h1 id="page-title">Dashboard</h1>
                </div>
                <div class="admin-header-right">
                    <div id="page-actions" class="admin-header-actions"></div>
                    @include('partials.admin-user-menu')
                </div>
            </header>

            {{-- Every SPA page renders into here --}}
            <div class="admin-content" id="admin-content"></div>
        </main>
    </div>

    {{-- Modal host, using the design system's modal classes --}}
    <div id="modal-root"></div>

    <script>
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }
        function closeSidebar() {
            document.getElementById('adminSidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('active');
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSidebar();
        });

        // The SPA runs inside the authenticated admin session, so it needs the
        // CSRF token rather than a bearer token.
        window.AdminConfig = {
            csrfToken: @json(csrf_token()),
            baseUrl: @json(url('/')),
            user: {
                name: @json(auth()->user()->name ?? 'Admin'),
                email: @json(auth()->user()->email ?? null)
            }
        };
    </script>

    <script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}"></script>
</body>
</html>
