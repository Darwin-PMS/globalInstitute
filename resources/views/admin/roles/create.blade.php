@extends('layouts.admin')

@section('title', 'Create Role')

@section('content')
<form action="{{ route('admin.roles.store') }}" method="POST">
    @csrf
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
        {{-- Left: Basic Info --}}
        <div>
            <div class="card">
                <div class="card-header"><h3>Role Information</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Role Name <span style="color:red;">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="slug">Slug <span style="color:red;">*</span></label>
                        <input type="text" name="slug" id="slug" class="form-control" value="{{ old('slug') }}" required placeholder="e.g. content-manager">
                        <small style="color:var(--gray-400);">Lowercase, hyphens only. Used internally.</small>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Summary --}}
        <div>
            <div class="card">
                <div class="card-header"><h3>Summary</h3></div>
                <div class="card-body">
                    <p style="color:var(--gray-500);font-size:14px;">Configure permissions and menus after creating the role.</p>
                    <div style="margin-top:24px;">
                        <button type="submit" class="btn btn-primary">Create Role</button>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Permissions --}}
    <div class="card" style="margin-top:24px;">
        <div class="card-header">
            <h3>Permissions</h3>
            <label style="font-size:13px;font-weight:normal;display:flex;align-items:center;gap:6px;cursor:pointer;">
                <input type="checkbox" id="select-all-perms"> Select All
            </label>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
            @foreach($groupedPermissions as $module => $permissions)
                <div style="border:1px solid var(--gray-200);border-radius:8px;padding:12px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <strong style="font-size:13px;text-transform:uppercase;color:var(--gray-600);">{{ $module }}</strong>
                        <label style="font-size:12px;cursor:pointer;"><input type="checkbox" class="module-toggle" data-module="{{ $module }}"> All</label>
                    </div>
                    @foreach($permissions as $perm)
                        <label style="display:flex;align-items:center;gap:6px;padding:3px 0;font-size:13px;cursor:pointer;">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->slug }}" class="perm-check" data-module="{{ $module }}" {{ in_array($perm->slug, old('permissions', [])) ? 'checked' : '' }}>
                            {{ $perm->action }}
                        </label>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    {{-- Menus --}}
    <div class="card" style="margin-top:24px;">
        <div class="card-header">
            <h3>Menu Access</h3>
            <label style="font-size:13px;font-weight:normal;display:flex;align-items:center;gap:6px;cursor:pointer;">
                <input type="checkbox" id="select-all-menus"> Select All
            </label>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:8px;">
            @foreach($menus->whereNull('parent_id') as $parent)
                <div style="border:1px solid var(--gray-200);border-radius:8px;padding:12px;">
                    <label style="display:flex;align-items:center;gap:6px;font-weight:600;font-size:14px;cursor:pointer;">
                        <input type="checkbox" name="menus[]" value="{{ $parent->id }}" class="menu-check" {{ in_array($parent->id, old('menus', [])) ? 'checked' : '' }}>
                        @if($parent->icon)<span class="material-symbols-outlined" style="font-size:16px;">{{ $parent->icon }}</span>@endif
                        {{ $parent->name }}
                    </label>
                    @foreach($menus->where('parent_id', $parent->id) as $child)
                        <label style="display:flex;align-items:center;gap:6px;padding:2px 0 2px 24px;font-size:13px;cursor:pointer;">
                            <input type="checkbox" name="menus[]" value="{{ $child->id }}" class="menu-check" data-parent="{{ $parent->id }}" {{ in_array($child->id, old('menus', [])) ? 'checked' : '' }}>
                            @if($child->icon)<span class="material-symbols-outlined" style="font-size:14px;">{{ $child->icon }}</span>@endif
                            {{ $child->name }}
                        </label>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <div style="margin-top:24px;margin-bottom:48px;">
        <button type="submit" class="btn btn-primary">Create Role</button>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate slug from name
    document.getElementById('name').addEventListener('input', function() {
        var slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        document.getElementById('slug').value = slug;
    });

    // Select all permissions
    document.getElementById('select-all-perms').addEventListener('change', function() {
        document.querySelectorAll('.perm-check').forEach(function(cb) { cb.checked = this.checked; }.bind(this));
    });

    // Module toggle
    document.querySelectorAll('.module-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            var module = this.dataset.module;
            document.querySelectorAll('.perm-check[data-module="' + module + '"]').forEach(function(cb) { cb.checked = toggle.checked; });
        });
    });

    // Select all menus
    document.getElementById('select-all-menus').addEventListener('change', function() {
        document.querySelectorAll('.menu-check').forEach(function(cb) { cb.checked = this.checked; }.bind(this));
    });
});
</script>
@endsection
