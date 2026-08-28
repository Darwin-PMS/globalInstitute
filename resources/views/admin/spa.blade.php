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
                    <div class="header-user">
                        <div class="header-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
                        <span class="header-user-name">{{ auth()->user()->name ?? 'Admin' }}</span>
                    </div>
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
