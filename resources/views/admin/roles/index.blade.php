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
@php
    $totalRoles = $roles->count();
    $systemRoles = $roles->where('is_system', true)->count();
    $activeRoles = $roles->where('is_active', true)->count();
    $customRoles = $totalRoles - $systemRoles;
@endphp

<div class="stats-bar">
    <div class="stat-item">
        <div class="stat-item-icon primary"><span class="material-symbols-outlined" style="font-size:20px;">admin_panel_settings</span></div>
        <div>
            <div class="stat-item-value">{{ $totalRoles }}</div>
            <div class="stat-item-label">Total Roles</div>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-item-icon info"><span class="material-symbols-outlined" style="font-size:20px;">shield</span></div>
        <div>
            <div class="stat-item-value">{{ $systemRoles }}</div>
            <div class="stat-item-label">System Roles</div>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-item-icon success"><span class="material-symbols-outlined" style="font-size:20px;">check_circle</span></div>
        <div>
            <div class="stat-item-value">{{ $activeRoles }}</div>
            <div class="stat-item-label">Active</div>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-item-icon warning"><span class="material-symbols-outlined" style="font-size:20px;">tune</span></div>
        <div>
            <div class="stat-item-value">{{ $customRoles }}</div>
            <div class="stat-item-label">Custom Roles</div>
        </div>
    </div>
</div>

<style>
    .roles-toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; width: 100%; }
    .roles-toolbar .search-wrap { position: relative; display: flex; align-items: center; flex: 1; min-width: 200px; max-width: 340px; }
    .roles-toolbar .search-wrap .material-symbols-outlined { position: absolute; left: 9px; font-size: 16px; color: var(--gray-400); pointer-events: none; }
    .roles-toolbar .search-wrap .form-control { padding-left: 30px; }
    .roles-toolbar .count-label { margin-left: auto; font-size: 13px; color: var(--gray-400); }
    .roles-empty-filter { display: none; padding: 40px; text-align: center; color: var(--gray-400); }
    .roles-empty-filter.visible { display: block; }
</style>

<div class="card">
    <div class="card-header">
        <div class="roles-toolbar">
            <div class="search-wrap">
                <span class="material-symbols-outlined">search</span>
                <input type="text" id="role-search" class="form-control" placeholder="Search roles...">
            </div>
            <select id="role-filter" class="form-control" style="max-width:170px;">
                <option value="all">All types</option>
                <option value="system">System roles</option>
                <option value="custom">Custom roles</option>
                <option value="active">Active only</option>
                <option value="inactive">Inactive only</option>
            </select>
            <span class="count-label"><span id="role-visible-count">{{ $totalRoles }}</span> of {{ $totalRoles }}</span>
        </div>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
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
                        <th style="width:150px;text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="roles-body">
                    @forelse($roles as $role)
                        <tr data-role-row
                            data-name="{{ strtolower($role->name) }}"
                            data-slug="{{ strtolower($role->slug) }}"
                            data-desc="{{ strtolower($role->description) }}"
                            data-system="{{ $role->is_system ? '1' : '0' }}"
                            data-active="{{ $role->is_active ? '1' : '0' }}">
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:36px;height:36px;border-radius:8px;background:{{ $role->is_system ? 'var(--color-primary-bg, #e8f5e9)' : 'var(--gray-100)' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
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
                            <td style="color:var(--gray-500);font-size:13px;max-width:220px;">{{ $role->description ?: '—' }}</td>
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
                                        <button type="submit" class="btn btn-sm btn-outline btn-danger" title="{{ $role->users_count > 0 ? 'Cannot delete: role has users' : 'Delete' }}" {{ $role->users_count > 0 ? 'disabled' : '' }}>
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
        <div class="roles-empty-filter" id="roles-empty-filter">
            <span class="material-symbols-outlined" style="font-size:48px;display:block;margin-bottom:8px;">search_off</span>
            No roles match your filters.
        </div>
    </div>
</div>
@endsection
