@extends('layouts.admin')

@section('title', 'Edit Role: ' . $role->name)

@section('actions')
<a href="{{ route('admin.roles.index') }}" class="btn btn-outline btn-sm">
    <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back
</a>
<button type="submit" form="role-form" class="btn btn-primary btn-sm">
    <span class="material-symbols-outlined" style="font-size:18px;">save</span> Save Changes
</button>
@endsection

@section('content')
@php
    $selectedPerms = old('permissions', $assignedPermSlugs);
    $selectedMenus = old('menus', $assignedMenuIds);
    $totalPerms = $groupedPermissions->reduce(fn ($carry, $group) => $carry + $group->count(), 0);
@endphp

<form id="role-form" action="{{ route('admin.roles.update', $role) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-item-icon primary"><span class="material-symbols-outlined" style="font-size:20px;">key</span></div>
            <div>
                <div class="stat-item-value" id="sum-perms">{{ count($selectedPerms) }} / {{ $totalPerms }}</div>
                <div class="stat-item-label">Permissions</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-item-icon info"><span class="material-symbols-outlined" style="font-size:20px;">menu</span></div>
            <div>
                <div class="stat-item-value" id="sum-menus">{{ count($selectedMenus) }} / {{ $menus->count() }}</div>
                <div class="stat-item-label">Menus</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-item-icon warning"><span class="material-symbols-outlined" style="font-size:20px;">group</span></div>
            <div>
                <div class="stat-item-value">{{ $role->users->count() }}</div>
                <div class="stat-item-label">Assigned Users</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-item-icon {{ $role->is_active ? 'success' : 'danger' }}"><span class="material-symbols-outlined" style="font-size:20px;">{{ $role->is_active ? 'check_circle' : 'block' }}</span></div>
            <div>
                <div class="stat-item-value">{{ $role->is_active ? 'Active' : 'Inactive' }}</div>
                <div class="stat-item-label">Status</div>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;" class="role-top-grid">
        {{-- Left: Basic Info --}}
        <div>
            <div class="card">
                <div class="card-header"><h3>Role Information</h3></div>
                <div class="card-body">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="name">Role Name <span style="color:red;">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $role->name) }}" required>
                        </div>
                        <div class="form-group">
                            <label>Slug</label>
                            <input type="text" class="form-control" value="{{ $role->slug }}" disabled>
                            <small class="form-text">Slugs cannot be changed after creation.</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3" placeholder="What is this role for?">{{ old('description', $role->description) }}</textarea>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $role->is_active) ? 'checked' : '' }}>
                            <span class="toggle-track" aria-hidden="true"></span>
                            <span class="toggle-label">Active</span>
                        </label>
                        <small class="form-text" style="display:block;margin-top:6px;">Inactive roles are hidden and their permissions are not applied.</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Users --}}
        <div>
            <div class="card">
                <div class="card-header"><h3>Users with this Role ({{ $role->users->count() }})</h3></div>
                <div class="card-body">
                    @forelse($role->users->take(10) as $user)
                        <div style="display:flex;align-items:center;gap:8px;padding:6px 0;">
                            <div style="width:28px;height:28px;border-radius:50%;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span class="material-symbols-outlined" style="font-size:14px;">person</span>
                            </div>
                            <div style="min-width:0;">
                                <div style="font-size:14px;font-weight:500;" class="truncate">{{ $user->name }}</div>
                                <div style="font-size:12px;color:var(--gray-400);" class="truncate">{{ $user->email }}</div>
                            </div>
                        </div>
                    @empty
                        <p style="color:var(--gray-400);font-size:14px;margin:0;">No users assigned to this role.</p>
                    @endforelse
                    @if($role->users->count() > 10)
                        <div style="margin-top:8px;font-size:13px;color:var(--gray-400);">+{{ $role->users->count() - 10 }} more</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Permissions --}}
    @include('admin.roles.partials.permissions', ['groupedPermissions' => $groupedPermissions, 'checkedPerms' => $selectedPerms])

    {{-- Menus --}}
    @include('admin.roles.partials.menus', ['menus' => $menus, 'checkedMenus' => $selectedMenus])

    <div style="margin-top:24px;margin-bottom:48px;display:flex;gap:8px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Save Changes
        </button>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

<style>
    .toggle-switch { position: relative; display: inline-flex; align-items: center; gap: 10px; cursor: pointer; user-select: none; }
    .toggle-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
    .toggle-track { position: relative; width: 42px; height: 24px; background: var(--gray-300, #cbd5e1); border-radius: 999px; transition: background .2s ease; flex-shrink: 0; }
    .toggle-track::after { content: ''; position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background: #fff; border-radius: 50%; box-shadow: 0 1px 2px rgba(0,0,0,.2); transition: transform .2s ease; }
    .toggle-switch input:checked + .toggle-track { background: var(--color-success, #16a34a); }
    .toggle-switch input:checked + .toggle-track::after { transform: translateX(18px); }
    .toggle-switch input:focus-visible + .toggle-track { box-shadow: 0 0 0 3px var(--color-success-bg, rgba(22,163,74,.25)); }
    .toggle-switch .toggle-label { font-size: 14px; font-weight: 500; }

    @media (max-width: 900px) {
        .role-top-grid { grid-template-columns: 1fr !important; }
    }
</style>
@endsection
