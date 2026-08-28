<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="dark">
    <meta name="robots" content="noindex, nofollow">
    <title>Live Class Room | {{ config('online-learning.branding.institute_name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    @vite(['resources/css/app.css', 'resources/js/online-learning/room/app.js'])
</head>
<body data-skeleton-theme="dark" class="bg-cr-base text-white font-sans antialiased overflow-hidden">

    <a href="#room-stage"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:px-4 focus:py-2 focus:rounded-xl focus:bg-indigo-600 focus:text-white focus:text-xs focus:font-bold">
        Skip to classroom
    </a>

    {{--
        Live Classroom Shell
        ====================
        Layout: Header → Main Workspace (Stage + Sidebar) → Bottom Control Bar

        The classroom is a single-screen application. The instructor must never
        need to leave this screen to conduct the entire session.
    --}}
    <div id="class-room" class="cr-shell">

        {{-- ─── Top Header ─────────────────────────────────────── --}}
        <header id="room-header" class="cr-header" role="banner">
            <div class="cr-header-inner">
                {{-- Left: Back + Identity --}}
                <div class="cr-header-left">
                    <a href="#" data-room-exit
                       class="cr-header-back" aria-label="Leave classroom">
                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_back</span>
                    </a>

                    <div class="cr-header-identity">
                        <div class="cr-header-tags">
                            <span id="header-state-badge"></span>
                            <span id="header-context" class="cr-header-context"></span>
                        </div>
                        <h1 id="header-title" class="cr-header-title"></h1>
                    </div>
                </div>

                {{-- Center: Live info --}}
                <div class="cr-header-center">
                    <span id="header-live-badge" class="cr-live-badge" style="display:none">
                        <span class="cr-live-dot"></span>
                        LIVE
                    </span>
                    <span id="header-timer" class="cr-header-timer">00:00:00</span>
                    <span class="cr-header-sep"></span>
                    <span id="header-participants" class="cr-header-meta">
                        <span class="material-symbols-outlined text-[14px]" aria-hidden="true">groups</span>
                        <span id="header-online-count">0</span>
                    </span>
                </div>

                {{-- Right: Connection + Actions --}}
                <div class="cr-header-right">
                    <span id="header-connection" class="cr-connection-badge" data-quality="excellent">
                        <span class="cr-connection-bars">
                            <span class="bar bar-1"></span>
                            <span class="bar bar-2"></span>
                            <span class="bar bar-3"></span>
                            <span class="bar bar-4"></span>
                        </span>
                        <span id="header-connection-label">Excellent</span>
                    </span>

                    <button id="header-settings-btn" class="cr-header-action" data-tooltip="Settings" aria-label="Settings">
                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">settings</span>
                    </button>

                    <button id="header-more-btn" class="cr-header-action" data-tooltip="More" aria-label="More options">
                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">more_vert</span>
                    </button>

                    <button id="header-end-btn" class="cr-header-end-btn" style="display:none" aria-label="End class">
                        End Class
                    </button>
                </div>
            </div>
        </header>

        {{-- ─── Main Workspace ─────────────────────────────────── --}}
        <div class="cr-workspace">

            {{-- Video / Content Stage --}}
            <main id="room-stage" class="cr-stage" aria-label="Classroom stage" tabindex="-1">
                {{-- Skeleton until bootstrap resolves --}}
                <div class="cr-stage-skeleton" aria-hidden="true">
                    <div class="skeleton" style="width:100%;height:100%;border-radius:16px"></div>
                </div>
            </main>

            {{-- Contextual Sidebar --}}
            <aside id="room-sidebar" class="cr-sidebar" role="complementary" aria-label="Classroom panels">
                {{-- Sidebar Tab Bar --}}
                <nav id="sidebar-tabs" class="cr-sidebar-tabs" role="tablist" aria-label="Classroom sections">
                    {{-- Tabs rendered by JS --}}
                </nav>

                {{-- Sidebar Panel Content --}}
                <div id="sidebar-panel" class="cr-sidebar-panel" tabindex="-1" aria-live="polite">
                    {{-- Panel content rendered by JS --}}
                </div>
            </aside>
        </div>

        {{-- ─── Bottom Control Bar ─────────────────────────────── --}}
        <footer id="room-controls" class="cr-controls" role="toolbar" aria-label="Classroom controls">
            <div class="cr-controls-inner">
                {{-- Left section: Quick info --}}
                <div class="cr-controls-left">
                    <div id="ctrl-recording" class="cr-ctrl-recording" style="display:none" aria-label="Recording">
                        <span class="cr-rec-dot"></span>
                        <span class="cr-rec-label">REC</span>
                        <span id="ctrl-rec-timer" class="cr-rec-timer">00:00:00</span>
                    </div>
                </div>

                {{-- Center: Primary controls --}}
                <div id="ctrl-buttons" class="cr-controls-center">
                    {{-- Rendered by JS based on role --}}
                </div>

                {{-- Right: Sidebar toggles --}}
                <div class="cr-controls-right">
                    <button id="ctrl-toggle-sidebar" class="cr-ctrl-toggle" data-tooltip="Toggle panel" aria-label="Toggle sidebar panel">
                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">sidebar</span>
                    </button>
                </div>
            </div>
        </footer>
    </div>

    {{-- ─── Mobile Bottom Sheet ────────────────────────────────── --}}
    <div id="mobile-sheet" class="cr-mobile-sheet" role="dialog" aria-modal="true" aria-label="Classroom panel" style="display:none">
        <div class="cr-mobile-sheet-backdrop" data-mobile-close></div>
        <div class="cr-mobile-sheet-content">
            <div class="cr-mobile-sheet-handle" data-mobile-close></div>
            <div id="mobile-sheet-header" class="cr-mobile-sheet-header">
                <h2 id="mobile-sheet-title" class="cr-mobile-sheet-title"></h2>
                <button data-mobile-close class="cr-mobile-sheet-close" aria-label="Close">
                    <span class="material-symbols-outlined text-[20px]" aria-hidden="true">close</span>
                </button>
            </div>
            <div id="mobile-sheet-body" class="cr-mobile-sheet-body"></div>
        </div>
    </div>

    {{-- ─── Toast Container ────────────────────────────────────── --}}
    <div id="room-toasts" role="status" aria-live="polite"
         class="cr-toasts"></div>

    {{-- ─── Modals Container ───────────────────────────────────── --}}
    <div id="room-modals"></div>

    @php
        $user = auth()->user();
    @endphp

    <script>
        window.ClassRoomConfig = {
            roomKey: @json($roomKey),
            user: { id: {{ (int) $user->id }}, name: @json($user->name) },
            csrfToken: @json(csrf_token()),
            baseUrl: @json(url('/')),
            branding: {
                name: @json(config('online-learning.branding.institute_name')),
            },
            exitUrl: @json($user->is_admin
                ? url('/admin/live-classroom')
                : (($user->trainer ?? null) ? url('/teacher/online-learning/classes') : url('/student/learning/classes'))),
        };
    </script>
</body>
</html>
