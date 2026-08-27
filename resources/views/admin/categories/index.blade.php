@extends('layouts.admin')

@section('title', 'Categories')

@section('actions')
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Category
    </a>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <h1>Categories</h1>
        <p class="page-header-desc">Organize courses into categories for easy navigation.</p>
    </div>
</div>

<div class="card">
    <div class="card-body-flush">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Category</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories ?? [] as $category)
                <tr>
                    <td class="mono" style="font-size:12px;color:var(--color-text-muted);">#{{ $category->id }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:40px;height:40px;border-radius:var(--border-radius-sm);background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span class="material-symbols-outlined" style="font-size:18px;color:var(--color-text-muted);">folder</span>
                            </div>
                            <div style="font-weight:600;color:var(--color-text);font-size:14px;">{{ $category->name }}</div>
                        </div>
                    </td>
                    <td><span class="mono" style="font-size:13px;color:var(--color-text-muted);">{{ $category->slug }}</span></td>
                    <td>
                        @if($category->status === 'active')
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-draft">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-menu">
                            <button class="action-menu-trigger" onclick="toggleDropdown(this)" aria-label="Actions">
                                <span class="material-symbols-outlined">more_horiz</span>
                            </button>
                            <div class="action-dropdown">
                                <a href="{{ route('admin.categories.edit', $category->id) }}" class="action-dropdown-item">
                                    <span class="material-symbols-outlined">edit</span> Edit
                                </a>
                                <div class="action-dropdown-divider"></div>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category->id) }}" onsubmit="return confirm('Delete this category?')">
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
                    <td colspan="5">
                        <div class="empty-state">
                            <span class="material-symbols-outlined">folder</span>
                            <h3>No categories yet</h3>
                            <p>Create your first category to organize courses.</p>
                            <div class="empty-state-actions">
                                <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">
                                    <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Category
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
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
