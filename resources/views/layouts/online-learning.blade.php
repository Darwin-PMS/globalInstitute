<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="dark">
    <title>Online Learning Portal | Global Institute &amp; CAD CAM</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    @vite(['resources/css/app.css', 'resources/js/online-learning/app.js'])
</head>
<body data-skeleton-theme="dark" class="bg-slate-950 text-slate-100 font-sans antialiased selection:bg-indigo-500 selection:text-white">

    {{-- Keyboard users land here first and can jump straight past the nav (§40) --}}
    <a href="#learning-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:px-4 focus:py-2 focus:rounded-xl focus:bg-indigo-600 focus:text-white focus:text-xs focus:font-bold">
        Skip to main content
    </a>

    <div id="online-learning-app" class="flex h-screen w-full overflow-hidden bg-slate-950">

        {{--
            Sidebar: a normal column from lg up, an off-canvas drawer below it.
            The `-translate-x-full` default is what makes it hidden on mobile;
            the JS toggle in Topbar flips `data-open` on the app root.
        --}}
        <aside id="learning-sidebar"
               aria-label="Online Learning navigation"
               class="fixed lg:static inset-y-0 left-0 z-40 w-64 flex-shrink-0 bg-slate-900 border-r border-slate-800
                      flex flex-col transition-transform duration-300 ease-out
                      -translate-x-full lg:translate-x-0"></aside>

        {{-- Scrim for the mobile drawer; click anywhere to dismiss --}}
        <div id="learning-sidebar-scrim"
             class="fixed inset-0 z-30 bg-slate-950/70 backdrop-blur-sm lg:hidden hidden"
             aria-hidden="true"></div>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
            <header id="learning-topbar"
                    class="h-16 flex-shrink-0 bg-slate-900/80 backdrop-blur-md border-b border-slate-800
                           flex items-center justify-between gap-3 px-4 sm:px-6 z-20"></header>

            <main id="learning-content"
                  tabindex="-1"
                  class="flex-1 overflow-y-auto p-4 sm:p-6 bg-slate-950 text-slate-100 scroll-smooth"></main>
        </div>
    </div>

    <div id="learning-overlay-container"></div>
    <div id="learning-toast-container"
         role="status" aria-live="polite"
         class="fixed top-4 right-4 z-[70] flex flex-col gap-3 max-w-[calc(100vw-2rem)] sm:max-w-sm pointer-events-none"></div>

    @php
        $user = auth()->user();
        $teacher = $user->trainer;
        $student = $user->student;
        $role = $user->learningRole();
    @endphp

    {{--
        Bootstrap only. Blade hands JavaScript the identity, CSRF token and
        configuration it needs to start; every authorisation decision is still
        made server-side on each request (§45, §48).
    --}}
    <script>
        window.OnlineLearningConfig = {
            user: {
                id: {{ (int) $user->id }},
                name: @json($user->name),
                email: @json($user->email),
                is_admin: {{ $user->is_admin ? 'true' : 'false' }}
            },
            role: @json($role),
            student: @json($student ? ['id' => $student->id] : null),
            teacher: @json($teacher ? ['id' => $teacher->id, 'name' => $teacher->name] : null),
            csrfToken: @json(csrf_token()),
            baseUrl: @json(url('/')),
            currentPath: @json(request()->getPathInfo()),
            joinWindowMinutes: {{ (int) config('online-learning.join_window.before_start_minutes', 15) }}
        };
    </script>
</body>
</html>
