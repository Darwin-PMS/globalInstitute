@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">
    <a href="{{ route('admin.reports.fee-collection') }}" style="background:#fff;border-radius:12px;padding:28px 24px;border:1px solid var(--gray-200);text-decoration:none;transition:all 0.2s;display:flex;flex-direction:column;gap:16px;" onmouseover="this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)';this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='none';this.style.transform='translateY(0)'">
        <div style="width:52px;height:52px;border-radius:12px;background:rgba(40,167,69,0.1);display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:28px;color:var(--success);">account_balance_wallet</span>
        </div>
        <div>
            <h3 style="font-family:'Montserrat';font-size:16px;font-weight:600;color:var(--primary);margin-bottom:4px;">Fee Collection</h3>
            <p style="font-size:13px;color:var(--gray-500);line-height:1.5;">Track fee collections by date range, course-wise breakdown and pending amounts.</p>
        </div>
        <div style="display:flex;align-items:center;gap:6px;color:var(--primary);font-size:13px;font-weight:500;margin-top:auto;">
            View Report <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
        </div>
    </a>

    <a href="{{ route('admin.reports.payments') }}" style="background:#fff;border-radius:12px;padding:28px 24px;border:1px solid var(--gray-200);text-decoration:none;transition:all 0.2s;display:flex;flex-direction:column;gap:16px;" onmouseover="this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)';this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='none';this.style.transform='translateY(0)'">
        <div style="width:52px;height:52px;border-radius:12px;background:rgba(0,8,30,0.06);display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:28px;color:var(--primary);">payments</span>
        </div>
        <div>
            <h3 style="font-family:'Montserrat';font-size:16px;font-weight:600;color:var(--primary);margin-bottom:4px;">Payment Report</h3>
            <p style="font-size:13px;color:var(--gray-500);line-height:1.5;">Detailed payment history with filters for method, status and date range.</p>
        </div>
        <div style="display:flex;align-items:center;gap:6px;color:var(--primary);font-size:13px;font-weight:500;margin-top:auto;">
            View Report <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
        </div>
    </a>

    <a href="{{ route('admin.reports.students') }}" style="background:#fff;border-radius:12px;padding:28px 24px;border:1px solid var(--gray-200);text-decoration:none;transition:all 0.2s;display:flex;flex-direction:column;gap:16px;" onmouseover="this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)';this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='none';this.style.transform='translateY(0)'">
        <div style="width:52px;height:52px;border-radius:12px;background:rgba(23,162,184,0.1);display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:28px;color:var(--info);">person_search</span>
        </div>
        <div>
            <h3 style="font-family:'Montserrat';font-size:16px;font-weight:600;color:var(--primary);margin-bottom:4px;">Student Report</h3>
            <p style="font-size:13px;color:var(--gray-500);line-height:1.5;">Student enrollment details, payment summaries and course-wise breakdowns.</p>
        </div>
        <div style="display:flex;align-items:center;gap:6px;color:var(--primary);font-size:13px;font-weight:500;margin-top:auto;">
            View Report <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
        </div>
    </a>

    <a href="{{ route('admin.reports.courses') }}" style="background:#fff;border-radius:12px;padding:28px 24px;border:1px solid var(--gray-200);text-decoration:none;transition:all 0.2s;display:flex;flex-direction:column;gap:16px;" onmouseover="this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)';this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='none';this.style.transform='translateY(0)'">
        <div style="width:52px;height:52px;border-radius:12px;background:rgba(206,167,0,0.1);display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:28px;color:var(--tertiary);">school</span>
        </div>
        <div>
            <h3 style="font-family:'Montserrat';font-size:16px;font-weight:600;color:var(--primary);margin-bottom:4px;">Course Report</h3>
            <p style="font-size:13px;color:var(--gray-500);line-height:1.5;">Enrollment counts, revenue per course, average fees and completion rates.</p>
        </div>
        <div style="display:flex;align-items:center;gap:6px;color:var(--primary);font-size:13px;font-weight:500;margin-top:auto;">
            View Report <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
        </div>
    </a>

    <a href="{{ route('admin.reports.batches') }}" style="background:#fff;border-radius:12px;padding:28px 24px;border:1px solid var(--gray-200);text-decoration:none;transition:all 0.2s;display:flex;flex-direction:column;gap:16px;" onmouseover="this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)';this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='none';this.style.transform='translateY(0)'">
        <div style="width:52px;height:52px;border-radius:12px;background:rgba(107,254,156,0.12);display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:28px;color:#006d37;">calendar_month</span>
        </div>
        <div>
            <h3 style="font-family:'Montserrat';font-size:16px;font-weight:600;color:var(--primary);margin-bottom:4px;">Batch Report</h3>
            <p style="font-size:13px;color:var(--gray-500);line-height:1.5;">Batch details with student count, trainer info, schedule and progress tracking.</p>
        </div>
        <div style="display:flex;align-items:center;gap:6px;color:var(--primary);font-size:13px;font-weight:500;margin-top:auto;">
            View Report <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
        </div>
    </a>
</div>
@endsection
