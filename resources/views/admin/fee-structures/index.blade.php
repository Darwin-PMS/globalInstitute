@extends('layouts.admin')

@section('title', 'Fee Structures')

@section('actions')
    <a href="{{ route('admin.fee-structures.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Fee Structure
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Structure Name</th>
                    <th>Course</th>
                    <th>Effective From</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th style="width:120px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($feeStructures ?? [] as $structure)
                    <tr>
                        <td style="color:var(--gray-400);font-family:'JetBrains Mono',monospace;font-size:12px;">#{{ $structure->id }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:40px;height:40px;border-radius:8px;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <span class="material-symbols-outlined" style="font-size:18px;color:var(--gray-400);">payments</span>
                                </div>
                                <div style="font-weight:600;color:var(--gray-900);font-size:14px;">{{ $structure->name }}</div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $structure->course->title ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);">{{ $structure->effective_from ? \Carbon\Carbon::parse($structure->effective_from)->format('d M Y') : 'N/A' }}</span>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-weight:500;font-size:13px;color:var(--gray-600);">₹{{ number_format($structure->total_amount ?? 0) }}</span>
                        </td>
                        <td>
                            @if($structure->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-warning">Inactive</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:6px;justify-content:flex-end;">
                                <a href="{{ route('admin.fee-structures.edit', $structure->id) }}" class="icon-btn icon-btn-edit" title="Edit">
                                    <span class="material-symbols-outlined">edit</span>
                                </a>
                                <form method="POST" action="{{ route('admin.fee-structures.destroy', $structure->id) }}" style="display:inline;" onsubmit="return confirm('Delete this fee structure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-btn icon-btn-delete" title="Delete">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:60px 0;text-align:center;">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">payments</span>
                                <p style="color:var(--gray-500);font-size:14px;">No fee structures yet</p>
                                <a href="{{ route('admin.fee-structures.create') }}" class="btn btn-primary btn-sm" style="margin-top:8px;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add First Fee Structure
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
