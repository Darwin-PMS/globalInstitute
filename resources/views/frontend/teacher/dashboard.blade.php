@extends('layouts.frontend')

@section('title', 'Teacher Dashboard')

@section('content')
<div class="container-fluid py-4">

    <!-- Welcome Banner -->
    <div class="card bg-gradient-primary text-white mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="mb-1">Welcome, {{ Auth::user()->name }}!</h3>
                    <p class="mb-0 opacity-75">Here's an overview of your teaching activities.</p>
                </div>
                <div>
                    <span class="material-symbols-outlined" style="font-size: 4rem;">school</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-lg bg-primary bg-opacity-10 rounded-3">
                                <span class="material-symbols-outlined text-primary">class</span>
                            </div>
                        </div>
                        <div>
                            <h4 class="mb-0">{{ $totalClasses ?? 0 }}</h4>
                            <small class="text-muted">Total Classes</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-lg bg-success bg-opacity-10 rounded-3">
                                <span class="material-symbols-outlined text-success">check_circle</span>
                            </div>
                        </div>
                        <div>
                            <h4 class="mb-0">{{ $completedClasses ?? 0 }}</h4>
                            <small class="text-muted">Completed Classes</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-lg bg-info bg-opacity-10 rounded-3">
                                <span class="material-symbols-outlined text-info">people</span>
                            </div>
                        </div>
                        <div>
                            <h4 class="mb-0">{{ $totalStudents ?? 0 }}</h4>
                            <small class="text-muted">Total Students</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-lg bg-warning bg-opacity-10 rounded-3">
                                <span class="material-symbols-outlined text-warning">pending_actions</span>
                            </div>
                        </div>
                        <div>
                            <h4 class="mb-0">{{ $pendingSubmissions ?? 0 }}</h4>
                            <small class="text-muted">Pending Submissions</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Classes -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0">
                    <span class="material-symbols-outlined me-2 align-middle">today</span>
                    Today's Classes
                </h5>
            </div>
        </div>
        <div class="card-body">
            @if(isset($todayClasses) && count($todayClasses) > 0)
                <div class="row g-3">
                    @foreach($todayClasses as $class)
                        <div class="col-lg-4 col-md-6">
                            <div class="card border h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">{{ $class->title }}</h6>
                                        @if($class->status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($class->status === 'ongoing')
                                            <span class="badge bg-primary">Ongoing</span>
                                        @else
                                            <span class="badge bg-secondary">Upcoming</span>
                                        @endif
                                    </div>
                                    <div class="mt-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="material-symbols-outlined text-muted me-2" style="font-size: 1.1rem;">schedule</span>
                                            <small class="text-muted">{{ $class->start_time }} - {{ $class->end_time }}</small>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="material-symbols-outlined text-muted me-2" style="font-size: 1.1rem;">batch</span>
                                            <small class="text-muted">{{ $class->batch->name ?? 'N/A' }}</small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="material-symbols-outlined text-muted me-2" style="font-size: 1.1rem;">menu_book</span>
                                            <small class="text-muted">{{ $class->course->name ?? 'N/A' }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-top">
                                    @if($class->status === 'completed')
                                        <a href="{{ route('teacher.mark-attendance', $class->id) }}" class="btn btn-sm btn-outline-primary">
                                            <span class="material-symbols-outlined me-1" style="font-size: 1rem;">fact_check</span>
                                            View Attendance
                                        </a>
                                    @elseif($class->meeting_link)
                                        <a href="{{ $class->meeting_link }}" target="_blank" class="btn btn-sm btn-primary">
                                            <span class="material-symbols-outlined me-1" style="font-size: 1rem;">videocam</span>
                                            Join Meeting
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4">
                    <span class="material-symbols-outlined text-muted" style="font-size: 3rem;">event_busy</span>
                    <p class="text-muted mt-2 mb-0">No classes scheduled for today.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <!-- Pending Submissions -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">
                            <span class="material-symbols-outlined me-2 align-middle">assignment_late</span>
                            Pending Submissions
                        </h5>
                        <a href="{{ route('teacher.submissions') }}" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if(isset($pendingSubmissionsList) && count($pendingSubmissionsList) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($pendingSubmissionsList->take(5) as $submission)
                                <div class="list-group-item d-flex align-items-center justify-content-between py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm bg-primary bg-opacity-10 rounded-circle me-3">
                                            <span class="material-symbols-outlined text-primary" style="font-size: 1.2rem;">person</span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $submission->student->user->name ?? 'N/A' }}</h6>
                                            <small class="text-muted">{{ $submission->assignment->title ?? 'N/A' }}</small>
                                            <br>
                                            <small class="text-muted">
                                                <span class="material-symbols-outlined me-1" style="font-size: 0.9rem;">calendar_today</span>
                                                {{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y') : 'N/A' }}
                                            </small>
                                        </div>
                                    </div>
                                    <a href="{{ route('teacher.evaluate-submission', $submission->id) }}" class="btn btn-sm btn-warning">
                                        <span class="material-symbols-outlined me-1" style="font-size: 1rem;">grade</span>
                                        Evaluate
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <span class="material-symbols-outlined text-muted" style="font-size: 3rem;">task_alt</span>
                            <p class="text-muted mt-2 mb-0">No pending submissions.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pending Quiz Evaluations -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">
                            <span class="material-symbols-outlined me-2 align-middle">quiz</span>
                            Pending Quiz Evaluations
                        </h5>
                        <a href="{{ route('teacher.quiz-results') }}" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if(isset($pendingQuizEvaluations) && count($pendingQuizEvaluations) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($pendingQuizEvaluations->take(5) as $attempt)
                                <div class="list-group-item d-flex align-items-center justify-content-between py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm bg-info bg-opacity-10 rounded-circle me-3">
                                            <span class="material-symbols-outlined text-info" style="font-size: 1.2rem;">person</span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $attempt->student->user->name ?? 'N/A' }}</h6>
                                            <small class="text-muted">{{ $attempt->quiz->title ?? 'N/A' }}</small>
                                            <br>
                                            <small class="text-muted">
                                                <span class="material-symbols-outlined me-1" style="font-size: 0.9rem;">calendar_today</span>
                                                {{ $attempt->attempted_at ? $attempt->attempted_at->format('M d, Y') : 'N/A' }}
                                            </small>
                                        </div>
                                    </div>
                                    <a href="{{ route('teacher.quiz-results') }}" class="btn btn-sm btn-info text-white">
                                        <span class="material-symbols-outlined me-1" style="font-size: 1rem;">visibility</span>
                                        Evaluate
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <span class="material-symbols-outlined text-muted" style="font-size: 3rem;">task_alt</span>
                            <p class="text-muted mt-2 mb-0">No pending quiz evaluations.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">
                <span class="material-symbols-outlined me-2 align-middle">link</span>
                Quick Links
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="{{ route('teacher.my-classes') }}" class="btn btn-outline-primary w-100 py-3">
                        <span class="material-symbols-outlined d-block mb-2" style="font-size: 2rem;">class</span>
                        My Classes
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('teacher.submissions') }}" class="btn btn-outline-success w-100 py-3">
                        <span class="material-symbols-outlined d-block mb-2" style="font-size: 2rem;">assignment</span>
                        Submissions
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('teacher.quiz-results') }}" class="btn btn-outline-info w-100 py-3">
                        <span class="material-symbols-outlined d-block mb-2" style="font-size: 2rem;">quiz</span>
                        Quiz Results
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection