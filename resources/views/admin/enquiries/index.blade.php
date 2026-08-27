@extends('layouts.admin')

@section('title', 'Enquiries')

@section('actions')
    <a href="{{ route('admin.enquiries.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Enquiry
    </a>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <h1>Enquiries</h1>
        <p class="page-header-desc">Track and manage student enquiries and leads.</p>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <form method="GET" action="{{ route('admin.enquiries.index') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;width:100%;">
            <div class="search-wrap">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="search" class="search-input" placeholder="Search enquiries..." value="{{ request('search') }}">
                @if(request('search'))
                <a href="{{ route('admin.enquiries.index', array_filter(['status' => request('status')])) }}" class="search-clear">
                    <span class="material-symbols-outlined" style="font-size:16px;">close</span>
                </a>
                @endif
            </div>
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>New</option>
                <option value="contacted" {{ request('status') === 'contacted' ? 'selected' : '' }}>Contacted</option>
                <option value="converted" {{ request('status') === 'converted' ? 'selected' : '' }}>Converted</option>
            </select>
        </form>
    </div>
</div>

@if(request('search') || request('status'))
<div class="active-filters">
    <span class="active-filters-label">Active Filters:</span>
    @if(request('search'))
    <span class="filter-tag">Search: {{ request('search') }} <a href="{{ route('admin.enquiries.index', array_filter(['status' => request('status')])) }}">&times;</a></span>
    @endif
    @if(request('status'))
    <span class="filter-tag">Status: {{ ucfirst(request('status')) }} <a href="{{ route('admin.enquiries.index', array_filter(['search' => request('search')])) }}">&times;</a></span>
    @endif
    <a href="{{ route('admin.enquiries.index') }}" class="clear-all-filters">Clear All</a>
</div>
@endif

<div class="card">
    <div class="card-body-flush">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Contact</th>
                    <th>Phone</th>
                    <th>Course / Subject</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($enquiries ?? [] as $enquiry)
                <tr>
                    <td class="mono" style="font-size:12px;color:var(--color-text-muted);">#{{ $enquiry->id }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:40px;height:40px;border-radius:50%;background:var(--color-primary);color:var(--color-accent);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px;font-weight:600;">
                                {{ strtoupper(substr($enquiry->name, 0, 2)) }}
                            </div>
                            <div>
                                <div style="font-weight:600;color:var(--color-text);font-size:14px;">{{ $enquiry->name }}</div>
                                @if($enquiry->email)
                                    <div style="font-size:12px;color:var(--color-text-muted);">{{ $enquiry->email }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td><span class="mono" style="font-size:13px;color:var(--color-text-secondary);">{{ $enquiry->phone }}</span></td>
                    <td><span style="font-size:13px;color:var(--color-text-secondary);">{{ $enquiry->course->title ?? $enquiry->subject ?? 'N/A' }}</span></td>
                    <td><span class="badge badge-secondary">{{ ucfirst($enquiry->source ?? 'N/A') }}</span></td>
                    <td>
                        @if($enquiry->status === 'new')
                            <span class="badge badge-info">New</span>
                        @elseif($enquiry->status === 'contacted')
                            <span class="badge badge-warning">Contacted</span>
                        @elseif($enquiry->status === 'converted')
                            <span class="badge badge-success">Converted</span>
                        @else
                            <span class="badge badge-draft">{{ ucfirst($enquiry->status) }}</span>
                        @endif
                    </td>
                    <td><span style="font-size:13px;color:var(--color-text-muted);">{{ $enquiry->created_at->format('d M Y') }}</span></td>
                    <td>
                        <div class="action-menu">
                            <button class="action-menu-trigger" onclick="toggleDropdown(this)" aria-label="Actions">
                                <span class="material-symbols-outlined">more_horiz</span>
                            </button>
                            <div class="action-dropdown">
                                <a href="{{ route('admin.enquiries.show', $enquiry->id) }}" class="action-dropdown-item">
                                    <span class="material-symbols-outlined">visibility</span> View
                                </a>
                                <a href="{{ route('admin.enquiries.edit', $enquiry->id) }}" class="action-dropdown-item">
                                    <span class="material-symbols-outlined">edit</span> Edit
                                </a>
                                <div class="action-dropdown-divider"></div>
                                <form method="POST" action="{{ route('admin.enquiries.destroy', $enquiry->id) }}" onsubmit="return confirm('Delete this enquiry?')">
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
                    <td colspan="8">
                        <div class="empty-state">
                            <span class="material-symbols-outlined">contact_page</span>
                            <h3>{{ request('search') || request('status') ? 'No enquiries found' : 'No enquiries yet' }}</h3>
                            <p>{{ request('search') || request('status') ? 'Try changing your search or filters.' : 'Enquiries from your website will appear here.' }}</p>
                            <div class="empty-state-actions">
                                @if(request('search') || request('status'))
                                    <a href="{{ route('admin.enquiries.index') }}" class="btn btn-outline btn-sm">Clear Filters</a>
                                @endif
                                <a href="{{ route('admin.enquiries.create') }}" class="btn btn-primary btn-sm">
                                    <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Enquiry
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($enquiries->hasPages())
    <div class="card-footer">
        <div class="pagination-wrap" style="border-top:none;margin-top:0;padding-top:0;">
            <div class="pagination-info">
                Showing {{ $enquiries->firstItem() }} to {{ $enquiries->lastItem() }} of {{ $enquiries->total() }} enquiries
            </div>
            <div class="pagination">
                @if($enquiries->onFirstPage())
                <span class="page-item disabled"><span class="page-link">Previous</span></span>
                @else
                <span class="page-item"><a class="page-link" href="{{ $enquiries->previousPageUrl() }}">Previous</a></span>
                @endif

                @foreach($enquiries->getUrlRange(max(1, $enquiries->currentPage() - 2), min($enquiries->lastPage(), $enquiries->currentPage() + 2)) as $page => $url)
                <span class="page-item {{ $page == $enquiries->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </span>
                @endforeach

                @if($enquiries->currentPage() < $enquiries->lastPage())
                <span class="page-item"><a class="page-link" href="{{ $enquiries->nextPageUrl() }}">Next</a></span>
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
