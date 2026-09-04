@extends('layouts.admin')

@section('title', 'Permissions: ' . $user->name)

@section('content')
<div style="display:grid;grid-template-columns:1fr 300px;gap:24px;">
    <div>
        <form action="{{ route('admin.users.permissions.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Effective Permissions Summary --}}
            <div class="card" style="margin-bottom:24px;">
                <div class="card-header"><h3>Effective Permissions</h3></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;font-size:13px;">
                        @php
                            $effective = $user->getEffectivePermissions();
                        @endphp
                        @forelse(array_slice($effective, 0, 20) as $perm)
                            <div style="display:flex;align-items:center;gap:6px;">
                                <span class="material-symbols-outlined" style="font-size:14px;color:var(--color-tertiary, #2e7d32);">check_circle</span>
                                {{ $perm }}
                            </div>
                        @empty
                            <p style="color:var(--gray-400);">No effective permissions.</p>
                        @endforelse
                        @if(count($effective) > 20)
                            <div style="color:var(--gray-400);">...and {{ count($effective) - 20 }} more</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Permission Overrides --}}
            <div class="card">
                <div class="card-header">
                    <h3>Permission Overrides</h3>
                    <label style="font-size:13px;font-weight:normal;display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" id="select-all-perms"> Select All
                    </label>
                </div>
                <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
                    @foreach($groupedPermissions as $module => $permissions)
                        <div style="border:1px solid var(--gray-200);border-radius:8px;padding:12px;">
                            <strong style="font-size:13px;text-transform:uppercase;color:var(--gray-600);display:block;margin-bottom:8px;">{{ $module }}</strong>
                            @foreach($permissions as $perm)
                                @php
                                    $roleHas = in_array($perm->slug, $rolePermSlugs);
                                    $directPerm = $directPerms->get($perm->slug);
                                    $directType = $directPerm ? $directPerm->pivot->type : null;
                                @endphp
                                <div style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:13px;">
                                    <span style="width:100px;color:var(--gray-500);">{{ $perm->action }}</span>
                                    @if($roleHas)
                                        <span style="font-size:11px;color:var(--gray-400);width:50px;">Role: ✓</span>
                                    @else
                                        <span style="font-size:11px;color:var(--gray-300);width:50px;">Role: —</span>
                                    @endif
                                    <select name="overrides[{{ $perm->slug }}][type]" class="form-control" style="width:100px;padding:4px 8px;font-size:12px;">
                                        <option value="">Inherit</option>
                                        <option value="allow" {{ $directType === 'allow' ? 'selected' : '' }}>Allow</option>
                                        <option value="deny" {{ $directType === 'deny' ? 'selected' : '' }}>Deny</option>
                                    </select>
                                    <input type="hidden" name="overrides[{{ $perm->slug }}][slug]" value="{{ $perm->slug }}">
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <div style="margin-top:24px;margin-bottom:48px;">
                <button type="submit" class="btn btn-primary">Save Permission Overrides</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline" style="margin-left:8px;">Back to Users</a>
            </div>
        </form>
    </div>

    {{-- Sidebar: User Info --}}
    <div>
        <div class="card">
            <div class="card-body" style="text-align:center;">
                <div style="width:64px;height:64px;border-radius:50%;background:var(--gray-100);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                    <span class="material-symbols-outlined" style="font-size:32px;">person</span>
                </div>
                <div style="font-weight:600;font-size:16px;">{{ $user->name }}</div>
                <div style="color:var(--gray-400);font-size:13px;">{{ $user->email }}</div>

                <div style="margin-top:16px;text-align:left;">
                    <div style="font-size:12px;font-weight:600;color:var(--gray-500);margin-bottom:6px;">ROLES</div>
                    @forelse($user->roles as $role)
                        <span class="badge badge-primary" style="font-size:11px;">{{ $role->name }}</span>
                    @empty
                        <span style="color:var(--gray-400);font-size:13px;">No role assigned</span>
                    @endforelse
                </div>

                <div style="margin-top:16px;text-align:left;">
                    <div style="font-size:12px;font-weight:600;color:var(--gray-500);margin-bottom:6px;">OVERRIDE COUNT</div>
                    <span style="font-weight:600;">{{ $user->directPermissions()->count() }}</span>
                </div>

                <div style="margin-top:16px;text-align:left;">
                    <div style="font-size:12px;font-weight:600;color:var(--gray-500);margin-bottom:6px;">EFFECTIVE PERMISSIONS</div>
                    <span style="font-weight:600;">{{ count($effective) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('select-all-perms').addEventListener('change', function() {
        document.querySelectorAll('select[name*="overrides"]').forEach(function(sel) {
            sel.value = this.checked ? 'allow' : '';
        }.bind(this));
    });
});
</script>
@endsection
