@extends('layouts.admin')

@section('title', 'Students')

@section('actions')
    <a href="{{ route('admin.students.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Student
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:80px;">ID</th>
                    <th>Student</th>
                    <th>Phone</th>
                    <th>Qualification</th>
                    <th>City</th>
                    <th>Status</th>
                    <th style="width:120px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students ?? [] as $student)
                    <tr>
                        <td style="color:var(--gray-400);font-family:'JetBrains Mono',monospace;font-size:12px;">{{ $student->student_id ?? '#'.$student->id }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:40px;height:40px;border-radius:50%;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                                    @if($student->photo)
                                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40'%3E%3Crect fill='%23f1f3f5' width='40' height='40'/%3E%3C/svg%3E" data-src="{{ asset('storage/'.$student->photo) }}" alt="{{ $student->name }}" loading="lazy" decoding="async" class="lazy-img avatar" style="width:100%;height:100%;object-fit:cover;">
                                    @else
                                        <span class="material-symbols-outlined" style="font-size:18px;color:var(--gray-400);">person</span>
                                    @endif
                                </div>
                                <div>
                                    <a href="{{ route('admin.students.show', $student->id) }}" style="font-weight:600;color:var(--primary);font-size:14px;text-decoration:none;">{{ $student->name }}</a>
                                    @if($student->email)
                                        <div style="font-size:12px;color:var(--gray-500);">{{ $student->email }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $student->phone ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $student->qualification ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $student->city ?? 'N/A' }}</span>
                        </td>
                        <td>
                            @if($student->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @elseif($student->status === 'inactive')
                                <span class="badge badge-warning">Inactive</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($student->status ?? '') }}</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:6px;justify-content:flex-end;">
                                <a href="{{ route('admin.students.show', $student->id) }}" class="icon-btn" title="View" style="color:var(--gray-500);">
                                    <span class="material-symbols-outlined">visibility</span>
                                </a>
                                <a href="{{ route('admin.students.edit', $student->id) }}" class="icon-btn icon-btn-edit" title="Edit">
                                    <span class="material-symbols-outlined">edit</span>
                                </a>
                                <form method="POST" action="{{ route('admin.students.destroy', $student->id) }}" style="display:inline;" onsubmit="return confirm('Delete this student?')">
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
                                <p style="color:var(--gray-500);font-size:14px;">No students yet</p>
                                <a href="{{ route('admin.students.create') }}" class="btn btn-primary btn-sm" style="margin-top:8px;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add First Student
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
