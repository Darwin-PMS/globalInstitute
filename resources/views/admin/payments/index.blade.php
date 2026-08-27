@extends('layouts.admin')

@section('title', 'Payments')

@section('actions')
    <a href="{{ route('admin.payments.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Record Payment
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:100px;">Payment #</th>
                    <th>Student</th>
                    <th>Enrollment</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th style="width:120px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments ?? [] as $payment)
                    <tr>
                        <td style="color:var(--gray-400);font-family:'JetBrains Mono',monospace;font-size:12px;">{{ $payment->payment_id }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:50%;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                                    @if($payment->student->photo)
                                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='32'%3E%3Crect fill='%23f1f3f5' width='32' height='32'/%3E%3C/svg%3E" data-src="{{ asset('storage/'.$payment->student->photo) }}" alt="{{ $payment->student->name ?? '' }}" loading="lazy" decoding="async" class="lazy-img avatar" style="width:100%;height:100%;object-fit:cover;">
                                    @else
                                        <span class="material-symbols-outlined" style="font-size:14px;color:var(--gray-400);">person</span>
                                    @endif
                                </div>
                                <div style="font-weight:600;color:var(--gray-900);font-size:13px;">{{ $payment->student->name ?? 'N/A' }}</div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);font-family:'JetBrains Mono',monospace;">{{ $payment->enrollment->enrollment_number ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-weight:600;font-size:13px;color:var(--gray-900);">₹{{ number_format($payment->amount ?? 0) }}</span>
                        </td>
                        <td>
                            @if($payment->method === 'cash')
                                <span class="badge badge-success">Cash</span>
                            @elseif(in_array($payment->method ?? '', ['upi', 'gpay', 'phonepe', 'paytm']))
                                <span class="badge badge-info">{{ strtoupper($payment->method) }}</span>
                            @elseif(in_array($payment->method ?? '', ['bank_transfer', 'card', 'net_banking']))
                                <span class="badge badge-warning">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $payment->method ?? '')) }}</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : 'N/A' }}</span>
                        </td>
                        <td>
                            @if($payment->status === 'completed')
                                <span class="badge badge-success">Completed</span>
                            @elseif($payment->status === 'pending')
                                <span class="badge badge-warning">Pending</span>
                            @elseif($payment->status === 'failed')
                                <span class="badge badge-danger">Failed</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($payment->status ?? '') }}</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:6px;justify-content:flex-end;">
                                <a href="{{ route('admin.payments.show', $payment->id) }}" class="icon-btn" title="View" style="color:var(--gray-500);">
                                    <span class="material-symbols-outlined">visibility</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="padding:60px 0;text-align:center;">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">payments</span>
                                <p style="color:var(--gray-500);font-size:14px;">No payments yet</p>
                                <a href="{{ route('admin.payments.create') }}" class="btn btn-primary btn-sm" style="margin-top:8px;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">add</span> Record First Payment
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
