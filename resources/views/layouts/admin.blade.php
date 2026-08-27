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
                    <div class="nav-section-label">Main</div>
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">dashboard</span>
                        <span class="nav-text">Dashboard</span>
                    </a>

                    <div class="nav-section-label">Content</div>
                    <a href="{{ route('admin.courses.index') }}" class="nav-link {{ request()->routeIs('admin.courses.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">school</span>
                        <span class="nav-text">Courses</span>
                    </a>
                    <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">category</span>
                        <span class="nav-text">Categories</span>
                    </a>
                    <a href="{{ route('admin.gallery.index') }}" class="nav-link {{ request()->routeIs('admin.gallery.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">photo_library</span>
                        <span class="nav-text">Gallery</span>
                    </a>
                    <a href="{{ route('admin.testimonials.index') }}" class="nav-link {{ request()->routeIs('admin.testimonials.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">reviews</span>
                        <span class="nav-text">Testimonials</span>
                    </a>
                    <a href="{{ route('admin.faqs.index') }}" class="nav-link {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">help</span>
                        <span class="nav-text">FAQs</span>
                    </a>
                    <a href="{{ route('admin.pages.index') }}" class="nav-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">description</span>
                        <span class="nav-text">Pages</span>
                    </a>

                    <div class="nav-section-label">CRM</div>
                    <a href="{{ route('admin.enquiries.index') }}" class="nav-link {{ request()->routeIs('admin.enquiries.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">contact_page</span>
                        <span class="nav-text">Enquiries</span>
                        @php $pending = \App\Models\Enquiry::where('status','new')->count(); @endphp
                        @if($pending > 0)
                        <span class="nav-badge">{{ $pending }}</span>
                        @endif
                    </a>
                    <a href="{{ route('admin.admissions.index') }}" class="nav-link {{ request()->routeIs('admin.admissions.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">how_to_reg</span>
                        <span class="nav-text">Admissions</span>
                    </a>

                    <div class="nav-section-label">Academic</div>
                    <a href="{{ route('admin.teachers.index') }}" class="nav-link {{ request()->routeIs('admin.teachers.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">groups</span>
                        <span class="nav-text">Teachers</span>
                    </a>
                    <a href="{{ route('admin.batches.index') }}" class="nav-link {{ request()->routeIs('admin.batches.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">calendar_month</span>
                        <span class="nav-text">Batches</span>
                    </a>
                    <a href="{{ route('admin.rooms.index') }}" class="nav-link {{ request()->routeIs('admin.rooms.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">meeting_room</span>
                        <span class="nav-text">Rooms</span>
                    </a>
                    <a href="{{ route('admin.course-modules.index') }}" class="nav-link {{ request()->routeIs('admin.course-modules.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">view_module</span>
                        <span class="nav-text">Modules</span>
                    </a>
                    <a href="{{ route('admin.fee-structures.index') }}" class="nav-link {{ request()->routeIs('admin.fee-structures.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">account_balance_wallet</span>
                        <span class="nav-text">Fee Structure</span>
                    </a>

                    <div class="nav-section-label">Students</div>
                    <a href="{{ route('admin.students.index') }}" class="nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">person</span>
                        <span class="nav-text">Students</span>
                    </a>
                    <a href="{{ route('admin.enrollments.index') }}" class="nav-link {{ request()->routeIs('admin.enrollments.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">badge</span>
                        <span class="nav-text">Enrollments</span>
                    </a>
                    <a href="{{ route('admin.payments.index') }}" class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">payments</span>
                        <span class="nav-text">Payments</span>
                    </a>

                    <div class="nav-section-label">Online Learning</div>
                    <a href="{{ route('admin.live-classroom.index') }}" class="nav-link {{ request()->routeIs('admin.live-classroom.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">live_tv</span>
                        <span class="nav-text">Live Classroom</span>
                    </a>
                    <a href="{{ route('admin.online-classes.index') }}" class="nav-link {{ request()->routeIs('admin.online-classes.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">videocam</span>
                        <span class="nav-text">Schedule Classes</span>
                    </a>
                    <a href="{{ route('admin.lessons.index') }}" class="nav-link {{ request()->routeIs('admin.lessons.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">menu_book</span>
                        <span class="nav-text">Lessons</span>
                    </a>
                    <a href="{{ route('admin.materials.index') }}" class="nav-link {{ request()->routeIs('admin.materials.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">folder_open</span>
                        <span class="nav-text">Materials</span>
                    </a>
                    <a href="{{ route('admin.assignments.index') }}" class="nav-link {{ request()->routeIs('admin.assignments.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">assignment</span>
                        <span class="nav-text">Assignments</span>
                    </a>
                    <a href="{{ route('admin.quizzes.index') }}" class="nav-link {{ request()->routeIs('admin.quizzes.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">quiz</span>
                        <span class="nav-text">Quizzes</span>
                    </a>
                    <a href="{{ route('admin.recordings.index') }}" class="nav-link {{ request()->routeIs('admin.recordings.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">video_library</span>
                        <span class="nav-text">Recordings</span>
                    </a>
                    <a href="{{ route('admin.student-progress.index') }}" class="nav-link {{ request()->routeIs('admin.student-progress.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">trending_up</span>
                        <span class="nav-text">Progress</span>
                    </a>

                    <div class="nav-section-label">Reports</div>
                    <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">analytics</span>
                        <span class="nav-text">Reports</span>
                    </a>

                    <div class="nav-section-label">System</div>
                    <a href="{{ route('admin.settings.edit') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined">settings</span>
                        <span class="nav-text">Settings</span>
                    </a>
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
                    <div class="header-user">
                        <div class="header-user-avatar">A</div>
                        <span class="header-user-name">Admin</span>
                        <span class="material-symbols-outlined">expand_more</span>
                    </div>
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
    @vite('resources/js/app.js')
</body>
</html>
