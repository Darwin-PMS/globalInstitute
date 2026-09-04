@extends('layouts.admin')

@section('title', 'Users')

@section('content')
<div class="card">
    <div class="card-header">
        <form method="GET" style="display:flex;gap:12px;flex:1;">
            <input type="text" name="search" class="form-control" placeholder="Search users..." value="{{ request('search') }}" style="max-width:300px;">
            <select name="role" class="form-control" style="max-width:200px;">
                <option value="">All Roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role->slug }}" {{ request('role') === $role->slug ? 'selected' : '' }}>{{ $role->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        </form>
    </div>
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th style="text-align:center;">Overrides</th>
                    <th style="width:180px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:36px;height:36px;border-radius:50%;background:var(--gray-100);display:flex;align-items:center;justify-content:center;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">person</span>
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:14px;">{{ $user->name }}</div>
                                    @if($user->is_admin)
                                        <span style="font-size:11px;color:var(--color-tertiary, #2e7d32);">Admin</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--gray-500);font-size:13px;">{{ $user->email }}</td>
                        <td>
                            @forelse($user->roles as $role)
                                <span class="badge {{ $role->is_system ? 'badge-primary' : 'badge-secondary' }}" style="font-size:11px;">{{ $role->name }}</span>
                            @empty
                                <span style="color:var(--gray-400);font-size:13px;">No role</span>
                            @endforelse
                        </td>
                        <td style="text-align:center;">
                            @php $overrideCount = $user->directPermissions()->count(); @endphp
                            @if($overrideCount > 0)
                                <span class="badge badge-warning">{{ $overrideCount }} override{{ $overrideCount > 1 ? 's' : '' }}</span>
                            @else
                                <span style="color:var(--gray-300);">—</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <a href="{{ route('admin.users.permissions', $user) }}" class="btn btn-sm btn-outline" title="Manage Permissions">
                                <span class="material-symbols-outlined" style="font-size:16px;">admin_panel_settings</span>
                                <span style="font-size:12px;">Permissions</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:40px;color:var(--gray-400);">
                            No users found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer">
        {{ $users->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
