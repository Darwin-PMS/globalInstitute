@extends('layouts.admin')

@section('title', 'Enrollments')

@section('actions')
    <a href="{{ route('admin.enrollments.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> New Enrollment
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:100px;">Enrollment #</th>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Batch</th>
                    <th>Final Fee</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Fee Status</th>
                    <th>Status</th>
                    <th style="width:120px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($enrollments ?? [] as $enrollment)
                    <tr>
                        <td style="color:var(--gray-400);font-family:'JetBrains Mono',monospace;font-size:12px;">{{ $enrollment->enrollment_number }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:50%;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                                    @if($enrollment->student->photo)
                                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='32'%3E%3Crect fill='%23f1f3f5' width='32' height='32'/%3E%3C/svg%3E" data-src="{{ asset('storage/'.$enrollment->student->photo) }}" alt="{{ $enrollment->student->name ?? '' }}" loading="lazy" decoding="async" class="lazy-img avatar" style="width:100%;height:100%;object-fit:cover;">
                                    @else
                                        <span class="material-symbols-outlined" style="font-size:14px;color:var(--gray-400);">person</span>
                                    @endif
                                </div>
                                <div>
                                    <div style="font-weight:600;color:var(--gray-900);font-size:13px;">{{ $enrollment->student->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $enrollment->course->title ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $enrollment->batch->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-weight:500;font-size:13px;color:var(--gray-600);">₹{{ number_format($enrollment->final_fee ?? 0) }}</span>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-weight:500;font-size:13px;color:var(--gray-600);">₹{{ number_format($enrollment->paid_amount ?? 0) }}</span>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-weight:500;font-size:13px;color:{{ ($enrollment->balance ?? 0) > 0 ? 'var(--danger)' : 'var(--gray-600)' }};">₹{{ number_format($enrollment->balance ?? 0) }}</span>
                        </td>
                        <td>
                            @if($enrollment->fee_status === 'paid')
                                <span class="badge badge-success">Paid</span>
                            @elseif($enrollment->fee_status === 'partial')
                                <span class="badge badge-warning">Partial</span>
                            @elseif($enrollment->fee_status === 'overdue')
                                <span class="badge badge-danger">Overdue</span>
                            @else
                                <span class="badge badge-secondary">Unpaid</span>
                            @endif
                        </td>
                        <td>
                            @if($enrollment->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @elseif($enrollment->status === 'completed')
                                <span class="badge badge-secondary">Completed</span>
                            @elseif($enrollment->status === 'dropped')
                                <span class="badge badge-danger">Dropped</span>
                            @else
                                <span class="badge badge-warning">{{ ucfirst($enrollment->status ?? '') }}</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:6px;justify-content:flex-end;">
                                <a href="{{ route('admin.enrollments.show', $enrollment->id) }}" class="icon-btn" title="View" style="color:var(--gray-500);">
                                    <span class="material-symbols-outlined">visibility</span>
                                </a>
                                <a href="{{ route('admin.enrollments.edit', $enrollment->id) }}" class="icon-btn icon-btn-edit" title="Edit">
                                    <span class="material-symbols-outlined">edit</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="padding:60px 0;text-align:center;">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">badge</span>
                                <p style="color:var(--gray-500);font-size:14px;">No enrollments yet</p>
                                <a href="{{ route('admin.enrollments.create') }}" class="btn btn-primary btn-sm" style="margin-top:8px;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">add</span> Create First Enrollment
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
