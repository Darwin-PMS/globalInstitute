@extends('layouts.admin')

@section('title', 'Student Progress Detail')

@section('actions')
    <a href="{{ route('admin.student-progress.index') }}" class="btn btn-outline btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back
    </a>
@endsection

@section('content')
<div class="form-grid-2" style="align-items:start;">
    <div>
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">person</span>
                <h3>Student Information</h3>
            </div>
            <div class="card-body">
                <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--gray-200);">
                    <div style="width:56px;height:56px;border-radius:50%;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                        @if($enrollment->student->photo)
                            <img src="{{ asset('storage/'.$enrollment->student->photo) }}" alt="{{ $enrollment->student->name ?? '' }}" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            <span class="material-symbols-outlined" style="font-size:24px;color:var(--gray-400);">person</span>
                        @endif
                    </div>
                    <div>
                        <div style="font-weight:700;color:var(--gray-900);font-size:16px;">{{ $enrollment->student->name ?? 'N/A' }}</div>
                        <div style="font-size:13px;color:var(--gray-500);">{{ $enrollment->student->email ?? '' }}</div>
                    </div>
                </div>
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:140px;">Course</td><td style="padding:10px 0;font-weight:600;color:var(--gray-900);">{{ $enrollment->course->title ?? 'N/A' }}</td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Batch</td><td style="padding:10px 0;color:var(--gray-700);">{{ $enrollment->batch->name ?? 'N/A' }}</td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Enrollment Date</td><td style="padding:10px 0;color:var(--gray-700);">{{ $enrollment->created_at ? $enrollment->created_at->format('d M Y') : 'N/A' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">bar_chart</span>
                <h3>Progress Overview</h3>
            </div>
            <div class="card-body">
                @php
                    $totalLessons = $enrollment->total_lessons ?? 0;
                    $completedLessons = $enrollment->lessons_completed ?? 0;
                    $progressPct = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
                @endphp
                <div style="display:flex;gap:24px;margin-bottom:20px;">
                    <div style="flex:1;text-align:center;padding:16px;border-radius:8px;background:var(--gray-50);">
                        <div style="font-family:'JetBrains Mono',monospace;font-size:24px;font-weight:700;color:var(--primary);">{{ $totalLessons }}</div>
                        <div style="font-size:12px;color:var(--gray-500);margin-top:4px;">Total Lessons</div>
                    </div>
                    <div style="flex:1;text-align:center;padding:16px;border-radius:8px;background:var(--gray-50);">
                        <div style="font-family:'JetBrains Mono',monospace;font-size:24px;font-weight:700;color:var(--success);">{{ $completedLessons }}</div>
                        <div style="font-size:12px;color:var(--gray-500);margin-top:4px;">Completed</div>
                    </div>
                    <div style="flex:1;text-align:center;padding:16px;border-radius:8px;background:var(--gray-50);">
                        <div style="font-family:'JetBrains Mono',monospace;font-size:24px;font-weight:700;color:{{ $progressPct >= 75 ? 'var(--success)' : ($progressPct >= 40 ? 'var(--warning)' : 'var(--primary)') }};">{{ $progressPct }}%</div>
                        <div style="font-size:12px;color:var(--gray-500);margin-top:4px;">Progress</div>
                    </div>
                </div>
                <div style="height:8px;border-radius:4px;background:var(--gray-200);overflow:hidden;">
                    <div style="height:100%;width:{{ $progressPct }}%;border-radius:4px;background:{{ $progressPct >= 75 ? 'var(--success)' : ($progressPct >= 40 ? 'var(--warning)' : 'var(--primary)') }};"></div>
                </div>
            </div>
        </div>
    </div>

    <div>
        @if(isset($modules) && count($modules))
            @foreach($modules as $module)
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header" style="cursor:pointer;" onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none'">
                    <span class="material-symbols-outlined" style="color:var(--primary);">folder</span>
                    <h3>{{ $module->name }}</h3>
                    @php
                        $moduleLessons = $module->lessons ?? [];
                        $moduleCompleted = $moduleLessons->filter(function($l) use ($completedLessonIds) {
                            return in_array($l->id, $completedLessonIds ?? []);
                        })->count();
                    @endphp
                    <span style="margin-left:auto;font-size:13px;color:var(--gray-500);font-family:'JetBrains Mono',monospace;">{{ $moduleCompleted }}/{{ $moduleLessons->count() }}</span>
                </div>
                <div class="card-body" style="padding:0;">
                    @forelse($moduleLessons as $lesson)
                        @php $isCompleted = in_array($lesson->id, $completedLessonIds ?? []); @endphp
                        <div style="display:flex;align-items:center;gap:12px;padding:12px 24px;border-bottom:1px solid var(--gray-100);{{ $loop->last ? 'border-bottom:none;' : '' }}">
                            <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:{{ $isCompleted ? 'rgba(39,174,96,0.1)' : 'var(--gray-100)' }};">
                                @if($isCompleted)
                                    <span class="material-symbols-outlined" style="font-size:16px;color:var(--success);">check</span>
                                @else
                                    <span class="material-symbols-outlined" style="font-size:16px;color:var(--gray-400);">radio_button_unchecked</span>
                                @endif
                            </div>
                            <div style="flex:1;">
                                <div style="font-weight:500;color:{{ $isCompleted ? 'var(--gray-500)' : 'var(--gray-900)' }};font-size:13px;{{ $isCompleted ? 'text-decoration:line-through;' : '' }}">{{ $lesson->title }}</div>
                            </div>
                            <span style="font-size:12px;color:{{ $isCompleted ? 'var(--success)' : 'var(--gray-400)' }};">{{ $isCompleted ? 'Completed' : 'Pending' }}</span>
                        </div>
                    @empty
                        <div style="padding:20px 24px;text-align:center;color:var(--gray-500);font-size:13px;">No lessons in this module.</div>
                    @endforelse
                </div>
            </div>
            @endforeach
        @else
            <div class="card">
                <div class="card-body" style="padding:60px 0;text-align:center;">
                    <div class="empty-state">
                        <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">menu_book</span>
                        <h3>No modules found</h3>
                        <p>Course modules and lessons will appear here.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
