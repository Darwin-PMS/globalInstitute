@extends('layouts.admin')

@section('title', 'Admissions')

@section('actions')
    <a href="{{ route('admin.admissions.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> New Admission
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:100px;">Admission #</th>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Batch</th>
                    <th>Fee</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="width:120px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admissions ?? [] as $admission)
                    <tr>
                        <td style="color:var(--gray-400);font-family:'JetBrains Mono',monospace;font-size:12px;">{{ $admission->admission_number }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:36px;height:36px;border-radius:50%;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                                    @if($admission->student->photo)
                                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='36' height='36'%3E%3Crect fill='%23f1f3f5' width='36' height='36'/%3E%3C/svg%3E" data-src="{{ asset('storage/'.$admission->student->photo) }}" alt="{{ $admission->student->name ?? '' }}" loading="lazy" decoding="async" class="lazy-img avatar" style="width:100%;height:100%;object-fit:cover;">
                                    @else
                                        <span class="material-symbols-outlined" style="font-size:16px;color:var(--gray-400);">person</span>
                                    @endif
                                </div>
                                <div>
                                    <div style="font-weight:600;color:var(--gray-900);font-size:14px;">{{ $admission->student->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $admission->course->title ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $admission->batch->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-weight:500;font-size:13px;color:var(--gray-600);">₹{{ number_format($admission->fee ?? 0) }}</span>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-weight:500;font-size:13px;color:var(--gray-600);">₹{{ number_format($admission->paid ?? 0) }}</span>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-weight:500;font-size:13px;color:{{ ($admission->balance ?? 0) > 0 ? 'var(--danger)' : 'var(--gray-600)' }};">₹{{ number_format($admission->balance ?? 0) }}</span>
                        </td>
                        <td>
                            @if($admission->status === 'confirmed')
                                <span class="badge badge-success">Confirmed</span>
                            @elseif($admission->status === 'pending')
                                <span class="badge badge-warning">Pending</span>
                            @elseif($admission->status === 'cancelled')
                                <span class="badge badge-danger">Cancelled</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($admission->status ?? '') }}</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $admission->admission_date ? \Carbon\Carbon::parse($admission->admission_date)->format('d M Y') : 'N/A' }}</span>
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:6px;justify-content:flex-end;">
                                <a href="{{ route('admin.admissions.show', $admission->id) }}" class="icon-btn" title="View" style="color:var(--gray-500);">
                                    <span class="material-symbols-outlined">visibility</span>
                                </a>
                                <a href="{{ route('admin.admissions.edit', $admission->id) }}" class="icon-btn icon-btn-edit" title="Edit">
                                    <span class="material-symbols-outlined">edit</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="padding:60px 0;text-align:center;">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">school</span>
                                <p style="color:var(--gray-500);font-size:14px;">No admissions yet</p>
                                <a href="{{ route('admin.admissions.create') }}" class="btn btn-primary btn-sm" style="margin-top:8px;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">add</span> Create First Admission
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
