@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="dashboard-welcome">
    <div class="dashboard-welcome-content">
        <h1>Welcome back, Admin</h1>
        <p>Here's what's happening with your institute today.</p>
        <div class="dashboard-welcome-actions">
            <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary btn-sm">
                <span class="material-symbols-outlined" style="font-size:18px;">analytics</span> View Reports
            </a>
            <a href="{{ route('admin.enquiries.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:#fff;">
                <span class="material-symbols-outlined" style="font-size:18px;">contact_page</span> View Enquiries
            </a>
        </div>
    </div>
</div>

<div class="stats-bar">
    <div class="stat-item">
        <div class="stat-item-icon primary"><span class="material-symbols-outlined">person</span></div>
        <div><div class="stat-item-value">{{ $totalStudents }}</div><div class="stat-item-label">Total Students</div></div>
    </div>
    <div class="stat-item">
        <div class="stat-item-icon" style="background:rgba(206,167,0,0.1);color:var(--color-tertiary);"><span class="material-symbols-outlined">groups</span></div>
        <div><div class="stat-item-value">{{ $totalTeachers }}</div><div class="stat-item-label">Total Teachers</div></div>
    </div>
    <div class="stat-item">
        <div class="stat-item-icon info"><span class="material-symbols-outlined">calendar_month</span></div>
        <div><div class="stat-item-value">{{ $activeBatches }}</div><div class="stat-item-label">Active Batches</div></div>
    </div>
    <div class="stat-item">
        <div class="stat-item-icon success"><span class="material-symbols-outlined">paid</span></div>
        <div><div class="stat-item-value">₹{{ number_format($totalRevenue) }}</div><div class="stat-item-label">Total Revenue</div></div>
    </div>
</div>

<div class="stats-bar">
    <div class="stat-item">
        <div class="stat-item-icon primary"><span class="material-symbols-outlined">how_to_reg</span></div>
        <div><div class="stat-item-value">{{ $thisMonthAdmissions }}</div><div class="stat-item-label">This Month Admissions</div></div>
    </div>
    <div class="stat-item">
        <div class="stat-item-icon warning"><span class="material-symbols-outlined">pending</span></div>
        <div><div class="stat-item-value">{{ $pendingEnquiries }}</div><div class="stat-item-label">Pending Enquiries</div></div>
    </div>
    <div class="stat-item">
        <div class="stat-item-icon info"><span class="material-symbols-outlined">school</span></div>
        <div><div class="stat-item-value">{{ $topCourses->count() }}</div><div class="stat-item-label">Top Courses</div></div>
    </div>
    <div class="stat-item">
        <div class="stat-item-icon success"><span class="material-symbols-outlined">show_chart</span></div>
        <div><div class="stat-item-value">{{ count($revenueByMonth) }}</div><div class="stat-item-label">Months Tracked</div></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <div class="card">
        <div class="dashboard-section-header">
            <h3>Recent Payments</h3>
            <a href="{{ route('admin.reports.payments') }}">View All</a>
        </div>
        @forelse($recentPayments as $payment)
        <div class="dashboard-list-item">
            <div class="dashboard-list-icon" style="background:var(--color-success);">
                <span class="material-symbols-outlined" style="font-size:18px;">paid</span>
            </div>
            <div class="dashboard-list-content">
                <p>{{ $payment->student->user->name ?? 'N/A' }}</p>
                <p>{{ $payment->payment_id }}</p>
            </div>
            <div class="dashboard-list-right">
                <p style="font-size:14px;font-weight:600;color:var(--color-success);font-family:'JetBrains Mono',monospace;">₹{{ number_format($payment->amount) }}</p>
                <p style="font-size:12px;color:var(--color-text-muted);">{{ $payment->payment_date->format('M d') }}</p>
            </div>
        </div>
        @empty
        <div class="empty-state" style="padding:32px;">
            <span class="material-symbols-outlined" style="font-size:40px;">payments</span>
            <h3>No payments yet</h3>
            <p>Payment records will appear here.</p>
        </div>
        @endforelse
    </div>

    <div class="card">
        <div class="dashboard-section-header">
            <h3>Recent Enquiries</h3>
            <a href="{{ route('admin.enquiries.index') }}">View All</a>
        </div>
        @forelse($recentEnquiries as $enquiry)
        <div class="dashboard-list-item">
            <div class="dashboard-list-icon" style="background:var(--color-primary);font-size:12px;">
                {{ strtoupper(substr($enquiry->name, 0, 2)) }}
            </div>
            <div class="dashboard-list-content">
                <p>{{ $enquiry->name }}</p>
                <p>{{ $enquiry->phone ?? 'No phone' }}</p>
            </div>
            <div class="dashboard-list-right">
                <p style="font-size:12px;color:var(--color-text-muted);">{{ $enquiry->created_at->format('M d') }}</p>
                @if($enquiry->status === 'new')
                <span class="badge badge-info">New</span>
                @elseif($enquiry->status === 'contacted')
                <span class="badge badge-warning">Contacted</span>
                @elseif($enquiry->status === 'converted')
                <span class="badge badge-success">Converted</span>
                @else
                <span class="badge badge-draft">{{ ucfirst($enquiry->status) }}</span>
                @endif
            </div>
        </div>
        @empty
        <div class="empty-state" style="padding:32px;">
            <span class="material-symbols-outlined" style="font-size:40px;">inbox</span>
            <h3>No enquiries yet</h3>
            <p>New enquiries will appear here.</p>
        </div>
        @endforelse
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-top:20px;">
    <div class="card">
        <div class="dashboard-section-header">
            <h3>Revenue (Last 6 Months)</h3>
        </div>
        <div class="card-body">
            <div style="display:flex;align-items:flex-end;gap:10px;height:180px;">
                @php $maxRevenue = $revenueByMonth->max('total') ?: 1; @endphp
                @foreach($revenueByMonth as $month)
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;">
                    <span style="font-size:11px;font-family:'JetBrains Mono',monospace;color:var(--color-text-muted);">₹{{ number_format($month['total'] / 1000) }}k</span>
                    <div style="width:100%;background:linear-gradient(180deg, var(--color-primary) 0%, #0a1f44 100%);border-radius:4px 4px 0 0;height:{{ max(4, ($month['total'] / $maxRevenue) * 140) }}px;transition:height 0.3s;"></div>
                    <span style="font-size:11px;color:var(--color-text-muted);font-weight:500;">{{ \Carbon\Carbon::parse($month['label'])->format('M') }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="dashboard-section-header">
            <h3>Top Courses</h3>
        </div>
        @forelse($topCourses as $index => $course)
        <div class="dashboard-list-item">
            <div style="width:32px;height:32px;border-radius:var(--border-radius-sm);background:{{ $index === 0 ? 'rgba(206,167,0,0.12)' : ($index === 1 ? 'rgba(0,8,30,0.06)' : 'rgba(8,145,178,0.1)') }};display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:{{ $index === 0 ? 'var(--color-tertiary)' : ($index === 1 ? 'var(--color-primary)' : 'var(--color-info)') }};flex-shrink:0;">
                {{ $index + 1 }}
            </div>
            <div class="dashboard-list-content">
                <p>{{ $course->title }}</p>
                <p>{{ $course->enrollments_count }} enrollments</p>
            </div>
            <span class="mono" style="font-size:13px;font-weight:500;color:var(--color-success);">₹{{ number_format($course->revenue) }}</span>
        </div>
        @empty
        <div class="empty-state" style="padding:32px;">
            <p>No enrollments yet</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
