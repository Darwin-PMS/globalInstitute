@extends('layouts.admin')

@section('title', 'Admission Details')

@section('actions')
    <a href="{{ route('admin.admissions.index') }}" class="btn btn-outline btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back
    </a>
    <a href="{{ route('admin.admissions.edit', $admission->id) }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">edit</span> Edit
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
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:140px;">Name</td><td style="padding:10px 0;font-weight:600;color:var(--gray-900);">{{ $admission->student->name ?? 'N/A' }}</td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Phone</td><td style="padding:10px 0;color:var(--gray-700);">{{ $admission->student->phone ?? 'N/A' }}</td></tr>
                    @if($admission->student->email)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Email</td><td style="padding:10px 0;color:var(--gray-700);">{{ $admission->student->email }}</td></tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">school</span>
                <h3>Course & Batch</h3>
            </div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:140px;">Admission #</td><td style="padding:10px 0;font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--primary);">{{ $admission->admission_number }}</td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Course</td><td style="padding:10px 0;font-weight:600;color:var(--gray-900);">{{ $admission->course->title ?? 'N/A' }}</td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Batch</td><td style="padding:10px 0;color:var(--gray-700);">{{ $admission->batch->name ?? 'N/A' }}</td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Date</td><td style="padding:10px 0;color:var(--gray-700);">{{ $admission->admission_date ? \Carbon\Carbon::parse($admission->admission_date)->format('d M Y') : 'N/A' }}</td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Status</td><td style="padding:10px 0;">
                        @if($admission->status === 'confirmed')
                            <span class="badge badge-success">Confirmed</span>
                        @elseif($admission->status === 'pending')
                            <span class="badge badge-warning">Pending</span>
                        @else
                            <span class="badge badge-secondary">{{ ucfirst($admission->status ?? '') }}</span>
                        @endif
                    </td></tr>
                </table>
            </div>
        </div>
    </div>

    <div>
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">payments</span>
                <h3>Fee Breakdown</h3>
            </div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:140px;">Total Fee</td><td style="padding:10px 0;font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--gray-900);">₹{{ number_format($admission->fee ?? 0) }}</td></tr>
                    @if(($admission->discount_amount ?? 0) > 0)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Discount</td><td style="padding:10px 0;font-family:'JetBrains Mono',monospace;color:var(--success);">-₹{{ number_format($admission->discount_amount) }} @if($admission->discount_type === 'percentage') ({{ $admission->discount_amount }}%) @endif</td></tr>
                    @endif
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Final Fee</td><td style="padding:10px 0;font-family:'JetBrains Mono',monospace;font-weight:700;font-size:16px;color:var(--primary);">₹{{ number_format($admission->final_fee ?? ($admission->fee ?? 0)) }}</td></tr>
                    <tr><td style="padding:10px 0;border-top:1px solid var(--gray-200);color:var(--gray-500);font-size:13px;">Paid</td><td style="padding:10px 0;border-top:1px solid var(--gray-200);font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--success);">₹{{ number_format($admission->paid ?? 0) }}</td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Balance</td><td style="padding:10px 0;font-family:'JetBrains Mono',monospace;font-weight:700;color:{{ ($admission->balance ?? 0) > 0 ? 'var(--danger)' : 'var(--success)' }};">₹{{ number_format($admission->balance ?? 0) }}</td></tr>
                </table>
            </div>
        </div>

        @if(isset($admission->payments) && $admission->payments->count())
        <div class="card">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">receipt_long</span>
                <h3>Payment History</h3>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($admission->payments as $payment)
                            <tr>
                                <td style="font-size:13px;color:var(--gray-600);">{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : 'N/A' }}</td>
                                <td style="font-family:'JetBrains Mono',monospace;font-weight:500;font-size:13px;color:var(--gray-600);">₹{{ number_format($payment->amount) }}</td>
                                <td>
                                    <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $payment->method ?? '')) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
