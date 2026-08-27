@extends('layouts.admin')

@section('title', 'Lessons')

@section('actions')
    <a href="{{ route('admin.lessons.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Lesson
    </a>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <h1>Lessons</h1>
        <p class="page-header-desc">Manage course lessons, modules, and content.</p>
    </div>
</div>

<div class="stats-bar">
    <div class="stat-item">
        <span class="stat-value">{{ $totalLessons ?? 0 }}</span>
        <span class="stat-label">Total Lessons</span>
    </div>
    <div class="stat-item">
        <span class="stat-value">{{ $publishedLessons ?? 0 }}</span>
        <span class="stat-label">Published</span>
    </div>
    <div class="stat-item">
        <span class="stat-value">{{ $draftLessons ?? 0 }}</span>
        <span class="stat-label">Draft</span>
    </div>
    <div class="stat-item">
        <span class="stat-value">{{ $freeLessons ?? 0 }}</span>
        <span class="stat-label">Free Lessons</span>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <form method="GET" action="{{ route('admin.lessons.index') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;width:100%;">
            <div class="search-wrap">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="search" class="search-input" placeholder="Search lessons..." value="{{ request('search') }}">
                @if(request('search'))
                <a href="{{ route('admin.lessons.index', array_filter(['course_id' => request('course_id')])) }}" class="search-clear">
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
    <span class="filter-tag">Search: {{ request('search') }} <a href="{{ route('admin.lessons.index', array_filter(['course_id' => request('course_id')])) }}">&times;</a></span>
    @endif
    @if(request('course_id'))
    <span class="filter-tag">Course: {{ $courses->firstWhere('id', request('course_id'))?->title ?? request('course_id') }} <a href="{{ route('admin.lessons.index', array_filter(['search' => request('search')])) }}">&times;</a></span>
    @endif
    <a href="{{ route('admin.lessons.index') }}" class="clear-all-filters">Clear All</a>
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
                    <th>Module</th>
                    <th>Duration</th>
                    <th>Sequence</th>
                    <th>Status</th>
                    <th style="width:120px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lessons ?? [] as $lesson)
                    <tr>
                        <td style="color:var(--gray-400);font-family:'JetBrains Mono',monospace;font-size:12px;">#{{ $lesson->id }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:40px;height:40px;border-radius:8px;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <span class="material-symbols-outlined" style="font-size:18px;color:var(--gray-400);">menu_book</span>
                                </div>
                                <div>
                                    <div style="font-weight:600;color:var(--gray-900);font-size:14px;">{{ $lesson->title }}</div>
                                    @if($lesson->description)
                                        <div style="font-size:12px;color:var(--gray-500);max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $lesson->description }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $lesson->course->title ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $lesson->module->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);display:flex;align-items:center;gap:6px;">
                                <span class="material-symbols-outlined" style="font-size:16px;color:var(--gray-400);">schedule</span>
                                {{ $lesson->duration ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--gray-600);">{{ $lesson->sequence ?? '-' }}</span>
                        </td>
                        <td>
                            @if($lesson->is_published)
                                <span class="badge badge-success">Published</span>
                            @else
                                <span class="badge badge-warning">Draft</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div class="action-menu">
                                <button class="action-menu-trigger" onclick="toggleDropdown(this)" aria-label="Actions">
                                    <span class="material-symbols-outlined">more_horiz</span>
                                </button>
                                <div class="action-dropdown">
                                    <a href="{{ route('admin.lessons.edit', $lesson->id) }}" class="action-dropdown-item">
                                        <span class="material-symbols-outlined">edit</span> Edit
                                    </a>
                                    <div class="action-dropdown-divider"></div>
                                    <form method="POST" action="{{ route('admin.lessons.destroy', $lesson->id) }}" onsubmit="return confirm('Delete this lesson?')">
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
                        <td colspan="8" style="padding:60px 0;text-align:center;">
                            <div class="empty-state">
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">menu_book</span>
                                <h3>{{ request('search') || request('course_id') ? 'No lessons found' : 'No lessons yet' }}</h3>
                                <p>{{ request('search') || request('course_id') ? 'Try changing your search or filters.' : 'Add your first lesson to get started.' }}</p>
                                <div class="empty-state-actions">
                                    @if(request('search') || request('course_id'))
                                        <a href="{{ route('admin.lessons.index') }}" class="btn btn-outline btn-sm">Clear Filters</a>
                                    @endif
                                    <a href="{{ route('admin.lessons.create') }}" class="btn btn-primary btn-sm">
                                        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Lesson
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($lessons->hasPages())
    <div class="card-footer">
        <div class="pagination-wrap" style="border-top:none;margin-top:0;padding-top:0;">
            <div class="pagination-info">
                Showing {{ $lessons->firstItem() }} to {{ $lessons->lastItem() }} of {{ $lessons->total() }} lessons
            </div>
            <div class="pagination">
                @if($lessons->onFirstPage())
                <span class="page-item disabled"><span class="page-link">Previous</span></span>
                @else
                <span class="page-item"><a class="page-link" href="{{ $lessons->previousPageUrl() }}">Previous</a></span>
                @endif

                @foreach($lessons->getUrlRange(max(1, $lessons->currentPage() - 2), min($lessons->lastPage(), $lessons->currentPage() + 2)) as $page => $url)
                <span class="page-item {{ $page == $lessons->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </span>
                @endforeach

                @if($lessons->currentPage() < $lessons->lastPage())
                <span class="page-item"><a class="page-link" href="{{ $lessons->nextPageUrl() }}">Next</a></span>
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
