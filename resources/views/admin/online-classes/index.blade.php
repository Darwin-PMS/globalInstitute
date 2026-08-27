@extends('layouts.admin')

@section('title', 'Online Classes')

@section('actions')
    <a href="{{ route('admin.online-classes.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Class
    </a>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <h1>Online Classes</h1>
        <p class="page-header-desc">Schedule and manage live online classes for students.</p>
    </div>
</div>

<div class="stats-bar">
    <div class="stat-item">
        <span class="stat-value">{{ $totalClasses ?? 0 }}</span>
        <span class="stat-label">Total Classes</span>
    </div>
    <div class="stat-item">
        <span class="stat-value">{{ $scheduledClasses ?? 0 }}</span>
        <span class="stat-label">Scheduled</span>
    </div>
    <div class="stat-item">
        <span class="stat-value">{{ $completedClasses ?? 0 }}</span>
        <span class="stat-label">Completed</span>
    </div>
    <div class="stat-item">
        <span class="stat-value">{{ $cancelledClasses ?? 0 }}</span>
        <span class="stat-label">Cancelled</span>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <form method="GET" action="{{ route('admin.online-classes.index') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;width:100%;">
            <div class="search-wrap">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="search" class="search-input" placeholder="Search classes..." value="{{ request('search') }}">
                @if(request('search'))
                <a href="{{ route('admin.online-classes.index', array_filter(['status' => request('status'), 'course_id' => request('course_id'), 'date' => request('date')])) }}" class="search-clear">
                    <span class="material-symbols-outlined" style="font-size:16px;">close</span>
                </a>
                @endif
            </div>
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                <option value="live" {{ request('status') === 'live' ? 'selected' : '' }}>Live</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="postponed" {{ request('status') === 'postponed' ? 'selected' : '' }}>Postponed</option>
            </select>
            <select name="course_id" class="filter-select" onchange="this.form.submit()">
                <option value="">All Courses</option>
                @foreach($courses ?? [] as $course)
                    <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                @endforeach
            </select>
            <input type="date" name="date" class="filter-select" value="{{ request('date') }}" onchange="this.form.submit()">
        </form>
    </div>
</div>

@if(request('search') || request('status') || request('course_id') || request('date'))
<div class="active-filters">
    <span class="active-filters-label">Active Filters:</span>
    @if(request('search'))
    <span class="filter-tag">Search: {{ request('search') }} <a href="{{ route('admin.online-classes.index', array_filter(['status' => request('status'), 'course_id' => request('course_id'), 'date' => request('date')])) }}">&times;</a></span>
    @endif
    @if(request('status'))
    <span class="filter-tag">Status: {{ ucfirst(request('status')) }} <a href="{{ route('admin.online-classes.index', array_filter(['search' => request('search'), 'course_id' => request('course_id'), 'date' => request('date')])) }}">&times;</a></span>
    @endif
    @if(request('course_id'))
    <span class="filter-tag">Course: {{ $courses->firstWhere('id', request('course_id'))?->title ?? request('course_id') }} <a href="{{ route('admin.online-classes.index', array_filter(['search' => request('search'), 'status' => request('status'), 'date' => request('date')])) }}">&times;</a></span>
    @endif
    @if(request('date'))
    <span class="filter-tag">Date: {{ request('date') }} <a href="{{ route('admin.online-classes.index', array_filter(['search' => request('search'), 'status' => request('status'), 'course_id' => request('course_id')])) }}">&times;</a></span>
    @endif
    <a href="{{ route('admin.online-classes.index') }}" class="clear-all-filters">Clear All</a>
</div>
@endif

<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Title</th>
                    <th>Course</th>
                    <th>Batch</th>
                    <th>Teacher</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th style="width:120px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classes ?? [] as $class)
                    <tr>
                        <td style="color:var(--gray-400);font-family:'JetBrains Mono',monospace;font-size:12px;">#{{ $class->id }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:40px;height:40px;border-radius:8px;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <span class="material-symbols-outlined" style="font-size:18px;color:var(--gray-400);">videocam</span>
                                </div>
                                <div>
                                    <div style="font-weight:600;color:var(--gray-900);font-size:14px;">{{ $class->title }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $class->course->title ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $class->batch->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $class->teacher->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $class->class_date ? \Carbon\Carbon::parse($class->class_date)->format('d M Y') : 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">
                                @if($class->start_time && $class->end_time)
                                    {{ \Carbon\Carbon::parse($class->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($class->end_time)->format('h:i A') }}
                                @else
                                    N/A
                                @endif
                            </span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ ucfirst($class->meeting_provider ?? 'N/A') }}</span>
                        </td>
                        <td>
                            @if($class->status === 'scheduled')
                                <span class="badge badge-info">Scheduled</span>
                            @elseif($class->status === 'live')
                                <span class="badge badge-success">Live</span>
                            @elseif($class->status === 'completed')
                                <span class="badge badge-primary">Completed</span>
                            @elseif($class->status === 'cancelled')
                                <span class="badge badge-danger">Cancelled</span>
                            @elseif($class->status === 'postponed')
                                <span class="badge badge-warning">Postponed</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($class->status ?? '') }}</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div class="action-menu">
                                <button class="action-menu-trigger" onclick="toggleDropdown(this)" aria-label="Actions">
                                    <span class="material-symbols-outlined">more_horiz</span>
                                </button>
                                <div class="action-dropdown">
                                    <a href="{{ route('admin.online-classes.edit', $class->id) }}" class="action-dropdown-item">
                                        <span class="material-symbols-outlined">edit</span> Edit
                                    </a>
                                    <div class="action-dropdown-divider"></div>
                                    <form method="POST" action="{{ route('admin.online-classes.destroy', $class->id) }}" onsubmit="return confirm('Delete this class?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-dropdown-item action-dropdown-danger">
                                            <span class="material-symbols-outlined">delete</span> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="padding:60px 0;text-align:center;">
                            <div class="empty-state">
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">videocam</span>
                                <h3>{{ request('search') || request('status') || request('course_id') || request('date') ? 'No classes found' : 'No classes yet' }}</h3>
                                <p>{{ request('search') || request('status') || request('course_id') || request('date') ? 'Try changing your search or filters.' : 'Schedule your first class to get started.' }}</p>
                                <div class="empty-state-actions">
                                    @if(request('search') || request('status') || request('course_id') || request('date'))
                                        <a href="{{ route('admin.online-classes.index') }}" class="btn btn-outline btn-sm">Clear Filters</a>
                                    @endif
                                    <a href="{{ route('admin.online-classes.create') }}" class="btn btn-primary btn-sm">
                                        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Class
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($classes->hasPages())
    <div class="card-footer">
        <div class="pagination-wrap" style="border-top:none;margin-top:0;padding-top:0;">
            <div class="pagination-info">
                Showing {{ $classes->firstItem() }} to {{ $classes->lastItem() }} of {{ $classes->total() }} classes
            </div>
            <div class="pagination">
                @if($classes->onFirstPage())
                <span class="page-item disabled"><span class="page-link">Previous</span></span>
                @else
                <span class="page-item"><a class="page-link" href="{{ $classes->previousPageUrl() }}">Previous</a></span>
                @endif

                @foreach($classes->getUrlRange(max(1, $classes->currentPage() - 2), min($classes->lastPage(), $classes->currentPage() + 2)) as $page => $url)
                <span class="page-item {{ $page == $classes->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </span>
                @endforeach

                @if($classes->currentPage() < $classes->lastPage())
                <span class="page-item"><a class="page-link" href="{{ $classes->nextPageUrl() }}">Next</a></span>
                @else
                <span class="page-item disabled"><span class="page-link">Next</span></span>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function toggleDropdown(btn) {
    const dropdown = btn.nextElementSibling;
    document.querySelectorAll('.action-dropdown.open').forEach(d => { if (d !== dropdown) d.classList.remove('open'); });
    dropdown.classList.toggle('open');
}
document.addEventListener('click', function(e) { if (!e.target.closest('.action-menu')) document.querySelectorAll('.action-dropdown.open').forEach(d => d.classList.remove('open')); });
</script>
@endpush
