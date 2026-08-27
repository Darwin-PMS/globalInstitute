@extends('layouts.admin')

@section('title', 'Teachers')

@section('actions')
    <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Teacher
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Specialization</th>
                    <th>Employment Type</th>
                    <th>Status</th>
                    <th style="width:120px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teachers ?? [] as $teacher)
                    <tr>
                        <td style="color:var(--gray-400);font-family:'JetBrains Mono',monospace;font-size:12px;">#{{ $teacher->id }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:40px;height:40px;border-radius:50%;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                                    @if($teacher->photo)
                                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40'%3E%3Crect fill='%23f1f3f5' width='40' height='40'/%3E%3C/svg%3E" data-src="{{ asset('storage/'.$teacher->photo) }}" alt="{{ $teacher->name }}" loading="lazy" decoding="async" class="lazy-img avatar" style="width:100%;height:100%;object-fit:cover;">
                                    @else
                                        <span class="material-symbols-outlined" style="font-size:18px;color:var(--gray-400);">person</span>
                                    @endif
                                </div>
                                <div>
                                    <div style="font-weight:600;color:var(--gray-900);font-size:14px;">{{ $teacher->name }}</div>
                                    @if($teacher->email)
                                        <div style="font-size:12px;color:var(--gray-500);">{{ $teacher->email }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $teacher->phone ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $teacher->specialization ?? 'N/A' }}</span>
                        </td>
                        <td>
                            @if($teacher->employment_type === 'full_time')
                                <span class="badge badge-success">Full Time</span>
                            @elseif($teacher->employment_type === 'part_time')
                                <span class="badge badge-warning">Part Time</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $teacher->employment_type ?? '')) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($teacher->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-warning">Inactive</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:6px;justify-content:flex-end;">
                                <a href="{{ route('admin.teachers.edit', $teacher->id) }}" class="icon-btn icon-btn-edit" title="Edit">
                                    <span class="material-symbols-outlined">edit</span>
                                </a>
                                <form method="POST" action="{{ route('admin.teachers.destroy', $teacher->id) }}" style="display:inline;" onsubmit="return confirm('Delete this teacher?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-btn icon-btn-delete" title="Delete">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:60px 0;text-align:center;">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">person</span>
                                <p style="color:var(--gray-500);font-size:14px;">No teachers yet</p>
                                <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary btn-sm" style="margin-top:8px;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add First Teacher
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
