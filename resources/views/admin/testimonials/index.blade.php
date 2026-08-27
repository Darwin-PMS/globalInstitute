@extends('layouts.admin')

@section('title', 'Testimonials')

@section('actions')
    <a href="{{ route('admin.testimonials.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Testimonial
    </a>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <h1>Testimonials</h1>
        <p class="page-header-desc">Manage student testimonials and reviews.</p>
    </div>
</div>

<div class="card">
    <div class="card-body-flush">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Batch</th>
                    <th>Status</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($testimonials ?? [] as $testimonial)
                <tr>
                    <td class="mono" style="font-size:12px;color:var(--color-text-muted);">#{{ $testimonial->id }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:40px;height:40px;border-radius:50%;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span class="material-symbols-outlined" style="font-size:18px;color:var(--color-text-muted);">person</span>
                            </div>
                            <div>
                                <div style="font-weight:600;color:var(--color-text);font-size:14px;">{{ $testimonial->student_name }}</div>
                                <div style="font-size:12px;color:var(--color-text-muted);max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ Str::limit($testimonial->testimonial, 60) }}</div>
                            </div>
                        </div>
                    </td>
                    <td><span style="font-size:13px;color:var(--color-text-secondary);">{{ $testimonial->course ?? 'N/A' }}</span></td>
                    <td><span style="font-size:13px;color:var(--color-text-secondary);">{{ $testimonial->batch ?? 'N/A' }}</span></td>
                    <td>
                        @if($testimonial->status === 'published')
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
                                <a href="{{ route('admin.testimonials.edit', $testimonial->id) }}" class="action-dropdown-item">
                                    <span class="material-symbols-outlined">edit</span> Edit
                                </a>
                                <div class="action-dropdown-divider"></div>
                                <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial->id) }}" onsubmit="return confirm('Delete this testimonial?')">
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
                    <td colspan="6">
                        <div class="empty-state">
                            <span class="material-symbols-outlined">reviews</span>
                            <h3>No testimonials yet</h3>
                            <p>Add your first student testimonial.</p>
                            <div class="empty-state-actions">
                                <a href="{{ route('admin.testimonials.create') }}" class="btn btn-primary btn-sm">
                                    <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Testimonial
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
