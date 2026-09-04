@extends('layouts.admin')

@section('title', 'Edit Role: ' . $role->name)

@section('content')
<form action="{{ route('admin.roles.update', $role) }}" method="POST">
    @csrf
    @method('PUT')
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
        {{-- Left: Basic Info --}}
        <div>
            <div class="card">
                <div class="card-header"><h3>Role Information</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Role Name <span style="color:red;">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $role->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Slug</label>
                        <input type="text" class="form-control" value="{{ $role->slug }}" disabled>
                        <small style="color:var(--gray-400);">Slugs cannot be changed after creation.</small>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $role->description) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="is_active" value="1" {{ $role->is_active ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top:24px;">
                <div class="card-header"><h3>Users with this Role ({{ $role->users_count }})</h3></div>
                <div class="card-body">
                    @forelse($role->users->take(10) as $user)
                        <div style="display:flex;align-items:center;gap:8px;padding:6px 0;">
                            <div style="width:28px;height:28px;border-radius:50%;background:var(--gray-100);display:flex;align-items:center;justify-content:center;">
                                <span class="material-symbols-outlined" style="font-size:14px;">person</span>
                            </div>
                            <span style="font-size:14px;">{{ $user->name }}</span>
                            <span style="font-size:12px;color:var(--gray-400);">{{ $user->email }}</span>
                        </div>
                    @empty
                        <p style="color:var(--gray-400);font-size:14px;">No users assigned to this role.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right: Summary --}}
        <div>
            <div class="card">
                <div class="card-header"><h3>Summary</h3></div>
                <div class="card-body">
                    <div id="perm-summary" style="font-size:14px;color:var(--gray-600);"></div>
                    <div style="margin-top:24px;">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
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
                            <input type="checkbox" name="permissions[]" value="{{ $perm->slug }}" class="perm-check" data-module="{{ $module }}" {{ in_array($perm->slug, $assignedPermSlugs) ? 'checked' : '' }}>
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
                        <input type="checkbox" name="menus[]" value="{{ $parent->id }}" class="menu-check" {{ in_array($parent->id, $assignedMenuIds) ? 'checked' : '' }}>
                        @if($parent->icon)<span class="material-symbols-outlined" style="font-size:16px;">{{ $parent->icon }}</span>@endif
                        {{ $parent->name }}
                    </label>
                    @foreach($menus->where('parent_id', $parent->id) as $child)
                        <label style="display:flex;align-items:center;gap:6px;padding:2px 0 2px 24px;font-size:13px;cursor:pointer;">
                            <input type="checkbox" name="menus[]" value="{{ $child->id }}" class="menu-check" data-parent="{{ $parent->id }}" {{ in_array($child->id, $assignedMenuIds) ? 'checked' : '' }}>
                            @if($child->icon)<span class="material-symbols-outlined" style="font-size:14px;">{{ $child->icon }}</span>@endif
                            {{ $child->name }}
                        </label>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <div style="margin-top:24px;margin-bottom:48px;">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all permissions
    document.getElementById('select-all-perms').addEventListener('change', function() {
        document.querySelectorAll('.perm-check').forEach(function(cb) { cb.checked = this.checked; }.bind(this));
        updateSummary();
    });

    // Module toggle
    document.querySelectorAll('.module-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            var module = this.dataset.module;
            document.querySelectorAll('.perm-check[data-module="' + module + '"]').forEach(function(cb) { cb.checked = toggle.checked; });
            updateSummary();
        });
    });

    // Select all menus
    document.getElementById('select-all-menus').addEventListener('change', function() {
        document.querySelectorAll('.menu-check').forEach(function(cb) { cb.checked = this.checked; }.bind(this));
    });

    // Update summary on change
    document.querySelectorAll('.perm-check').forEach(function(cb) {
        cb.addEventListener('change', updateSummary);
    });

    function updateSummary() {
        var checked = document.querySelectorAll('.perm-check:checked').length;
        var total = document.querySelectorAll('.perm-check').length;
        var menusChecked = document.querySelectorAll('.menu-check:checked').length;
        var menusTotal = document.querySelectorAll('.menu-check').length;
        document.getElementById('perm-summary').innerHTML =
            '<div style="margin-bottom:8px;"><strong>' + checked + '</strong> of ' + total + ' permissions enabled</div>' +
            '<div><strong>' + menusChecked + '</strong> of ' + menusTotal + ' menus enabled</div>';
    }

    updateSummary();
});
</script>
@endsection
