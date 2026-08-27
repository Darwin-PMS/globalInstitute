@extends('layouts.admin')

@section('title', 'Fee Collection Report')

@section('content')
<div style="background:#fff;border-radius:12px;padding:20px 24px;border:1px solid var(--gray-200);margin-bottom:24px;">
    <form method="GET" action="{{ route('admin.reports.fee-collection') }}" style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
        <div style="flex:1;min-width:180px;">
            <label style="display:block;font-size:13px;font-weight:500;color:var(--gray-700);margin-bottom:6px;">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" style="width:100%;padding:10px 12px;border:1px solid var(--gray-200);border-radius:8px;font-size:14px;color:var(--primary);outline:none;">
        </div>
        <div style="flex:1;min-width:180px;">
            <label style="display:block;font-size:13px;font-weight:500;color:var(--gray-700);margin-bottom:6px;">Date To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" style="width:100%;padding:10px 12px;border:1px solid var(--gray-200);border-radius:8px;font-size:14px;color:var(--primary);outline:none;">
        </div>
        <div style="flex:1;min-width:180px;">
            <label style="display:block;font-size:13px;font-weight:500;color:var(--gray-700);margin-bottom:6px;">Course</label>
            <select name="course_id" style="width:100%;padding:10px 12px;border:1px solid var(--gray-200);border-radius:8px;font-size:14px;color:var(--primary);background:#fff;outline:none;">
                <option value="">All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary btn-sm" style="padding:10px 20px;">
                <span class="material-symbols-outlined" style="font-size:18px;">filter_list</span> Filter
            </button>
            <a href="{{ route('admin.reports.fee-collection') }}" class="btn btn-sm" style="padding:10px 20px;background:var(--gray-100);color:var(--gray-600);border:1px solid var(--gray-200);border-radius:8px;text-decoration:none;">Clear</a>
        </div>
    </form>
</div>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
    <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid var(--gray-200);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(40,167,69,0.1);display:flex;align-items:center;justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:22px;color:var(--success);">paid</span>
            </div>
            <span style="font-size:13px;color:var(--gray-600);">Total Collected</span>
        </div>
        <p style="font-size:28px;font-weight:700;color:var(--primary);font-family:'Montserrat';">₹{{ number_format($totalCollected) }}</p>
    </div>
    <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid var(--gray-200);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(220,53,69,0.1);display:flex;align-items:center;justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:22px;color:var(--danger);">pending</span>
            </div>
            <span style="font-size:13px;color:var(--gray-600);">Total Pending</span>
        </div>
        <p style="font-size:28px;font-weight:700;color:var(--primary);font-family:'Montserrat';">₹{{ number_format($totalPending) }}</p>
    </div>
    <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid var(--gray-200);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(23,162,184,0.1);display:flex;align-items:center;justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:22px;color:var(--info);">calendar_month</span>
            </div>
            <span style="font-size:13px;color:var(--gray-600);">This Month</span>
        </div>
        <p style="font-size:28px;font-weight:700;color:var(--primary);font-family:'Montserrat';">₹{{ number_format($thisMonth) }}</p>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        @forelse($grouped as $date => $dayPayments)
            <div style="padding:16px 24px;border-bottom:1px solid var(--gray-100);background:var(--gray-50);">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="material-symbols-outlined" style="font-size:20px;color:var(--gray-400);">calendar_today</span>
                        <span style="font-size:14px;font-weight:600;color:var(--primary);">{{ \Carbon\Carbon::parse($date)->format('d M Y (l)') }}</span>
                    </div>
                    <span style="font-size:13px;font-weight:600;color:var(--success);">₹{{ number_format($dayPayments->where('status','completed')->sum('amount')) }}</span>
                </div>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dayPayments as $payment)
                        <tr>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--gray-500);">{{ $payment->payment_id }}</td>
                            <td>
                                <span style="font-weight:500;color:var(--primary);font-size:13px;">{{ $payment->student->user->name ?? 'N/A' }}</span>
                            </td>
                            <td style="font-size:13px;color:var(--gray-600);">{{ $payment->enrollment->course->title ?? 'N/A' }}</td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:500;">₹{{ number_format($payment->amount) }}</td>
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
                    @endforeach
                </tbody>
            </table>
        @empty
            <div style="padding:60px 0;text-align:center;">
                <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                    <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">receipt_long</span>
                    <p style="color:var(--gray-500);font-size:14px;">No payments found for the selected filters.</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
