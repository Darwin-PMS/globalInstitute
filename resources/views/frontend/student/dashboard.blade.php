@extends('layouts.frontend')

@section('title', 'Student Dashboard')

@section('content')
<section class="section">
    <div class="container">

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-text">
                <h2>Welcome back, {{ Auth::user()->name ?? 'Student' }}!</h2>
                <p>Continue your learning journey. Here's what's happening today.</p>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <span class="material-symbols-outlined">school</span>
                <div class="stat-info">
                    <h3>{{ $enrolledCoursesCount ?? 0 }}</h3>
                    <p>Enrolled Courses</p>
                </div>
            </div>
            <div class="stat-card">
                <span class="material-symbols-outlined">class</span>
                <div class="stat-info">
                    <h3>{{ $todayClassesCount ?? 0 }}</h3>
                    <p>Today's Classes</p>
                </div>
            </div>
            <div class="stat-card">
                <span class="material-symbols-outlined">check_circle</span>
                <div class="stat-info">
                    <h3>{{ $completedLessonsCount ?? 0 }}</h3>
                    <p>Completed Lessons</p>
                </div>
            </div>
            <div class="stat-card">
                <span class="material-symbols-outlined">trending_up</span>
                <div class="stat-info">
                    <h3>{{ $overallProgress ?? 0 }}%</h3>
                    <p>Overall Progress</p>
                </div>
            </div>
        </div>

        <!-- Today's Classes -->
        <div class="section-header">
            <h3>Today's Classes</h3>
        </div>
        <div class="cards-grid">
            @forelse($todayClasses ?? [] as $class)
                <div class="card class-card">
                    <div class="card-body">
                        <h4 class="card-title">{{ $class->title }}</h4>
                        <div class="class-meta">
                            <span class="class-time">
                                <span class="material-symbols-outlined">schedule</span>
                                {{ $class->start_time }} - {{ $class->end_time }}
                            </span>
                            <span class="class-teacher">
                                <span class="material-symbols-outlined">person</span>
                                {{ $class->teacher->name ?? 'N/A' }}
                            </span>
                        </div>
                        <a href="{{ $class->meeting_url ?? '#' }}" class="btn btn-primary btn-sm" target="_blank">
                            <span class="material-symbols-outlined">videocam</span> Join Meeting
                        </a>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <span class="material-symbols-outlined">event_busy</span>
                    <p>No classes scheduled for today.</p>
                </div>
            @endforelse
        </div>

        <!-- Upcoming Classes -->
        <div class="section-header">
            <h3>Upcoming Classes</h3>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Course</th>
                        <th>Teacher</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($upcomingClasses ?? [] as $class)
                        <tr>
                            <td>{{ $class->date ? \Carbon\Carbon::parse($class->date)->format('M d, Y') : 'N/A' }}</td>
                            <td>{{ $class->start_time }} - {{ $class->end_time }}</td>
                            <td>{{ $class->course->title ?? 'N/A' }}</td>
                            <td>{{ $class->teacher->name ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No upcoming classes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pending Assignments -->
        <div class="section-header">
            <h3>Pending Assignments</h3>
        </div>
        <div class="cards-grid">
            @forelse($pendingAssignments ?? [] as $assignment)
                <div class="card assignment-card">
                    <div class="card-body">
                        <h4 class="card-title">{{ $assignment->title }}</h4>
                        <p class="card-text">
                            <span class="material-symbols-outlined">book</span> {{ $assignment->course->title ?? 'N/A' }}
                        </p>
                        <p class="card-text due-date">
                            <span class="material-symbols-outlined">calendar_today</span>
                            Due: {{ $assignment->due_date ? \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') : 'N/A' }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <span class="material-symbols-outlined">assignment_turned_in</span>
                    <p>No pending assignments. Great job!</p>
                </div>
            @endforelse
        </div>

        <!-- Recent Recordings -->
        <div class="section-header">
            <h3>Recent Recordings</h3>
        </div>
        <div class="cards-grid">
            @forelse($recentRecordings ?? [] as $recording)
                <div class="card recording-card">
                    <div class="card-body">
                        <h4 class="card-title">{{ $recording->title }}</h4>
                        <p class="card-text">
                            <span class="material-symbols-outlined">book</span> {{ $recording->class->course->title ?? 'N/A' }}
                        </p>
                        <p class="card-text">
                            <span class="material-symbols-outlined">timer</span> {{ $recording->duration ?? 'N/A' }}
                        </p>
                        <a href="{{ $recording->url ?? '#' }}" class="btn btn-primary btn-sm" target="_blank">
                            <span class="material-symbols-outlined">play_circle</span> Watch
                        </a>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <span class="material-symbols-outlined">videocam_off</span>
                    <p>No recordings available yet.</p>
                </div>
            @endforelse
        </div>

        <!-- Quick Links -->
        <div class="section-header">
            <h3>Quick Links</h3>
        </div>
        <div class="quick-links-grid">
            <a href="{{ route('student.my-courses') }}" class="quick-link-card">
                <span class="material-symbols-outlined">menu_book</span>
                <span>My Courses</span>
            </a>
            <a href="{{ route('student.my-classes') }}" class="quick-link-card">
                <span class="material-symbols-outlined">class</span>
                <span>My Classes</span>
            </a>
            <a href="{{ route('student.my-assignments') }}" class="quick-link-card">
                <span class="material-symbols-outlined">assignment</span>
                <span>Assignments</span>
            </a>
            <a href="{{ route('student.my-quizzes') }}" class="quick-link-card">
                <span class="material-symbols-outlined">quiz</span>
                <span>Quizzes</span>
            </a>
            <a href="{{ route('student.my-recordings') }}" class="quick-link-card">
                <span class="material-symbols-outlined">videocam</span>
                <span>Recordings</span>
            </a>
        </div>

    </div>
</section>
@endsection
