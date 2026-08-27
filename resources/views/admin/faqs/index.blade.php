@extends('layouts.admin')

@section('title', 'FAQs')

@section('actions')
    <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add FAQ
    </a>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <h1>FAQs</h1>
        <p class="page-header-desc">Manage frequently asked questions for your website.</p>
    </div>
</div>

<div class="card">
    <div class="card-body-flush">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Question</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($faqs ?? [] as $faq)
                <tr>
                    <td class="mono" style="font-size:12px;color:var(--color-text-muted);">#{{ $faq->id }}</td>
                    <td>
                        <div style="font-weight:500;color:var(--color-text);font-size:14px;max-width:400px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ Str::limit($faq->question, 80) }}</div>
                    </td>
                    <td><span class="badge badge-secondary">{{ ucfirst($faq->category ?? 'General') }}</span></td>
                    <td>
                        @if($faq->status === 'published')
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
                                <a href="{{ route('admin.faqs.edit', $faq->id) }}" class="action-dropdown-item">
                                    <span class="material-symbols-outlined">edit</span> Edit
                                </a>
                                <div class="action-dropdown-divider"></div>
                                <form method="POST" action="{{ route('admin.faqs.destroy', $faq->id) }}" onsubmit="return confirm('Delete this FAQ?')">
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
                            <span class="material-symbols-outlined">help</span>
                            <h3>No FAQs yet</h3>
                            <p>Create your first FAQ to help visitors.</p>
                            <div class="empty-state-actions">
                                <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary btn-sm">
                                    <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add FAQ
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
