@extends('layouts.admin')

@section('title', 'Roles & Permissions')

@section('actions')
    @can('roles.create')
    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Role
    </a>
    @endcan
@endsection

@section('content')
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th style="text-align:center;">Users</th>
                    <th style="text-align:center;">Permissions</th>
                    <th style="text-align:center;">Menus</th>
                    <th>Status</th>
                    <th style="width:180px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:36px;height:36px;border-radius:8px;background:{{ $role->is_system ? 'var(--color-primary-bg, #e8f5e9)' : 'var(--gray-100)' }};display:flex;align-items:center;justify-content:center;">
                                    <span class="material-symbols-outlined" style="font-size:18px;color:{{ $role->is_system ? 'var(--color-tertiary, #2e7d32)' : 'var(--gray-500)' }};">{{ $role->slug === 'super-admin' ? 'shield' : ($role->slug === 'admin' ? 'admin_panel_settings' : ($role->slug === 'teacher' ? 'school' : ($role->slug === 'student' ? 'person' : 'account_balance'))) }}</span>
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:14px;">{{ $role->name }}</div>
                                    @if($role->is_system)
                                        <span style="font-size:11px;color:var(--color-tertiary, #2e7d32);background:var(--color-primary-bg, #e8f5e9);padding:2px 6px;border-radius:4px;">System</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td><code style="font-size:12px;background:var(--gray-100);padding:2px 6px;border-radius:4px;">{{ $role->slug }}</code></td>
                        <td style="color:var(--gray-500);font-size:13px;max-width:200px;">{{ $role->description ?: '—' }}</td>
                        <td style="text-align:center;font-weight:600;">{{ $role->users_count }}</td>
                        <td style="text-align:center;font-weight:600;">{{ $role->permissions_count }}</td>
                        <td style="text-align:center;font-weight:600;">{{ $role->menus_count }}</td>
                        <td>
                            @if($role->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-secondary">Inactive</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:4px;justify-content:flex-end;">
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline" title="Edit">
                                    <span class="material-symbols-outlined" style="font-size:16px;">edit</span>
                                </a>
                                @unless($role->is_system)
                                <form action="{{ route('admin.roles.duplicate', $role) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline" title="Duplicate">
                                        <span class="material-symbols-outlined" style="font-size:16px;">content_copy</span>
                                    </button>
                                </form>
                                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this role? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline btn-danger" title="Delete" {{ $role->users_count > 0 ? 'disabled' : '' }}>
                                        <span class="material-symbols-outlined" style="font-size:16px;">delete</span>
                                    </button>
                                </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:40px;color:var(--gray-400);">
                            <span class="material-symbols-outlined" style="font-size:48px;display:block;margin-bottom:8px;">admin_panel_settings</span>
                            No roles created yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
