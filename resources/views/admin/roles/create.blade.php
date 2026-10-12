@extends('layouts.admin')

@section('title', 'Create Role')

@section('actions')
<a href="{{ route('admin.roles.index') }}" class="btn btn-outline btn-sm">
    <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back
</a>
<button type="submit" form="role-form" class="btn btn-primary btn-sm">
    <span class="material-symbols-outlined" style="font-size:18px;">check</span> Create Role
</button>
@endsection

@section('content')
@php
    $selectedPerms = old('permissions', []);
    $selectedMenus = old('menus', []);
    $totalPerms = $groupedPermissions->reduce(fn ($carry, $group) => $carry + $group->count(), 0);
@endphp

<form id="role-form" action="{{ route('admin.roles.store') }}" method="POST">
    @csrf

    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-item-icon primary"><span class="material-symbols-outlined" style="font-size:20px;">key</span></div>
            <div>
                <div class="stat-item-value" id="sum-perms">0 / {{ $totalPerms }}</div>
                <div class="stat-item-label">Permissions</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-item-icon info"><span class="material-symbols-outlined" style="font-size:20px;">menu</span></div>
            <div>
                <div class="stat-item-value" id="sum-menus">0 / {{ $menus->count() }}</div>
                <div class="stat-item-label">Menus</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-item-icon warning"><span class="material-symbols-outlined" style="font-size:20px;">lightbulb</span></div>
            <div>
                <div class="stat-item-value" style="font-size:13px;font-weight:600;line-height:1.3;">Optional</div>
                <div class="stat-item-label">Assign now or later</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Role Information</h3></div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="name">Role Name <span style="color:red;">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
                </div>
                <div class="form-group">
                    <label for="slug">Slug <span style="color:red;">*</span></label>
                    <input type="text" name="slug" id="slug" class="form-control" value="{{ old('slug') }}" required placeholder="e.g. content-manager">
                    <small class="form-text">Lowercase, hyphens only. Used internally.</small>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3" placeholder="What is this role for?">{{ old('description') }}</textarea>
            </div>
        </div>
    </div>

    {{-- Permissions --}}
    @include('admin.roles.partials.permissions', ['groupedPermissions' => $groupedPermissions, 'checkedPerms' => $selectedPerms])

    {{-- Menus --}}
    @include('admin.roles.partials.menus', ['menus' => $menus, 'checkedMenus' => $selectedMenus])

    <div style="margin-top:24px;margin-bottom:48px;display:flex;gap:8px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">check</span> Create Role
        </button>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>
@endsection
