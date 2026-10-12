@php
    $checkedPerms = $checkedPerms ?? [];
@endphp

<style>
    .perm-tools { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-left: auto; }
    .perm-search { position: relative; display: flex; align-items: center; }
    .perm-search .material-symbols-outlined { position: absolute; left: 9px; font-size: 16px; color: var(--gray-400); pointer-events: none; }
    .perm-search .search-input { padding-left: 30px; width: 200px; }
    .perm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; align-items: start; }
    .perm-module { border: 1px solid var(--gray-200); border-radius: 8px; overflow: hidden; background: var(--color-surface); }
    .perm-module-header { display: flex; align-items: center; gap: 8px; padding: 9px 12px; background: var(--gray-50); border-bottom: 1px solid var(--gray-200); }
    .perm-module-name { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: var(--gray-600); flex: 1; }
    .perm-toggle { border: none; background: transparent; cursor: pointer; display: inline-flex; align-items: center; padding: 0; border-radius: 4px; color: var(--gray-500); }
    .perm-toggle:hover { color: var(--color-text); }
    .perm-toggle .material-symbols-outlined { font-size: 18px; transition: transform .2s ease; }
    .perm-module.collapsed .perm-toggle .material-symbols-outlined { transform: rotate(-90deg); }
    .perm-module.collapsed .perm-module-body { display: none; }
    .perm-module-body { padding: 6px 8px 10px; }
    .perm-item { display: flex; align-items: center; gap: 8px; padding: 5px 6px; font-size: 13px; cursor: pointer; border-radius: 6px; }
    .perm-item:hover { background: var(--gray-50); }
    .perm-item-slug { margin-left: auto; font-size: 11px; color: var(--gray-400); font-family: 'JetBrains Mono', monospace; }
    .perm-module-all { display: flex; align-items: center; gap: 4px; font-size: 12px; cursor: pointer; color: var(--gray-500); white-space: nowrap; }
    .perm-count { font-size: 11px; }
    .perm-item.is-hidden, .perm-module.is-hidden { display: none; }
    .perm-empty { display: none; padding: 24px; text-align: center; color: var(--gray-400); font-size: 13px; }
    .perm-empty.visible { display: block; }
</style>

<div class="card" id="permissions-card" style="margin-top:24px;">
    <div class="card-header" style="justify-content:space-between;">
        <h3>Permissions</h3>
        <div class="perm-tools" data-no-loading>
            <div class="perm-search">
                <span class="material-symbols-outlined">search</span>
                <input type="text" id="perm-search" class="search-input" placeholder="Filter permissions...">
            </div>
            <button type="button" class="icon-btn" id="perm-expand-all" title="Expand all" aria-label="Expand all">
                <span class="material-symbols-outlined" style="font-size:18px;">unfold_more</span>
            </button>
            <button type="button" class="icon-btn" id="perm-collapse-all" title="Collapse all" aria-label="Collapse all">
                <span class="material-symbols-outlined" style="font-size:18px;">unfold_less</span>
            </button>
            <label class="perm-module-all">
                <input type="checkbox" id="select-all-perms"> Select All
            </label>
        </div>
    </div>
    <div class="card-body perm-grid">
        @foreach($groupedPermissions as $module => $permissions)
            @php
                $moduleChecked = $permissions->filter(fn ($p) => in_array($p->slug, $checkedPerms))->count();
                $moduleTotal = $permissions->count();
            @endphp
            <div class="perm-module" data-module="{{ $module }}">
                <div class="perm-module-header">
                    <button type="button" class="perm-toggle" aria-expanded="true" title="Toggle {{ $module }}">
                        <span class="material-symbols-outlined">expand_more</span>
                    </button>
                    <strong class="perm-module-name">{{ $module }}</strong>
                    <span class="badge badge-secondary perm-count">{{ $moduleChecked }}/{{ $moduleTotal }}</span>
                    <label class="perm-module-all">
                        <input type="checkbox" class="module-toggle" data-module="{{ $module }}" {{ $moduleTotal > 0 && $moduleChecked === $moduleTotal ? 'checked' : '' }}>
                        All
                    </label>
                </div>
                <div class="perm-module-body">
                    @foreach($permissions as $perm)
                        <label class="perm-item">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->slug }}" class="perm-check" data-module="{{ $module }}" data-label="{{ $perm->action }} {{ $perm->slug }}" {{ in_array($perm->slug, $checkedPerms) ? 'checked' : '' }}>
                            <span>{{ $perm->action }}</span>
                            <span class="perm-item-slug">{{ $perm->slug }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
        <div class="perm-empty" id="perm-empty">No permissions match your filter.</div>
    </div>
</div>
