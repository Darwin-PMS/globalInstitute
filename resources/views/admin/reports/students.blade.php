@extends('layouts.admin')

@section('title', 'Student Report')

@section('content')
<div style="background:#fff;border-radius:12px;padding:20px 24px;border:1px solid var(--gray-200);margin-bottom:24px;">
    <form method="GET" action="{{ route('admin.reports.students') }}" style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
        <div style="flex:1;min-width:180px;">
            <label style="display:block;font-size:13px;font-weight:500;color:var(--gray-700);margin-bottom:6px;">Course</label>
            <select name="course_id" style="width:100%;padding:10px 12px;border:1px solid var(--gray-200);border-radius:8px;font-size:14px;color:var(--primary);background:#fff;outline:none;">
                <option value="">All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1;min-width:180px;">
            <label style="display:block;font-size:13px;font-weight:500;color:var(--gray-700);margin-bottom:6px;">Batch</label>
            <select name="batch_id" style="width:100%;padding:10px 12px;border:1px solid var(--gray-200);border-radius:8px;font-size:14px;color:var(--primary);background:#fff;outline:none;">
                <option value="">All Batches</option>
                @foreach($batches as $batch)
                    <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>{{ $batch->batch_name }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1;min-width:180px;">
            <label style="display:block;font-size:13px;font-weight:500;color:var(--gray-700);margin-bottom:6px;">Status</label>
            <select name="status" style="width:100%;padding:10px 12px;border:1px solid var(--gray-200);border-radius:8px;font-size:14px;color:var(--primary);background:#fff;outline:none;">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="graduated" {{ request('status') === 'graduated' ? 'selected' : '' }}>Graduated</option>
            </select>
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary btn-sm" style="padding:10px 20px;">
                <span class="material-symbols-outlined" style="font-size:18px;">filter_list</span> Filter
            </button>
            <a href="{{ route('admin.reports.students') }}" class="btn btn-sm" style="padding:10px 20px;background:var(--gray-100);color:var(--gray-600);border:1px solid var(--gray-200);border-radius:8px;text-decoration:none;">Clear</a>
        </div>
    </form>
</div>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
    <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid var(--gray-200);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(0,8,30,0.06);display:flex;align-items:center;justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:22px;color:var(--primary);">people</span>
            </div>
            <span style="font-size:13px;color:var(--gray-600);">Total Students</span>
        </div>
        <p style="font-size:28px;font-weight:700;color:var(--primary);font-family:'Montserrat';">{{ $totalStudents }}</p>
    </div>
    <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid var(--gray-200);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(40,167,69,0.1);display:flex;align-items:center;justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:22px;color:var(--success);">check_circle</span>
            </div>
            <span style="font-size:13px;color:var(--gray-600);">Active</span>
        </div>
        <p style="font-size:28px;font-weight:700;color:var(--primary);font-family:'Montserrat';">{{ $activeStudents }}</p>
    </div>
    <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid var(--gray-200);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(23,162,184,0.1);display:flex;align-items:center;justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:22px;color:var(--info);">badge</span>
            </div>
            <span style="font-size:13px;color:var(--gray-600);">Enrolled</span>
        </div>
        <p style="font-size:28px;font-weight:700;color:var(--primary);font-family:'Montserrat';">{{ $enrolledStudents }}</p>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Student Name</th>
                    <th>Enrollments</th>
                    <th>Courses</th>
                    <th>Total Paid</th>
                    <th>Last Payment</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    <tr>
                        <td style="color:var(--gray-400);font-family:'JetBrains Mono',monospace;font-size:12px;">#{{ $student->id }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:36px;height:36px;border-radius:8px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;flex-shrink:0;">{{ strtoupper(substr($student->user->name ?? 'S', 0, 2)) }}</div>
                                <div>
                                    <div style="font-weight:500;color:var(--primary);font-size:13px;">{{ $student->user->name ?? 'N/A' }}</div>
                                    <div style="font-size:12px;color:var(--gray-500);">{{ $student->student_id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:500;">{{ $student->enrollment_count }}</span>
                        </td>
                        <td>
                            <div style="font-size:12px;color:var(--gray-600);max-width:200px;">
                                @foreach($student->enrollments->take(2) as $enrollment)
                                    <span style="display:inline-block;padding:2px 6px;background:var(--gray-100);border-radius:4px;margin:1px 2px;">{{ $enrollment->course->title ?? 'N/A' }}</span>
                                @endforeach
                                @if($student->enrollments->count() > 2)
                                    <span style="font-size:11px;color:var(--gray-400);">+{{ $student->enrollments->count() - 2 }} more</span>
                                @endif
                            </div>
                        </td>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:500;">₹{{ number_format($student->total_paid) }}</td>
                        <td style="font-size:12px;color:var(--gray-600);">
                            @if($student->last_payment)
                                {{ $student->last_payment->payment_date->format('d M Y') }}
                            @else
                                <span style="color:var(--gray-400);">No payments</span>
                            @endif
                        </td>
                        <td>
                            @if($student->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @elseif($student->status === 'inactive')
                                <span class="badge badge-warning">Inactive</span>
                            @else
                                <span class="badge badge-danger">{{ ucfirst($student->status) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:60px 0;text-align:center;">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">person_search</span>
                                <p style="color:var(--gray-500);font-size:14px;">No students found.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
