@extends('layouts.admin')

@section('title', 'Materials')

@section('actions')
    <a href="{{ route('admin.materials.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Material
    </a>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <h1>Materials</h1>
        <p class="page-header-desc">Manage course materials, documents, and resources.</p>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <form method="GET" action="{{ route('admin.materials.index') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;width:100%;">
            <div class="search-wrap">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="search" class="search-input" placeholder="Search materials..." value="{{ request('search') }}">
                @if(request('search'))
                <a href="{{ route('admin.materials.index', array_filter(['course_id' => request('course_id')])) }}" class="search-clear">
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
    <span class="filter-tag">Search: {{ request('search') }} <a href="{{ route('admin.materials.index', array_filter(['course_id' => request('course_id')])) }}">&times;</a></span>
    @endif
    @if(request('course_id'))
    <span class="filter-tag">Course: {{ $courses->firstWhere('id', request('course_id'))?->title ?? request('course_id') }} <a href="{{ route('admin.materials.index', array_filter(['search' => request('search')])) }}">&times;</a></span>
    @endif
    <a href="{{ route('admin.materials.index') }}" class="clear-all-filters">Clear All</a>
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
                    <th>File Type</th>
                    <th>Size</th>
                    <th>Visibility</th>
                    <th>Published</th>
                    <th style="width:120px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials ?? [] as $material)
                    <tr>
                        <td style="color:var(--gray-400);font-family:'JetBrains Mono',monospace;font-size:12px;">#{{ $material->id }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:40px;height:40px;border-radius:8px;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    @if($material->file_type === 'pdf')
                                        <span class="material-symbols-outlined" style="font-size:18px;color:#e74c3c;">picture_as_pdf</span>
                                    @elseif(in_array($material->file_type, ['ppt', 'pptx']))
                                        <span class="material-symbols-outlined" style="font-size:18px;color:#e67e22;">slideshow</span>
                                    @elseif(in_array($material->file_type, ['doc', 'docx']))
                                        <span class="material-symbols-outlined" style="font-size:18px;color:#3498db;">description</span>
                                    @elseif(in_array($material->file_type, ['xls', 'xlsx']))
                                        <span class="material-symbols-outlined" style="font-size:18px;color:#27ae60;">table_chart</span>
                                    @elseif($material->file_type === 'zip')
                                        <span class="material-symbols-outlined" style="font-size:18px;color:#9b59b6;">folder_zip</span>
                                    @elseif($material->file_type === 'image')
                                        <span class="material-symbols-outlined" style="font-size:18px;color:#1abc9c;">image</span>
                                    @elseif($material->file_type === 'link')
                                        <span class="material-symbols-outlined" style="font-size:18px;color:var(--gray-400);">link</span>
                                    @else
                                        <span class="material-symbols-outlined" style="font-size:18px;color:var(--gray-400);">insert_drive_file</span>
                                    @endif
                                </div>
                                <div>
                                    <div style="font-weight:600;color:var(--gray-900);font-size:14px;">{{ $material->title }}</div>
                                    @if($material->description)
                                        <div style="font-size:12px;color:var(--gray-500);max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $material->description }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $material->course->title ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $material->module->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="badge badge-secondary">{{ strtoupper($material->file_type ?? 'N/A') }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);font-family:'JetBrains Mono',monospace;">
                                {{ $material->file_size ? number_format($material->file_size / 1024, 1) . ' KB' : 'N/A' }}
                            </span>
                        </td>
                        <td>
                            @if($material->visibility === 'public')
                                <span class="badge badge-success">Public</span>
                            @elseif($material->visibility === 'enrolled')
                                <span class="badge badge-info">Enrolled</span>
                            @else
                                <span class="badge badge-warning">Private</span>
                            @endif
                        </td>
                        <td>
                            @if($material->is_published)
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
                                    <a href="{{ route('admin.materials.edit', $material->id) }}" class="action-dropdown-item">
                                        <span class="material-symbols-outlined">edit</span> Edit
                                    </a>
                                    @if($material->file_path)
                                    <a href="{{ asset('storage/' . $material->file_path) }}" class="action-dropdown-item" target="_blank">
                                        <span class="material-symbols-outlined">download</span> Download
                                    </a>
                                    @endif
                                    <div class="action-dropdown-divider"></div>
                                    <form method="POST" action="{{ route('admin.materials.destroy', $material->id) }}" onsubmit="return confirm('Delete this material?')">
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
                        <td colspan="9" style="padding:60px 0;text-align:center;">
                            <div class="empty-state">
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">folder_open</span>
                                <h3>{{ request('search') || request('course_id') ? 'No materials found' : 'No materials yet' }}</h3>
                                <p>{{ request('search') || request('course_id') ? 'Try changing your search or filters.' : 'Upload your first material to get started.' }}</p>
                                <div class="empty-state-actions">
                                    @if(request('search') || request('course_id'))
                                        <a href="{{ route('admin.materials.index') }}" class="btn btn-outline btn-sm">Clear Filters</a>
                                    @endif
                                    <a href="{{ route('admin.materials.create') }}" class="btn btn-primary btn-sm">
                                        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Material
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($materials->hasPages())
    <div class="card-footer">
        <div class="pagination-wrap" style="border-top:none;margin-top:0;padding-top:0;">
            <div class="pagination-info">
                Showing {{ $materials->firstItem() }} to {{ $materials->lastItem() }} of {{ $materials->total() }} materials
            </div>
            <div class="pagination">
                @if($materials->onFirstPage())
                <span class="page-item disabled"><span class="page-link">Previous</span></span>
                @else
                <span class="page-item"><a class="page-link" href="{{ $materials->previousPageUrl() }}">Previous</a></span>
                @endif

                @foreach($materials->getUrlRange(max(1, $materials->currentPage() - 2), min($materials->lastPage(), $materials->currentPage() + 2)) as $page => $url)
                <span class="page-item {{ $page == $materials->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </span>
                @endforeach

                @if($materials->currentPage() < $materials->lastPage())
                <span class="page-item"><a class="page-link" href="{{ $materials->nextPageUrl() }}">Next</a></span>
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
