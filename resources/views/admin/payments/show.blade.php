@extends('layouts.admin')

@section('title', 'Payment Details')

@section('actions')
    <a href="{{ route('admin.payments.index') }}" class="btn btn-outline btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back
    </a>
@endsection

@section('content')
<div class="form-grid-2" style="align-items:start;">
    <div>
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">receipt_long</span>
                <h3>Payment Receipt</h3>
            </div>
            <div class="card-body">
                <div style="text-align:center;padding-bottom:20px;margin-bottom:20px;border-bottom:1px solid var(--gray-200);">
                    <div style="font-size:18px;font-weight:700;color:var(--gray-900);">Payment Receipt</div>
                    <div style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--gray-500);margin-top:4px;">{{ $payment->payment_id }}</div>
                </div>
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:140px;">Amount</td><td style="padding:10px 0;font-family:'JetBrains Mono',monospace;font-weight:700;font-size:20px;color:var(--primary);">₹{{ number_format($payment->amount ?? 0) }}</td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Date</td><td style="padding:10px 0;color:var(--gray-700);">{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y, h:i A') : 'N/A' }}</td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Method</td><td style="padding:10px 0;">
                        @if($payment->method === 'cash')
                            <span class="badge badge-success">Cash</span>
                        @elseif(in_array($payment->method ?? '', ['upi', 'gpay', 'phonepe', 'paytm']))
                            <span class="badge badge-info">{{ strtoupper($payment->method) }}</span>
                        @else
                            <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $payment->method ?? '')) }}</span>
                        @endif
                    </td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Status</td><td style="padding:10px 0;">
                        @if($payment->status === 'completed')
                            <span class="badge badge-success">Completed</span>
                        @elseif($payment->status === 'pending')
                            <span class="badge badge-warning">Pending</span>
                        @else
                            <span class="badge badge-danger">{{ ucfirst($payment->status ?? '') }}</span>
                        @endif
                    </td></tr>
                </table>
            </div>
        </div>

        @if($payment->transaction_id || $payment->upi_id || $payment->reference_number)
        <div class="card">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">tag</span>
                <h3>Transaction Details</h3>
            </div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    @if($payment->transaction_id)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:140px;">Transaction ID</td><td style="padding:10px 0;font-family:'JetBrains Mono',monospace;color:var(--gray-700);">{{ $payment->transaction_id }}</td></tr>
                    @endif
                    @if($payment->upi_id)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">UPI ID</td><td style="padding:10px 0;font-family:'JetBrains Mono',monospace;color:var(--gray-700);">{{ $payment->upi_id }}</td></tr>
                    @endif
                    @if($payment->reference_number)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Reference Number</td><td style="padding:10px 0;font-family:'JetBrains Mono',monospace;color:var(--gray-700);">{{ $payment->reference_number }}</td></tr>
                    @endif
                </table>
            </div>
        </div>
        @endif
    </div>

    <div>
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">person</span>
                <h3>Student & Enrollment</h3>
            </div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:140px;">Student</td><td style="padding:10px 0;font-weight:600;color:var(--gray-900);">{{ $payment->student->name ?? 'N/A' }}</td></tr>
                    @if($payment->student->phone)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Phone</td><td style="padding:10px 0;color:var(--gray-700);">{{ $payment->student->phone }}</td></tr>
                    @endif
                    @if($payment->enrollment)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Enrollment #</td><td style="padding:10px 0;font-family:'JetBrains Mono',monospace;color:var(--primary);">{{ $payment->enrollment->enrollment_number }}</td></tr>
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Course</td><td style="padding:10px 0;color:var(--gray-700);">{{ $payment->enrollment->course->title ?? 'N/A' }}</td></tr>
                    @endif
                </table>
            </div>
        </div>

        @if($payment->notes)
        <div class="card">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">notes</span>
                <h3>Notes</h3>
            </div>
            <div class="card-body">
                <p style="color:var(--gray-700);line-height:1.7;">{{ $payment->notes }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
