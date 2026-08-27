@extends('layouts.admin')

@section('title', 'Batch Report')

@section('content')
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Batch</th>
                    <th>Course</th>
                    <th>Trainer</th>
                    <th>Students</th>
                    <th>Schedule</th>
                    <th>Status</th>
                    <th>Progress</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td>
                            <div>
                                <div style="font-weight:600;color:var(--gray-900);font-size:14px;">{{ $batch->batch_name }}</div>
                                <div style="font-size:12px;color:var(--gray-500);font-family:'JetBrains Mono',monospace;">{{ $batch->batch_code ?? 'N/A' }}</div>
                            </div>
                        </td>
                        <td style="font-size:13px;color:var(--gray-600);">{{ $batch->course->title ?? 'N/A' }}</td>
                        <td style="font-size:13px;color:var(--gray-600);">{{ $batch->trainer->name ?? 'Unassigned' }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <span style="font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:500;color:var(--primary);">{{ $batch->student_count }}</span>
                                @if($batch->capacity)
                                    <span style="font-size:12px;color:var(--gray-400);">/ {{ $batch->capacity }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="font-size:12px;color:var(--gray-600);">
                                @if($batch->start_date && $batch->end_date)
                                    <div>{{ $batch->start_date->format('d M') }} - {{ $batch->end_date->format('d M Y') }}</div>
                                @endif
                                @if($batch->start_time && $batch->end_time)
                                    <div style="color:var(--gray-500);">{{ $batch->start_time }} - {{ $batch->end_time }}</div>
                                @endif
                                @if($batch->days)
                                    <div style="color:var(--gray-400);">{{ $batch->days }}</div>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($batch->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @elseif($batch->status === 'upcoming')
                                <span class="badge badge-info">Upcoming</span>
                            @elseif($batch->status === 'completed')
                                <span class="badge badge-warning">Completed</span>
                            @else
                                <span class="badge badge-danger">{{ ucfirst($batch->status) }}</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="flex:1;max-width:80px;height:6px;background:var(--gray-100);border-radius:3px;overflow:hidden;">
                                    <div style="height:100%;width:{{ $batch->progress }}%;background:{{ $batch->progress >= 70 ? 'var(--success)' : ($batch->progress >= 40 ? 'var(--tertiary)' : 'var(--danger)') }};border-radius:3px;"></div>
                                </div>
                                <span style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:500;color:var(--gray-600);min-width:40px;">{{ $batch->progress }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:60px 0;text-align:center;">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">calendar_month</span>
                                <p style="color:var(--gray-500);font-size:14px;">No batches found.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
