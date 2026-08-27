@extends('layouts.admin')

@section('title', 'Student Profile')

@section('actions')
    <a href="{{ route('admin.students.index') }}" class="btn btn-outline btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back
    </a>
    <a href="{{ route('admin.students.edit', $student->id) }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">edit</span> Edit
    </a>
@endsection

@section('content')
<div class="form-grid-2" style="align-items:start;">
    <div>
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">person</span>
                <h3>Personal Information</h3>
            </div>
            <div class="card-body">
                <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--gray-200);">
                    <div style="width:64px;height:64px;border-radius:50%;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                        @if($student->photo)
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='64' height='64'%3E%3Crect fill='%23f1f3f5' width='64' height='64'/%3E%3C/svg%3E" data-src="{{ asset('storage/'.$student->photo) }}" alt="{{ $student->name }}" loading="lazy" decoding="async" class="lazy-img avatar" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            <span class="material-symbols-outlined" style="font-size:28px;color:var(--gray-400);">person</span>
                        @endif
                    </div>
                    <div>
                        <div style="font-weight:700;color:var(--gray-900);font-size:18px;">{{ $student->name }}</div>
                        <div style="font-size:13px;color:var(--gray-500);font-family:'JetBrains Mono',monospace;">{{ $student->student_id ?? 'N/A' }}</div>
                    </div>
                </div>
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:160px;">Phone</td><td style="padding:10px 0;color:var(--gray-700);">{{ $student->phone ?? 'N/A' }}</td></tr>
                    @if($student->whatsapp)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">WhatsApp</td><td style="padding:10px 0;color:var(--gray-700);">{{ $student->whatsapp }}</td></tr>
                    @endif
                    @if($student->email)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Email</td><td style="padding:10px 0;color:var(--gray-700);">{{ $student->email }}</td></tr>
                    @endif
                    @if($student->dob)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Date of Birth</td><td style="padding:10px 0;color:var(--gray-700);">{{ \Carbon\Carbon::parse($student->dob)->format('d M Y') }}</td></tr>
                    @endif
                    @if($student->gender)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Gender</td><td style="padding:10px 0;color:var(--gray-700);">{{ ucfirst($student->gender) }}</td></tr>
                    @endif
                    @if($student->father_name)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Father's Name</td><td style="padding:10px 0;color:var(--gray-700);">{{ $student->father_name }}</td></tr>
                    @endif
                    @if($student->mother_name)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Mother's Name</td><td style="padding:10px 0;color:var(--gray-700);">{{ $student->mother_name }}</td></tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">school</span>
                <h3>Education</h3>
            </div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:160px;">Qualification</td><td style="padding:10px 0;color:var(--gray-700);">{{ $student->qualification ?? 'N/A' }}</td></tr>
                    @if($student->institution)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Institution</td><td style="padding:10px 0;color:var(--gray-700);">{{ $student->institution }}</td></tr>
                    @endif
                    @if($student->passing_year)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Passing Year</td><td style="padding:10px 0;color:var(--gray-700);">{{ $student->passing_year }}</td></tr>
                    @endif
                    @if($student->experience)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Experience</td><td style="padding:10px 0;color:var(--gray-700);">{{ $student->experience }}</td></tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div>
        @if($student->address || $student->city || $student->state)
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">home</span>
                <h3>Address</h3>
            </div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    @if($student->address)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:100px;">Address</td><td style="padding:10px 0;color:var(--gray-700);">{{ $student->address }}</td></tr>
                    @endif
                    @if($student->city)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">City</td><td style="padding:10px 0;color:var(--gray-700);">{{ $student->city }}</td></tr>
                    @endif
                    @if($student->state)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">State</td><td style="padding:10px 0;color:var(--gray-700);">{{ $student->state }}</td></tr>
                    @endif
                    @if($student->pin)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">PIN</td><td style="padding:10px 0;color:var(--gray-700);">{{ $student->pin }}</td></tr>
                    @endif
                </table>
            </div>
        </div>
        @endif

        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">school</span>
                <h3>Enrollments</h3>
            </div>
            <div class="card-body" style="padding:0;">
                @if(isset($student->enrollments) && $student->enrollments->count())
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Batch</th>
                                <th>Fee Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($student->enrollments as $enrollment)
                                <tr>
                                    <td style="font-weight:600;color:var(--gray-900);font-size:13px;">{{ $enrollment->course->title ?? 'N/A' }}</td>
                                    <td style="font-size:13px;color:var(--gray-600);">{{ $enrollment->batch->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($enrollment->fee_status === 'paid')
                                            <span class="badge badge-success">Paid</span>
                                        @elseif($enrollment->fee_status === 'partial')
                                            <span class="badge badge-warning">Partial</span>
                                        @else
                                            <span class="badge badge-secondary">Unpaid</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="padding:40px 0;text-align:center;">
                        <p style="color:var(--gray-500);font-size:14px;">No enrollments yet</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">emergency</span>
                <h3>Emergency Contact</h3>
            </div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    @if($student->emergency_name)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:120px;">Name</td><td style="padding:10px 0;color:var(--gray-700);">{{ $student->emergency_name }}</td></tr>
                    @endif
                    @if($student->emergency_relationship)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Relationship</td><td style="padding:10px 0;color:var(--gray-700);">{{ $student->emergency_relationship }}</td></tr>
                    @endif
                    @if($student->emergency_phone)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Phone</td><td style="padding:10px 0;color:var(--gray-700);">{{ $student->emergency_phone }}</td></tr>
                    @endif
                    @if(!$student->emergency_name && !$student->emergency_phone)
                        <tr><td colspan="2" style="padding:20px 0;text-align:center;color:var(--gray-500);font-size:14px;">No emergency contact added</td></tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
