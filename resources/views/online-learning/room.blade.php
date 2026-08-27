<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
<body data-skeleton-theme="dark" class="bg-slate-950 text-slate-100 font-sans antialiased">

    <a href="#room-panel"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:px-4 focus:py-2 focus:rounded-xl focus:bg-indigo-600 focus:text-white focus:text-xs focus:font-bold">
        Skip to class content
    </a>

    {{--
        The room is one screen with three regions: a header carrying identity and
        live state, a stage that owns the join action, and a panel area whose
        contents are fetched only when opened. JS fills each region; Blade's job
        is the shell and the bootstrap payload.
    --}}
    <div id="class-room" class="min-h-screen flex flex-col">

        <header id="room-header" class="flex-shrink-0 border-b border-slate-800 bg-slate-900/80 backdrop-blur-md">
            {{-- Skeleton until bootstrap resolves, so the first paint is never blank --}}
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4" aria-hidden="true">
                <div class="skeleton skeleton-text" style="width:112px;margin-bottom:10px"></div>
                <div class="skeleton skeleton-title" style="width:288px"></div>
            </div>
        </header>

        <div class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-6 space-y-6">
            <section id="room-stage" aria-label="Class stage">
                <div class="skeleton" style="height:192px;border-radius:16px" aria-hidden="true"></div>
            </section>

            <nav id="room-tabs" class="flex gap-1 overflow-x-auto" aria-label="Room sections"></nav>

            <main id="room-panel" tabindex="-1" aria-live="polite" class="min-h-[18rem]">
                <div class="skeleton" style="height:256px;border-radius:16px" aria-hidden="true"></div>
            </main>
        </div>
    </div>

    <div id="room-toasts" role="status" aria-live="polite"
         class="fixed top-4 right-4 z-50 flex flex-col gap-3 max-w-[calc(100vw-2rem)] sm:max-w-sm pointer-events-none"></div>

    @php
        $user = auth()->user();
    @endphp

    {{--
        Bootstrap only: which room, who is asking, and the CSRF token. Roles,
        capabilities and the meeting link all come from the API, which derives
        them from the session — never from anything published here.
    --}}
    <script>
        window.ClassRoomConfig = {
            roomKey: @json($roomKey),
            user: { id: {{ (int) $user->id }}, name: @json($user->name) },
            csrfToken: @json(csrf_token()),
            baseUrl: @json(url('/')),
            branding: {
                name: @json(config('online-learning.branding.institute_name')),
            },
            // Where to send someone who cannot be here, or who leaves.
            exitUrl: @json($user->is_admin
                ? url('/admin/online-learning/classes')
                : (($user->trainer ?? null) ? url('/teacher/online-learning/classes') : url('/student/learning/classes'))),
        };
    </script>
</body>
</html>
