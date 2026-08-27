@extends('layouts.admin')

@section('title', 'Quizzes')

@section('actions')
    <a href="{{ route('admin.quizzes.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Quiz
    </a>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <h1>Quizzes</h1>
        <p class="page-header-desc">Manage course quizzes, questions, and assessments.</p>
    </div>
</div>

<div class="stats-bar">
    <div class="stat-item">
        <span class="stat-value">{{ $totalQuizzes ?? 0 }}</span>
        <span class="stat-label">Total Quizzes</span>
    </div>
    <div class="stat-item">
        <span class="stat-value">{{ $publishedQuizzes ?? 0 }}</span>
        <span class="stat-label">Published</span>
    </div>
    <div class="stat-item">
        <span class="stat-value">{{ $draftQuizzes ?? 0 }}</span>
        <span class="stat-label">Draft</span>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <form method="GET" action="{{ route('admin.quizzes.index') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;width:100%;">
            <div class="search-wrap">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="search" class="search-input" placeholder="Search quizzes..." value="{{ request('search') }}">
                @if(request('search'))
                <a href="{{ route('admin.quizzes.index', array_filter(['course_id' => request('course_id')])) }}" class="search-clear">
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
    <span class="filter-tag">Search: {{ request('search') }} <a href="{{ route('admin.quizzes.index', array_filter(['course_id' => request('course_id')])) }}">&times;</a></span>
    @endif
    @if(request('course_id'))
    <span class="filter-tag">Course: {{ $courses->firstWhere('id', request('course_id'))?->title ?? request('course_id') }} <a href="{{ route('admin.quizzes.index', array_filter(['search' => request('search')])) }}">&times;</a></span>
    @endif
    <a href="{{ route('admin.quizzes.index') }}" class="clear-all-filters">Clear All</a>
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
                    <th>Teacher</th>
                    <th>Questions</th>
                    <th>Time Limit</th>
                    <th>Pass Marks</th>
                    <th>Status</th>
                    <th style="width:120px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quizzes ?? [] as $quiz)
                    <tr>
                        <td style="color:var(--gray-400);font-family:'JetBrains Mono',monospace;font-size:12px;">#{{ $quiz->id }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:40px;height:40px;border-radius:8px;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <span class="material-symbols-outlined" style="font-size:18px;color:var(--gray-400);">quiz</span>
                                </div>
                                <div>
                                    <div style="font-weight:600;color:var(--gray-900);font-size:14px;">{{ $quiz->title }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $quiz->course->title ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $quiz->module->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $quiz->teacher->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--gray-600);">{{ $quiz->questions_count ?? $quiz->questions->count() ?? 0 }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);display:flex;align-items:center;gap:6px;">
                                <span class="material-symbols-outlined" style="font-size:14px;color:var(--gray-400);">schedule</span>
                                {{ $quiz->time_limit ?? 'N/A' }} min
                            </span>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--gray-600);">{{ $quiz->pass_marks ?? 'N/A' }}/{{ $quiz->total_marks ?? 'N/A' }}</span>
                        </td>
                        <td>
                            @if($quiz->status === 'published')
                                <span class="badge badge-success">Published</span>
                            @elseif($quiz->status === 'closed')
                                <span class="badge badge-danger">Closed</span>
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
                                    <a href="{{ route('admin.quizzes.edit', $quiz->id) }}" class="action-dropdown-item">
                                        <span class="material-symbols-outlined">edit</span> Edit
                                    </a>
                                    <a href="{{ route('admin.quizzes.add-question', $quiz->id) }}" class="action-dropdown-item">
                                        <span class="material-symbols-outlined">add_circle</span> Add Question
                                    </a>
                                    <div class="action-dropdown-divider"></div>
                                    <form method="POST" action="{{ route('admin.quizzes.destroy', $quiz->id) }}" onsubmit="return confirm('Delete this quiz?')">
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
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">quiz</span>
                                <h3>{{ request('search') || request('course_id') ? 'No quizzes found' : 'No quizzes yet' }}</h3>
                                <p>{{ request('search') || request('course_id') ? 'Try changing your search or filters.' : 'Create your first quiz to get started.' }}</p>
                                <div class="empty-state-actions">
                                    @if(request('search') || request('course_id'))
                                        <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline btn-sm">Clear Filters</a>
                                    @endif
                                    <a href="{{ route('admin.quizzes.create') }}" class="btn btn-primary btn-sm">
                                        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Quiz
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($quizzes->hasPages())
    <div class="card-footer">
        <div class="pagination-wrap" style="border-top:none;margin-top:0;padding-top:0;">
            <div class="pagination-info">
                Showing {{ $quizzes->firstItem() }} to {{ $quizzes->lastItem() }} of {{ $quizzes->total() }} quizzes
            </div>
            <div class="pagination">
                @if($quizzes->onFirstPage())
                <span class="page-item disabled"><span class="page-link">Previous</span></span>
                @else
                <span class="page-item"><a class="page-link" href="{{ $quizzes->previousPageUrl() }}">Previous</a></span>
                @endif

                @foreach($quizzes->getUrlRange(max(1, $quizzes->currentPage() - 2), min($quizzes->lastPage(), $quizzes->currentPage() + 2)) as $page => $url)
                <span class="page-item {{ $page == $quizzes->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </span>
                @endforeach

                @if($quizzes->currentPage() < $quizzes->lastPage())
                <span class="page-item"><a class="page-link" href="{{ $quizzes->nextPageUrl() }}">Next</a></span>
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
