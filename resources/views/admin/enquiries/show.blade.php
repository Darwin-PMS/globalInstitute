@extends('layouts.admin')

@section('title', 'Enquiry Details')

@section('actions')
    <a href="{{ route('admin.enquiries.index') }}" class="btn btn-outline btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back
    </a>
    <a href="{{ route('admin.enquiries.edit', $enquiry->id) }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">edit</span> Edit
    </a>
@endsection

@section('content')
<div class="form-grid-2" style="align-items:start;">
    <div>
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">person</span>
                <h3>Contact Information</h3>
            </div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:140px;">Name</td><td style="padding:10px 0;font-weight:600;color:var(--gray-900);">{{ $enquiry->name }}</td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Phone</td><td style="padding:10px 0;color:var(--gray-700);">{{ $enquiry->phone }}</td></tr>
                    @if($enquiry->email)
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Email</td><td style="padding:10px 0;color:var(--gray-700);">{{ $enquiry->email }}</td></tr>
                    @endif
                    @if($enquiry->qualification)
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Qualification</td><td style="padding:10px 0;color:var(--gray-700);">{{ $enquiry->qualification }}</td></tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">school</span>
                <h3>Course Interest</h3>
            </div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:140px;">Course</td><td style="padding:10px 0;font-weight:600;color:var(--gray-900);">{{ $enquiry->course->title ?? $enquiry->subject ?? 'N/A' }}</td></tr>
                    @if($enquiry->preferred_time)
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Preferred Time</td><td style="padding:10px 0;color:var(--gray-700);">{{ $enquiry->preferred_time }}</td></tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div>
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">info</span>
                <h3>Enquiry Details</h3>
            </div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:140px;">Status</td><td style="padding:10px 0;">
                        @if($enquiry->status === 'new')
                            <span class="badge badge-warning">New</span>
                        @elseif($enquiry->status === 'contacted')
                            <span class="badge badge-info">Contacted</span>
                        @elseif($enquiry->status === 'converted')
                            <span class="badge badge-success">Converted</span>
                        @else
                            <span class="badge badge-secondary">{{ ucfirst($enquiry->status) }}</span>
                        @endif
                    </td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Source</td><td style="padding:10px 0;color:var(--gray-700);">{{ ucfirst($enquiry->source ?? 'N/A') }}</td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Created</td><td style="padding:10px 0;color:var(--gray-700);">{{ $enquiry->created_at->format('d M Y, h:i A') }}</td></tr>
                </table>
            </div>
        </div>

        @if($enquiry->message)
        <div class="card">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">message</span>
                <h3>Message</h3>
            </div>
            <div class="card-body">
                <p style="color:var(--gray-700);line-height:1.7;">{{ $enquiry->message }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
