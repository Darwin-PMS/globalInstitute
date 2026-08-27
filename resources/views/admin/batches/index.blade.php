@extends('layouts.admin')

@section('title', 'Batches')

@section('actions')
    <a href="{{ route('admin.batches.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Batch
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Batch</th>
                    <th>Course</th>
                    <th>Schedule</th>
                    <th>Trainer</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th style="width:120px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches ?? [] as $batch)
                    <tr>
                        <td style="color:var(--gray-400);font-family:'JetBrains Mono',monospace;font-size:12px;">#{{ $batch->id }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:40px;height:40px;border-radius:8px;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <span class="material-symbols-outlined" style="font-size:18px;color:var(--gray-400);">groups</span>
                                </div>
                                <div>
                                    <div style="font-weight:600;color:var(--gray-900);font-size:14px;">{{ $batch->batch_name }}</div>
                                    @if($batch->batch_code)
                                        <div style="font-size:12px;color:var(--gray-500);font-family:'JetBrains Mono',monospace;">{{ $batch->batch_code }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $batch->course->title ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <div style="font-size:13px;color:var(--gray-600);">
                                <div>{{ $batch->days ?? 'N/A' }}</div>
                                @if($batch->start_time && $batch->end_time)
                                    <div style="font-size:12px;color:var(--gray-500);">{{ \Carbon\Carbon::parse($batch->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($batch->end_time)->format('h:i A') }}</div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $batch->teacher->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);display:flex;align-items:center;gap:6px;">
                                <span class="material-symbols-outlined" style="font-size:16px;color:var(--gray-400);">group</span>
                                {{ $batch->current_students ?? 0 }}/{{ $batch->capacity ?? '∞' }}
                            </span>
                        </td>
                        <td>
                            @if($batch->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @elseif($batch->status === 'upcoming')
                                <span class="badge badge-info">Upcoming</span>
                            @elseif($batch->status === 'completed')
                                <span class="badge badge-secondary">Completed</span>
                            @else
                                <span class="badge badge-warning">{{ ucfirst($batch->status ?? '') }}</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:6px;justify-content:flex-end;">
                                <a href="{{ route('admin.batches.edit', $batch->id) }}" class="icon-btn icon-btn-edit" title="Edit">
                                    <span class="material-symbols-outlined">edit</span>
                                </a>
                                <form method="POST" action="{{ route('admin.batches.destroy', $batch->id) }}" style="display:inline;" onsubmit="return confirm('Delete this batch?')">
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
                        <td colspan="8" style="padding:60px 0;text-align:center;">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">groups</span>
                                <p style="color:var(--gray-500);font-size:14px;">No batches yet</p>
                                <a href="{{ route('admin.batches.create') }}" class="btn btn-primary btn-sm" style="margin-top:8px;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">add</span> Create First Batch
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
