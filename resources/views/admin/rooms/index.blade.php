@extends('layouts.admin')

@section('title', 'Rooms')

@section('actions')
    <a href="{{ route('admin.rooms.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Room
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Room</th>
                    <th>Capacity</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th style="width:120px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rooms ?? [] as $room)
                    <tr>
                        <td style="color:var(--gray-400);font-family:'JetBrains Mono',monospace;font-size:12px;">#{{ $room->id }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:40px;height:40px;border-radius:8px;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <span class="material-symbols-outlined" style="font-size:18px;color:var(--gray-400);">meeting_room</span>
                                </div>
                                <div>
                                    <div style="font-weight:600;color:var(--gray-900);font-size:14px;">{{ $room->name }}</div>
                                    @if($room->room_number)
                                        <div style="font-size:12px;color:var(--gray-500);font-family:'JetBrains Mono',monospace;">{{ $room->room_number }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;color:var(--gray-600);display:flex;align-items:center;gap:6px;">
                                <span class="material-symbols-outlined" style="font-size:16px;color:var(--gray-400);">group</span>
                                {{ $room->capacity ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            @if($room->type === 'classroom')
                                <span class="badge badge-success">Classroom</span>
                            @elseif($room->type === 'lab')
                                <span class="badge badge-warning">Lab</span>
                            @elseif($room->type === 'seminar')
                                <span class="badge badge-info">Seminar Hall</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($room->type ?? '') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($room->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-warning">Inactive</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:6px;justify-content:flex-end;">
                                <a href="{{ route('admin.rooms.edit', $room->id) }}" class="icon-btn icon-btn-edit" title="Edit">
                                    <span class="material-symbols-outlined">edit</span>
                                </a>
                                <form method="POST" action="{{ route('admin.rooms.destroy', $room->id) }}" style="display:inline;" onsubmit="return confirm('Delete this room?')">
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
                        <td colspan="6" style="padding:60px 0;text-align:center;">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">meeting_room</span>
                                <p style="color:var(--gray-500);font-size:14px;">No rooms yet</p>
                                <a href="{{ route('admin.rooms.create') }}" class="btn btn-primary btn-sm" style="margin-top:8px;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add First Room
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
