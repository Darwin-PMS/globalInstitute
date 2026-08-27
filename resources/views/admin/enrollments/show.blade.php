@extends('layouts.admin')

@section('title', 'Enrollment Details')

@section('actions')
    <a href="{{ route('admin.enrollments.index') }}" class="btn btn-outline btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back
    </a>
    <a href="{{ route('admin.enrollments.edit', $enrollment->id) }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">edit</span> Edit
    </a>
    @if(($enrollment->balance ?? 0) > 0)
    <a href="{{ route('admin.payments.create', ['enrollment_id' => $enrollment->id]) }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">payments</span> Record Payment
    </a>
    @endif
@endsection

@section('content')
<div class="form-grid-2" style="align-items:start;">
    <div>
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">person</span>
                <h3>Student & Enrollment</h3>
            </div>
            <div class="card-body">
                <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--gray-200);">
                    <div style="width:56px;height:56px;border-radius:50%;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                        @if($enrollment->student->photo)
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='56' height='56'%3E%3Crect fill='%23f1f3f5' width='56' height='56'/%3E%3C/svg%3E" data-src="{{ asset('storage/'.$enrollment->student->photo) }}" alt="{{ $enrollment->student->name ?? '' }}" loading="lazy" decoding="async" class="lazy-img avatar" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            <span class="material-symbols-outlined" style="font-size:24px;color:var(--gray-400);">person</span>
                        @endif
                    </div>
                    <div>
                        <div style="font-weight:700;color:var(--gray-900);font-size:16px;">{{ $enrollment->student->name ?? 'N/A' }}</div>
                        <div style="font-size:13px;color:var(--gray-500);font-family:'JetBrains Mono',monospace;">{{ $enrollment->enrollment_number }}</div>
                    </div>
                </div>
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:140px;">Course</td><td style="padding:10px 0;font-weight:600;color:var(--gray-900);">{{ $enrollment->course->title ?? 'N/A' }}</td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Batch</td><td style="padding:10px 0;color:var(--gray-700);">{{ $enrollment->batch->name ?? 'N/A' }}</td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Status</td><td style="padding:10px 0;">
                        @if($enrollment->status === 'active')
                            <span class="badge badge-success">Active</span>
                        @elseif($enrollment->status === 'completed')
                            <span class="badge badge-secondary">Completed</span>
                        @else
                            <span class="badge badge-warning">{{ ucfirst($enrollment->status ?? '') }}</span>
                        @endif
                    </td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Enrollment Date</td><td style="padding:10px 0;color:var(--gray-700);">{{ $enrollment->created_at ? $enrollment->created_at->format('d M Y') : 'N/A' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">payments</span>
                <h3>Fee Breakdown</h3>
            </div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:140px;">Total Fee</td><td style="padding:10px 0;font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--gray-900);">₹{{ number_format($enrollment->total_fee ?? 0) }}</td></tr>
                    @if(($enrollment->discount ?? 0) > 0)
                        <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Discount</td><td style="padding:10px 0;font-family:'JetBrains Mono',monospace;color:var(--success);">-₹{{ number_format($enrollment->discount) }}</td></tr>
                    @endif
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Final Fee</td><td style="padding:10px 0;font-family:'JetBrains Mono',monospace;font-weight:700;font-size:16px;color:var(--primary);">₹{{ number_format($enrollment->final_fee ?? 0) }}</td></tr>
                    <tr><td style="padding:10px 0;border-top:1px solid var(--gray-200);color:var(--gray-500);font-size:13px;">Paid</td><td style="padding:10px 0;border-top:1px solid var(--gray-200);font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--success);">₹{{ number_format($enrollment->paid_amount ?? 0) }}</td></tr>
                    <tr><td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Balance</td><td style="padding:10px 0;font-family:'JetBrains Mono',monospace;font-weight:700;color:{{ ($enrollment->balance ?? 0) > 0 ? 'var(--danger)' : 'var(--success)' }};">₹{{ number_format($enrollment->balance ?? 0) }}</td></tr>
                </table>
                <div style="margin-top:12px;">
                    @if($enrollment->fee_status === 'paid')
                        <span class="badge badge-success">Fully Paid</span>
                    @elseif($enrollment->fee_status === 'partial')
                        <span class="badge badge-warning">Partially Paid</span>
                    @elseif($enrollment->fee_status === 'overdue')
                        <span class="badge badge-danger">Overdue</span>
                    @else
                        <span class="badge badge-secondary">Unpaid</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div>
        @if(isset($enrollment->installments) && $enrollment->installments->count())
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">event_repeat</span>
                <h3>Fee Installments</h3>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollment->installments as $i => $installment)
                            <tr>
                                <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--gray-400);">{{ $i + 1 }}</td>
                                <td style="font-size:13px;color:var(--gray-600);">{{ $installment->due_date ? \Carbon\Carbon::parse($installment->due_date)->format('d M Y') : 'N/A' }}</td>
                                <td style="font-family:'JetBrains Mono',monospace;font-weight:500;font-size:13px;color:var(--gray-600);">₹{{ number_format($installment->amount ?? 0) }}</td>
                                <td>
                                    @if($installment->status === 'paid')
                                        <span class="badge badge-success">Paid</span>
                                    @elseif($installment->status === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($installment->status ?? '') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if(isset($enrollment->payments) && $enrollment->payments->count())
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <span class="material-symbols-outlined" style="color:var(--primary);">receipt_long</span>
                <h3>Payment History</h3>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Payment #</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollment->payments as $payment)
                            <tr>
                                <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--gray-400);">{{ $payment->payment_id }}</td>
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
