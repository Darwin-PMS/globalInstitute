@extends('layouts.admin')

@section('title', 'Courses')

@section('actions')
    <a href="{{ route('admin.courses.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Course
    </a>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <h1>Courses</h1>
        <p class="page-header-desc">Manage courses, fees, duration and publishing status.</p>
    </div>
</div>

<div class="stats-bar">
    <div class="stat-item">
        <div class="stat-item-icon primary"><span class="material-symbols-outlined">school</span></div>
        <div><div class="stat-item-value">{{ $totalCourses }}</div><div class="stat-item-label">Total Courses</div></div>
    </div>
    <div class="stat-item">
        <div class="stat-item-icon success"><span class="material-symbols-outlined">check_circle</span></div>
        <div><div class="stat-item-value">{{ $publishedCourses }}</div><div class="stat-item-label">Published</div></div>
    </div>
    <div class="stat-item">
        <div class="stat-item-icon warning"><span class="material-symbols-outlined">edit_note</span></div>
        <div><div class="stat-item-value">{{ $draftCourses }}</div><div class="stat-item-label">Draft</div></div>
    </div>
    <div class="stat-item">
        <div class="stat-item-icon info"><span class="material-symbols-outlined">category</span></div>
        <div><div class="stat-item-value">{{ $categoryCount }}</div><div class="stat-item-label">Categories</div></div>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <form method="GET" action="{{ route('admin.courses.index') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;width:100%;" id="courseFilterForm">
            <div class="search-wrap">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="search" class="search-input" placeholder="Search courses..." value="{{ request('search') }}" id="courseSearch">
                @if(request('search'))
                <a href="{{ route('admin.courses.index', array_filter(['category' => request('category'), 'status' => request('status'), 'sort' => request('sort')])) }}" class="search-clear">
                    <span class="material-symbols-outlined" style="font-size:16px;">close</span>
                </a>
                @endif
            </div>
            <select name="category" class="filter-select" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            </select>
            <select name="sort" class="filter-select" onchange="this.form.submit()">
                <option value="">Newest First</option>
                <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                <option value="title_asc" {{ request('sort') === 'title_asc' ? 'selected' : '' }}>Title A-Z</option>
                <option value="title_desc" {{ request('sort') === 'title_desc' ? 'selected' : '' }}>Title Z-A</option>
                <option value="fees_low" {{ request('sort') === 'fees_low' ? 'selected' : '' }}>Fees: Low to High</option>
                <option value="fees_high" {{ request('sort') === 'fees_high' ? 'selected' : '' }}>Fees: High to Low</option>
            </select>
        </form>
    </div>
    <div class="toolbar-right">
        <div class="view-toggle">
            <button class="view-toggle-btn {{ (request('view', 'list') === 'grid') ? '' : 'active' }}" onclick="setView('list')" title="List View">
                <span class="material-symbols-outlined">view_list</span>
            </button>
            <button class="view-toggle-btn {{ request('view') === 'grid' ? 'active' : '' }}" onclick="setView('grid')" title="Grid View">
                <span class="material-symbols-outlined">grid_view</span>
            </button>
        </div>
    </div>
</div>

@if(request('search') || request('category') || request('status') || request('sort'))
<div class="active-filters">
    <span class="active-filters-label">Active Filters:</span>
    @if(request('search'))
    <span class="filter-tag">Search: {{ request('search') }} <a href="{{ route('admin.courses.index', array_filter(['category' => request('category'), 'status' => request('status'), 'sort' => request('sort'), 'view' => request('view')])) }}">&times;</a></span>
    @endif
    @if(request('category'))
    @php $catName = $categories->firstWhere('id', request('category'))?->name ?? request('category'); @endphp
    <span class="filter-tag">Category: {{ $catName }} <a href="{{ route('admin.courses.index', array_filter(['search' => request('search'), 'status' => request('status'), 'sort' => request('sort'), 'view' => request('view')])) }}">&times;</a></span>
    @endif
    @if(request('status'))
    <span class="filter-tag">Status: {{ ucfirst(request('status')) }} <a href="{{ route('admin.courses.index', array_filter(['search' => request('search'), 'category' => request('category'), 'sort' => request('sort'), 'view' => request('view')])) }}">&times;</a></span>
    @endif
    @if(request('sort'))
    <span class="filter-tag">Sort: {{ request('sort') }} <a href="{{ route('admin.courses.index', array_filter(['search' => request('search'), 'category' => request('category'), 'status' => request('status'), 'view' => request('view')])) }}">&times;</a></span>
    @endif
    <a href="{{ route('admin.courses.index') }}" class="clear-all-filters">Clear All</a>
</div>
@endif

<div id="courseListView" style="{{ request('view') === 'grid' ? 'display:none;' : '' }}">
    <div class="card">
        <div class="card-body-flush">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:50px;">ID</th>
                        <th>Course</th>
                        <th>Category</th>
                        <th>Duration</th>
                        <th>Fees</th>
                        <th>Status</th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($courses as $course)
                    <tr>
                        <td class="mono" style="font-size:12px;color:var(--color-text-muted);">#{{ $course->id }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                @if($course->image)
                                    <div class="lazy-bg" data-bg="{{ asset('storage/'.$course->image) }}" style="width:40px;height:40px;border-radius:var(--border-radius-sm);background:var(--gray-100);flex-shrink:0;"></div>
                                @else
                                    <div style="width:40px;height:40px;border-radius:var(--border-radius-sm);background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <span class="material-symbols-outlined" style="font-size:20px;color:var(--gray-400);">school</span>
                                    </div>
                                @endif
                                <div>
                                    <div style="font-weight:600;color:var(--color-text);font-size:14px;">{{ $course->title }}</div>
                                    @if($course->short_description)
                                        <div style="font-size:12px;color:var(--color-text-muted);max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $course->short_description }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td><span style="font-size:13px;color:var(--color-text-secondary);">{{ $course->category->name ?? 'N/A' }}</span></td>
                        <td><span style="font-size:13px;color:var(--color-text-secondary);display:flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:15px;color:var(--color-text-muted);">schedule</span>{{ $course->duration ?? 'N/A' }}</span></td>
                        <td>
                            @if($course->fees)
                                <span class="mono" style="font-weight:500;font-size:13px;">₹{{ number_format($course->fees) }}</span>
                            @else
                                <span style="font-size:13px;color:var(--color-text-muted);">Contact</span>
                            @endif
                        </td>
                        <td>
                            @if($course->status === 'published')
                                <span class="badge badge-success">Published</span>
                            @else
                                <span class="badge badge-draft">Draft</span>
                            @endif
                        </td>
                        <td>
                            <div class="action-menu">
                                <button class="action-menu-trigger" onclick="toggleDropdown(this)" aria-label="Actions">
                                    <span class="material-symbols-outlined">more_horiz</span>
                                </button>
                                <div class="action-dropdown">
                                    <a href="{{ route('admin.courses.edit', $course->id) }}" class="action-dropdown-item">
                                        <span class="material-symbols-outlined">edit</span> Edit
                                    </a>
                                    <div class="action-dropdown-divider"></div>
                                    <form method="POST" action="{{ route('admin.courses.destroy', $course->id) }}" onsubmit="return confirm('Delete this course? This cannot be undone.')">
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
                        <td colspan="7">
                            <div class="empty-state">
                                <span class="material-symbols-outlined">school</span>
                                <h3>{{ request('search') || request('category') || request('status') ? 'No courses found' : 'No courses yet' }}</h3>
                                <p>{{ request('search') || request('category') || request('status') ? 'Try changing your search or filters.' : 'Create your first course to get started.' }}</p>
                                <div class="empty-state-actions">
                                    @if(request('search') || request('category') || request('status'))
                                        <a href="{{ route('admin.courses.index') }}" class="btn btn-outline btn-sm">Clear Filters</a>
                                    @endif
                                    <a href="{{ route('admin.courses.create') }}" class="btn btn-primary btn-sm">
                                        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Course
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($courses->hasPages())
        <div class="card-footer">
            <div class="pagination-wrap" style="border-top:none;margin-top:0;padding-top:0;">
                <div class="pagination-info">
                    Showing {{ $courses->firstItem() }} to {{ $courses->lastItem() }} of {{ $courses->total() }} courses
                </div>
                <div class="pagination">
                    @if($courses->onFirstPage())
                    <span class="page-item disabled"><span class="page-link">Previous</span></span>
                    @else
                    <span class="page-item"><a class="page-link" href="{{ $courses->previousPageUrl() }}">Previous</a></span>
                    @endif

                    @foreach($courses->getUrlRange(max(1, $courses->currentPage() - 2), min($courses->lastPage(), $courses->currentPage() + 2)) as $page => $url)
                    <span class="page-item {{ $page == $courses->currentPage() ? 'active' : '' }}">
                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                    </span>
                    @endforeach

                    @if($courses->currentPage() < $courses->lastPage())
                    <span class="page-item"><a class="page-link" href="{{ $courses->nextPageUrl() }}">Next</a></span>
                    @else
                    <span class="page-item disabled"><span class="page-link">Next</span></span>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<div id="courseGridView" style="{{ request('view') !== 'grid' ? 'display:none;' : '' }}">
    <div class="grid-view">
        @forelse($courses as $course)
        <div class="grid-card">
            <div class="grid-card-image">
                @if($course->image)
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='250'%3E%3Crect fill='%23f1f3f5' width='400' height='250'/%3E%3C/svg%3E" data-src="{{ asset('storage/'.$course->image) }}" alt="{{ $course->title }}" loading="lazy" decoding="async" class="lazy-img"/>
                @else
                    <div class="grid-card-image-placeholder">
                        <span class="material-symbols-outlined">school</span>
                    </div>
                @endif
            </div>
            <div class="grid-card-body">
                <div class="grid-card-header">
                    <h3 class="grid-card-title">{{ $course->title }}</h3>
                    <div class="action-menu">
                        <button class="action-menu-trigger" onclick="toggleDropdown(this)" aria-label="Actions">
                            <span class="material-symbols-outlined">more_horiz</span>
                        </button>
                        <div class="action-dropdown">
                            <a href="{{ route('admin.courses.edit', $course->id) }}" class="action-dropdown-item">
                                <span class="material-symbols-outlined">edit</span> Edit
                            </a>
                            <div class="action-dropdown-divider"></div>
                            <form method="POST" action="{{ route('admin.courses.destroy', $course->id) }}" onsubmit="return confirm('Delete this course? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-dropdown-item action-dropdown-danger">
                                    <span class="material-symbols-outlined">delete</span> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <p class="grid-card-subtitle">{{ $course->category->name ?? 'Uncategorized' }}</p>
                <div class="grid-card-meta">
                    <span class="grid-card-meta-item">
                        <span class="material-symbols-outlined">schedule</span> {{ $course->duration ?? 'N/A' }}
                    </span>
                    @if($course->fees)
                    <span class="grid-card-meta-item mono" style="font-weight:500;">₹{{ number_format($course->fees) }}</span>
                    @endif
                </div>
                <div style="margin-top:10px;">
                    @if($course->status === 'published')
                        <span class="badge badge-success">Published</span>
                    @else
                        <span class="badge badge-draft">Draft</span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div style="grid-column:1/-1;">
            <div class="empty-state">
                <span class="material-symbols-outlined">school</span>
                <h3>{{ request('search') || request('category') || request('status') ? 'No courses found' : 'No courses yet' }}</h3>
                <p>{{ request('search') || request('category') || request('status') ? 'Try changing your search or filters.' : 'Create your first course to get started.' }}</p>
                <div class="empty-state-actions">
                    @if(request('search') || request('category') || request('status'))
                        <a href="{{ route('admin.courses.index') }}" class="btn btn-outline btn-sm">Clear Filters</a>
                    @endif
                    <a href="{{ route('admin.courses.create') }}" class="btn btn-primary btn-sm">
                        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Course
                    </a>
                </div>
            </div>
        </div>
        @endforelse
    </div>
    @if($courses->hasPages())
    <div class="pagination-wrap">
        <div class="pagination-info">
            Showing {{ $courses->firstItem() }} to {{ $courses->lastItem() }} of {{ $courses->total() }} courses
        </div>
        <div class="pagination">
            @if($courses->onFirstPage())
            <span class="page-item disabled"><span class="page-link">Previous</span></span>
            @else
            <span class="page-item"><a class="page-link" href="{{ $courses->previousPageUrl() }}">Previous</a></span>
            @endif

            @foreach($courses->getUrlRange(max(1, $courses->currentPage() - 2), min($courses->lastPage(), $courses->currentPage() + 2)) as $page => $url)
            <span class="page-item {{ $page == $courses->currentPage() ? 'active' : '' }}">
                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
            </span>
            @endforeach

            @if($courses->currentPage() < $courses->lastPage())
            <span class="page-item"><a class="page-link" href="{{ $courses->nextPageUrl() }}">Next</a></span>
            @else
            <span class="page-item disabled"><span class="page-link">Next</span></span>
            @endif
        </div>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
function setView(view) {
    const params = new URLSearchParams(window.location.search);
    params.set('view', view);
    window.location.search = params.toString();
}

function toggleDropdown(btn) {
    const dropdown = btn.nextElementSibling;
    document.querySelectorAll('.action-dropdown.open').forEach(d => {
        if (d !== dropdown) d.classList.remove('open');
    });
    dropdown.classList.toggle('open');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.action-menu')) {
        document.querySelectorAll('.action-dropdown.open').forEach(d => d.classList.remove('open'));
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.action-dropdown.open').forEach(d => d.classList.remove('open'));
    }
});

let searchTimeout;
document.getElementById('courseSearch')?.addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        e.target.closest('form').submit();
    }, 400);
});
</script>
@endpush
