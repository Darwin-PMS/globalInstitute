@extends('layouts.admin')

@section('title', 'Student Progress')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <h1>Student Progress</h1>
        <p class="page-header-desc">Track student enrollments and learning progress.</p>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <form method="GET" action="{{ route('admin.student-progress.index') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;width:100%;">
            <div class="search-wrap">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="search" class="search-input" placeholder="Search students..." value="{{ request('search') }}">
                @if(request('search'))
                <a href="{{ route('admin.student-progress.index', array_filter(['course_id' => request('course_id')])) }}" class="search-clear">
                    <span class="material-symbols-outlined" style="font-size:16px;">close</span>
                </a>
                @endif
            </div>
            <select name="course_id" class="filter-select" onchange="this.form.submit()">
                <option value="">All Courses</option>
                @foreach($courses ?? [] as $course)
                    <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>

@if(request('search') || request('course_id'))
<div class="active-filters">
    <span class="active-filters-label">Active Filters:</span>
    @if(request('search'))
    <span class="filter-tag">Search: {{ request('search') }} <a href="{{ route('admin.student-progress.index', array_filter(['course_id' => request('course_id')])) }}">&times;</a></span>
    @endif
    @if(request('course_id'))
    <span class="filter-tag">Course: {{ $courses->firstWhere('id', request('course_id'))?->title ?? request('course_id') }} <a href="{{ route('admin.student-progress.index', array_filter(['search' => request('search')])) }}">&times;</a></span>
    @endif
    <a href="{{ route('admin.student-progress.index') }}" class="clear-all-filters">Clear All</a>
</div>
@endif

<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Course</th>
                    <th>Batch</th>
                    <th>Enrolled Date</th>
                    <th>Lessons Completed</th>
                    <th>Progress %</th>
                    <th style="width:120px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($enrollments ?? [] as $enrollment)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:50%;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                                    @if($enrollment->student->photo)
                                        <img src="{{ asset('storage/'.$enrollment->student->photo) }}" alt="{{ $enrollment->student->name ?? '' }}" style="width:100%;height:100%;object-fit:cover;">
                                    @else
                                        <span class="material-symbols-outlined" style="font-size:14px;color:var(--gray-400);">person</span>
                                    @endif
                                </div>
                                <div>
                                    <div style="font-weight:600;color:var(--gray-900);font-size:13px;">{{ $enrollment->student->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $enrollment->course->title ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $enrollment->batch->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $enrollment->created_at ? $enrollment->created_at->format('d M Y') : 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--gray-600);">{{ $enrollment->lessons_completed ?? 0 }} / {{ $enrollment->total_lessons ?? 0 }}</span>
                        </td>
                        <td>
                            @php $progress = $enrollment->total_lessons > 0 ? round(($enrollment->lessons_completed / $enrollment->total_lessons) * 100) : 0; @endphp
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="flex:1;max-width:80px;height:6px;border-radius:3px;background:var(--gray-200);overflow:hidden;">
                                    <div style="height:100%;width:{{ $progress }}%;border-radius:3px;background:{{ $progress >= 75 ? 'var(--success)' : ($progress >= 40 ? 'var(--warning)' : 'var(--primary)') }};"></div>
                                </div>
                                <span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--gray-600);min-width:36px;">{{ $progress }}%</span>
                            </div>
                        </td>
                        <td style="text-align:right;">
                            <a href="{{ route('admin.student-progress.show', $enrollment->id) }}" class="icon-btn" title="View Detail" style="color:var(--primary);">
                                <span class="material-symbols-outlined">visibility</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:60px 0;text-align:center;">
                            <div class="empty-state">
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">trending_up</span>
                                <h3>{{ request('search') || request('course_id') ? 'No students found' : 'No enrollments yet' }}</h3>
                                <p>{{ request('search') || request('course_id') ? 'Try changing your search or filters.' : 'Enroll students to track their progress.' }}</p>
                                @if(request('search') || request('course_id'))
                                    <a href="{{ route('admin.student-progress.index') }}" class="btn btn-outline btn-sm">Clear Filters</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($enrollments->hasPages())
    <div class="card-footer">
        <div class="pagination-wrap" style="border-top:none;margin-top:0;padding-top:0;">
            <div class="pagination-info">
                Showing {{ $enrollments->firstItem() }} to {{ $enrollments->lastItem() }} of {{ $enrollments->total() }} students
            </div>
            <div class="pagination">
                @if($enrollments->onFirstPage())
                <span class="page-item disabled"><span class="page-link">Previous</span></span>
                @else
                <span class="page-item"><a class="page-link" href="{{ $enrollments->previousPageUrl() }}">Previous</a></span>
                @endif

                @foreach($enrollments->getUrlRange(max(1, $enrollments->currentPage() - 2), min($enrollments->lastPage(), $enrollments->currentPage() + 2)) as $page => $url)
                <span class="page-item {{ $page == $enrollments->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </span>
                @endforeach

                @if($enrollments->currentPage() < $enrollments->lastPage())
                <span class="page-item"><a class="page-link" href="{{ $enrollments->nextPageUrl() }}">Next</a></span>
                @else
                <span class="page-item disabled"><span class="page-link">Next</span></span>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
