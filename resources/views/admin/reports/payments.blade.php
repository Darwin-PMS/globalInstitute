@extends('layouts.admin')

@section('title', 'Payment Report')

@section('content')
<div style="background:#fff;border-radius:12px;padding:20px 24px;border:1px solid var(--gray-200);margin-bottom:24px;">
    <form method="GET" action="{{ route('admin.reports.payments') }}" style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
        <div style="flex:1;min-width:160px;">
            <label style="display:block;font-size:13px;font-weight:500;color:var(--gray-700);margin-bottom:6px;">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" style="width:100%;padding:10px 12px;border:1px solid var(--gray-200);border-radius:8px;font-size:14px;color:var(--primary);outline:none;">
        </div>
        <div style="flex:1;min-width:160px;">
            <label style="display:block;font-size:13px;font-weight:500;color:var(--gray-700);margin-bottom:6px;">Date To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" style="width:100%;padding:10px 12px;border:1px solid var(--gray-200);border-radius:8px;font-size:14px;color:var(--primary);outline:none;">
        </div>
        <div style="flex:1;min-width:160px;">
            <label style="display:block;font-size:13px;font-weight:500;color:var(--gray-700);margin-bottom:6px;">Method</label>
            <select name="method" style="width:100%;padding:10px 12px;border:1px solid var(--gray-200);border-radius:8px;font-size:14px;color:var(--primary);background:#fff;outline:none;">
                <option value="">All Methods</option>
                <option value="cash" {{ request('method') === 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="upi" {{ request('method') === 'upi' ? 'selected' : '' }}>UPI</option>
                <option value="bank_transfer" {{ request('method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                <option value="online" {{ request('method') === 'online' ? 'selected' : '' }}>Online</option>
                <option value="card" {{ request('method') === 'card' ? 'selected' : '' }}>Card</option>
                <option value="cheque" {{ request('method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
            </select>
        </div>
        <div style="flex:1;min-width:160px;">
            <label style="display:block;font-size:13px;font-weight:500;color:var(--gray-700);margin-bottom:6px;">Status</label>
            <select name="status" style="width:100%;padding:10px 12px;border:1px solid var(--gray-200);border-radius:8px;font-size:14px;color:var(--primary);background:#fff;outline:none;">
                <option value="">All Status</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary btn-sm" style="padding:10px 20px;">
                <span class="material-symbols-outlined" style="font-size:18px;">filter_list</span> Filter
            </button>
            <a href="{{ route('admin.reports.payments') }}" class="btn btn-sm" style="padding:10px 20px;background:var(--gray-100);color:var(--gray-600);border:1px solid var(--gray-200);border-radius:8px;text-decoration:none;">Clear</a>
        </div>
    </form>
</div>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid var(--gray-200);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(0,8,30,0.06);display:flex;align-items:center;justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:22px;color:var(--primary);">payments</span>
            </div>
            <span style="font-size:13px;color:var(--gray-600);">Total Payments</span>
        </div>
        <p style="font-size:28px;font-weight:700;color:var(--primary);font-family:'Montserrat';">₹{{ number_format($totalAmount) }}</p>
    </div>
    <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid var(--gray-200);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(40,167,69,0.1);display:flex;align-items:center;justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:22px;color:var(--success);">money</span>
            </div>
            <span style="font-size:13px;color:var(--gray-600);">Cash</span>
        </div>
        <p style="font-size:28px;font-weight:700;color:var(--primary);font-family:'Montserrat';">₹{{ number_format($cashTotal) }}</p>
    </div>
    <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid var(--gray-200);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(23,162,184,0.1);display:flex;align-items:center;justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:22px;color:var(--info);">qr_code_2</span>
            </div>
            <span style="font-size:13px;color:var(--gray-600);">UPI</span>
        </div>
        <p style="font-size:28px;font-weight:700;color:var(--primary);font-family:'Montserrat';">₹{{ number_format($upiTotal) }}</p>
    </div>
    <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid var(--gray-200);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(206,167,0,0.1);display:flex;align-items:center;justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:22px;color:var(--tertiary);">account_balance</span>
            </div>
            <span style="font-size:13px;color:var(--gray-600);">Bank / Online</span>
        </div>
        <p style="font-size:28px;font-weight:700;color:var(--primary);font-family:'Montserrat';">₹{{ number_format($bankTotal) }}</p>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:140px;">Payment ID</th>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--gray-500);">{{ $payment->payment_id }}</td>
                        <td>
                            <span style="font-weight:500;color:var(--primary);font-size:13px;">{{ $payment->student->user->name ?? 'N/A' }}</span>
                        </td>
                        <td style="font-size:13px;color:var(--gray-600);">{{ $payment->enrollment->course->title ?? 'N/A' }}</td>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:500;">₹{{ number_format($payment->amount) }}</td>
                        <td style="font-size:13px;color:var(--gray-600);">{{ $payment->payment_date->format('d M Y') }}</td>
                        <td>
                            <span style="font-size:12px;padding:3px 8px;border-radius:4px;font-weight:500;background:var(--gray-100);color:var(--gray-600);text-transform:capitalize;">{{ $payment->payment_method }}</span>
                        </td>
                        <td>
                            @if($payment->status === 'completed')
                                <span class="badge badge-success">Completed</span>
                            @elseif($payment->status === 'pending')
                                <span class="badge badge-warning">Pending</span>
                            @else
                                <span class="badge badge-danger">{{ ucfirst($payment->status) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:60px 0;text-align:center;">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">payments</span>
                                <p style="color:var(--gray-500);font-size:14px;">No payments found.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($payments->hasPages())
    <div style="margin-top:16px;display:flex;justify-content:center;">
        {{ $payments->links() }}
    </div>
@endif
@endsection
