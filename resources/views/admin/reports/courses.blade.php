@extends('layouts.admin')

@section('title', 'Course Report')

@section('content')
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Course Name</th>
                    <th>Enrollments</th>
                    <th>Revenue</th>
                    <th>Avg Fee</th>
                    <th>Completion %</th>
                </tr>
            </thead>
            <tbody>
                @forelse($courses as $course)
                    <tr>
                        <td style="color:var(--gray-400);font-family:'JetBrains Mono',monospace;font-size:12px;">#{{ $course->id }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                @if($course->image)
                                    <div class="lazy-bg" data-bg="{{ asset('storage/'.$course->image) }}" style="width:44px;height:44px;border-radius:8px;background:var(--gray-100);border:1px solid var(--gray-200);flex-shrink:0;"></div>
                                @else
                                    <div style="width:44px;height:44px;border-radius:8px;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <span class="material-symbols-outlined" style="font-size:20px;color:var(--gray-400);">school</span>
                                    </div>
                                @endif
                                <div>
                                    <div style="font-weight:600;color:var(--gray-900);font-size:14px;">{{ $course->title }}</div>
                                    <div style="font-size:12px;color:var(--gray-500);">{{ $course->category->name ?? 'Uncategorized' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:500;color:var(--primary);">{{ $course->enrollments_count }}</span>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:600;color:var(--success);">₹{{ number_format($course->revenue) }}</span>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--gray-600);">₹{{ number_format($course->avg_fee) }}</span>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="flex:1;max-width:80px;height:6px;background:var(--gray-100);border-radius:3px;overflow:hidden;">
                                    <div style="height:100%;width:{{ $course->completion_rate }}%;background:{{ $course->completion_rate >= 70 ? 'var(--success)' : ($course->completion_rate >= 40 ? 'var(--tertiary)' : 'var(--danger)') }};border-radius:3px;"></div>
                                </div>
                                <span style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:500;color:var(--gray-600);min-width:40px;">{{ $course->completion_rate }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding:60px 0;text-align:center;">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">school</span>
                                <p style="color:var(--gray-500);font-size:14px;">No courses found.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
