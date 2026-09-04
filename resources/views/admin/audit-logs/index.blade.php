@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('content')
<div class="card">
    <div class="card-header">
        <form method="GET" style="display:flex;gap:8px;flex:1;flex-wrap:wrap;">
            <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}" style="max-width:200px;">
            <select name="module" class="form-control" style="max-width:150px;">
                <option value="">All Modules</option>
                @foreach($modules as $mod)
                    <option value="{{ $mod }}" {{ request('module') === $mod ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $mod)) }}</option>
                @endforeach
            </select>
            <select name="action" class="form-control" style="max-width:150px;">
                <option value="">All Actions</option>
                @foreach($actions as $act)
                    <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $act)) }}</option>
                @endforeach
            </select>
            <select name="result" class="form-control" style="max-width:120px;">
                <option value="">All Results</option>
                <option value="success" {{ request('result') === 'success' ? 'selected' : '' }}>Success</option>
                <option value="denied" {{ request('result') === 'denied' ? 'selected' : '' }}>Denied</option>
                <option value="failed" {{ request('result') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
            <input type="date" name="from" class="form-control" value="{{ request('from') }}" style="max-width:150px;" title="From date">
            <input type="date" name="to" class="form-control" value="{{ request('to') }}" style="max-width:150px;" title="To date">
            <button type="submit" class="btn btn-outline btn-sm">Filter</button>
            @if(request()->hasAny(['search', 'module', 'action', 'result', 'from', 'to']))
                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-sm btn-outline">Clear</a>
            @endif
        </form>
    </div>
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:160px;">Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Result</th>
                    <th>Details</th>
                    <th style="width:100px;">IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--gray-400);">
                            {{ $log->created_at->format('M d, H:i:s') }}
                        </td>
                        <td>
                            <div style="font-size:13px;font-weight:500;">{{ $log->user_name ?: 'System' }}</div>
                            <div style="font-size:11px;color:var(--gray-400);">{{ $log->user_email }}</div>
                        </td>
                        <td>
                            <span style="font-size:12px;background:var(--gray-100);padding:2px 8px;border-radius:4px;">
                                {{ str_replace('_', ' ', $log->action) }}
                            </span>
                        </td>
                        <td style="font-size:13px;">{{ $log->module ?: '—' }}</td>
                        <td>
                            @if($log->result === 'success')
                                <span class="badge badge-success" style="font-size:11px;">Success</span>
                            @elseif($log->result === 'denied')
                                <span class="badge badge-danger" style="font-size:11px;">Denied</span>
                            @else
                                <span class="badge badge-secondary" style="font-size:11px;">Failed</span>
                            @endif
                        </td>
                        <td style="font-size:12px;color:var(--gray-500);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $log->notes ?: '—' }}
                        </td>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--gray-400);">
                            {{ $log->ip_address ?: '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:40px;color:var(--gray-400);">
                            <span class="material-symbols-outlined" style="font-size:48px;display:block;margin-bottom:8px;">history</span>
                            No audit logs found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="card-footer">
        {{ $logs->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
