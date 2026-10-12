<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('pageTitle', config('app.name', 'Global Institute') . ' Admin')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite('resources/css/app.css')
    @stack('styles')
    <style>
        /* Shared form polish for Blade admin screens (no rebuild required). */
        .req-mark { color: var(--color-danger); margin-left: 2px; }
        .form-actionbar {
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
        @media (max-width: 767px) {
            .form-grid-2,
            .form-grid-3 { grid-template-columns: 1fr; }
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
    <div class="admin-layout">
        {{-- Sidebar Overlay (Mobile) --}}
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

        {{-- Sidebar --}}
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
                <nav class="sidebar-nav">
                    @include('partials.admin-sidebar')
                </nav>
            </div>

            <div class="sidebar-footer">
                <a href="/" target="_blank" class="sidebar-footer-link">
                    <span class="material-symbols-outlined">open_in_new</span>
                    <span>View Website</span>
                </a>
                <div class="sidebar-divider"></div>
                <div class="sidebar-user">
                    <div class="sidebar-user-avatar">A</div>
                    <div class="sidebar-user-info">
                        <span class="sidebar-user-name">Admin</span>
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

        {{-- Main Content --}}
        <main class="admin-main">
            <header class="admin-header">
                <div class="admin-header-left">
                    <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                        <span class="material-symbols-outlined" style="font-size:20px;">menu</span>
                    </button>
                    {{--
                        Both hooks are required by the partial navigation in
                        resources/js/spa.js: it swaps .admin-content plus these two
                        regions. Without them the header kept the previous page's
                        title and action button until a full reload.
                    --}}
                    <h1 data-page-title>@yield('title', 'Dashboard')</h1>
                </div>
                <div class="admin-header-right">
                    <div data-actions>@yield('actions')</div>
                    @include('partials.admin-user-menu')
                </div>
            </header>

            <div class="admin-content">
                @if(session('success'))
                    <div class="alert alert-success">
                        <span class="material-symbols-outlined" style="font-size:18px;">check_circle</span>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">
                        <span class="material-symbols-outlined" style="font-size:18px;">error</span>
                        {{ session('error') }}
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }
        function closeSidebar() {
            document.getElementById('adminSidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('active');
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeSidebar();
        });
    </script>
    @stack('scripts')
    <script src="{{ asset('js/lazy-load.js') }}"></script>
    {{-- Delegated admin widgets. Loaded once here (outside .admin-content) so
         they survive the partial navigation in resources/js/spa.js, which
         swaps .admin-content via innerHTML and never executes scripts that
         arrive with it. --}}
    <script src="{{ asset('js/admin-widgets.js') }}"></script>
    @vite('resources/js/app.js')
</body>
</html>
