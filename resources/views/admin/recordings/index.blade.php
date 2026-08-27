@extends('layouts.admin')

@section('title', 'Recordings')

@section('actions')
    <a href="{{ route('admin.recordings.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Recording
    </a>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <h1>Recordings</h1>
        <p class="page-header-desc">Manage class recordings and video resources.</p>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <form method="GET" action="{{ route('admin.recordings.index') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;width:100%;">
            <div class="search-wrap">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="search" class="search-input" placeholder="Search recordings..." value="{{ request('search') }}">
                @if(request('search'))
                <a href="{{ route('admin.recordings.index') }}" class="search-clear">
                    <span class="material-symbols-outlined" style="font-size:16px;">close</span>
                </a>
                @endif
            </div>
        </form>
    </div>
</div>

@if(request('search'))
<div class="active-filters">
    <span class="active-filters-label">Active Filters:</span>
    <span class="filter-tag">Search: {{ request('search') }} <a href="{{ route('admin.recordings.index') }}">&times;</a></span>
    <a href="{{ route('admin.recordings.index') }}" class="clear-all-filters">Clear All</a>
</div>
@endif

<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Title</th>
                    <th>Class</th>
                    <th>Duration</th>
                    <th>Visibility</th>
                    <th>Published</th>
                    <th>Views</th>
                    <th style="width:120px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recordings ?? [] as $recording)
                    <tr>
                        <td style="color:var(--gray-400);font-family:'JetBrains Mono',monospace;font-size:12px;">#{{ $recording->id }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:40px;height:40px;border-radius:8px;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <span class="material-symbols-outlined" style="font-size:18px;color:var(--gray-400);">videocam</span>
                                </div>
                                <div>
                                    <div style="font-weight:600;color:var(--gray-900);font-size:14px;">{{ $recording->title }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $recording->onlineClass->title ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);display:flex;align-items:center;gap:6px;">
                                <span class="material-symbols-outlined" style="font-size:14px;color:var(--gray-400);">schedule</span>
                                {{ $recording->duration ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            @if($recording->visibility === 'public')
                                <span class="badge badge-success">Public</span>
                            @elseif($recording->visibility === 'enrolled')
                                <span class="badge badge-info">Enrolled</span>
                            @else
                                <span class="badge badge-warning">Private</span>
                            @endif
                        </td>
                        <td>
                            @if($recording->is_published)
                                <span class="badge badge-success">Published</span>
                            @else
                                <span class="badge badge-warning">Draft</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--gray-600);">{{ $recording->views ?? 0 }}</span>
                        </td>
                        <td style="text-align:right;">
                            <div class="action-menu">
                                <button class="action-menu-trigger" onclick="toggleDropdown(this)" aria-label="Actions">
                                    <span class="material-symbols-outlined">more_horiz</span>
                                </button>
                                <div class="action-dropdown">
                                    <a href="{{ route('admin.recordings.edit', $recording->id) }}" class="action-dropdown-item">
                                        <span class="material-symbols-outlined">edit</span> Edit
                                    </a>
                                    <div class="action-dropdown-divider"></div>
                                    <form method="POST" action="{{ route('admin.recordings.destroy', $recording->id) }}" onsubmit="return confirm('Delete this recording?')">
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
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">videocam</span>
                                <h3>{{ request('search') ? 'No recordings found' : 'No recordings yet' }}</h3>
                                <p>{{ request('search') ? 'Try changing your search.' : 'Add your first recording to get started.' }}</p>
                                <div class="empty-state-actions">
                                    @if(request('search'))
                                        <a href="{{ route('admin.recordings.index') }}" class="btn btn-outline btn-sm">Clear Filters</a>
                                    @endif
                                    <a href="{{ route('admin.recordings.create') }}" class="btn btn-primary btn-sm">
                                        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Recording
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($recordings->hasPages())
    <div class="card-footer">
        <div class="pagination-wrap" style="border-top:none;margin-top:0;padding-top:0;">
            <div class="pagination-info">
                Showing {{ $recordings->firstItem() }} to {{ $recordings->lastItem() }} of {{ $recordings->total() }} recordings
            </div>
            <div class="pagination">
                @if($recordings->onFirstPage())
                <span class="page-item disabled"><span class="page-link">Previous</span></span>
                @else
                <span class="page-item"><a class="page-link" href="{{ $recordings->previousPageUrl() }}">Previous</a></span>
                @endif

                @foreach($recordings->getUrlRange(max(1, $recordings->currentPage() - 2), min($recordings->lastPage(), $recordings->currentPage() + 2)) as $page => $url)
                <span class="page-item {{ $page == $recordings->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </span>
                @endforeach

                @if($recordings->currentPage() < $recordings->lastPage())
                <span class="page-item"><a class="page-link" href="{{ $recordings->nextPageUrl() }}">Next</a></span>
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
