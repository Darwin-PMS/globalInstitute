@php
    $checkedMenus = $checkedMenus ?? [];
@endphp

<style>
    .menu-tools { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-left: auto; }
    .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; align-items: start; }
    .menu-card { border: 1px solid var(--gray-200); border-radius: 8px; overflow: hidden; background: var(--color-surface); }
    .menu-card-header { display: flex; align-items: center; gap: 8px; padding: 9px 12px; background: var(--gray-50); border-bottom: 1px solid var(--gray-200); }
    .menu-card-header .material-symbols-outlined.menu-icon { font-size: 16px; color: var(--gray-500); }
    .menu-card-name { font-weight: 600; font-size: 14px; flex: 1; }
    .menu-toggle { border: none; background: transparent; cursor: pointer; display: inline-flex; align-items: center; padding: 0; border-radius: 4px; color: var(--gray-500); }
    .menu-toggle:hover { color: var(--color-text); }
    .menu-toggle .material-symbols-outlined { font-size: 18px; transition: transform .2s ease; }
    .menu-card.collapsed .menu-toggle .material-symbols-outlined { transform: rotate(-90deg); }
    .menu-card.collapsed .menu-card-body { display: none; }
    .menu-card-body { padding: 6px 8px 8px; }
    .menu-child { display: flex; align-items: center; gap: 8px; padding: 5px 6px 5px 20px; font-size: 13px; cursor: pointer; border-radius: 6px; }
    .menu-child:hover { background: var(--gray-50); }
    .menu-child .material-symbols-outlined { font-size: 14px; color: var(--gray-400); }
    .menu-child-item.is-hidden, .menu-card.is-hidden, .menu-empty.is-hidden { display: none; }
    .menu-empty { display: none; padding: 24px; text-align: center; color: var(--gray-400); font-size: 13px; }
    .menu-empty.visible { display: block; }
    .menu-count { font-size: 11px; }
    .menu-search { position: relative; display: flex; align-items: center; }
    .menu-search .material-symbols-outlined { position: absolute; left: 9px; font-size: 16px; color: var(--gray-400); pointer-events: none; }
    .menu-search .search-input { padding-left: 30px; width: 180px; }
</style>

<div class="card" id="menus-card" style="margin-top:24px;">
    <div class="card-header" style="justify-content:space-between;">
        <h3>Menu Access</h3>
        <div class="menu-tools" data-no-loading>
            <div class="menu-search">
                <span class="material-symbols-outlined">search</span>
                <input type="text" id="menu-search" class="search-input" placeholder="Filter menus...">
            </div>
            <button type="button" class="icon-btn" id="menu-expand-all" title="Expand all" aria-label="Expand all">
                <span class="material-symbols-outlined" style="font-size:18px;">unfold_more</span>
            </button>
            <button type="button" class="icon-btn" id="menu-collapse-all" title="Collapse all" aria-label="Collapse all">
                <span class="material-symbols-outlined" style="font-size:18px;">unfold_less</span>
            </button>
            <label class="perm-module-all">
                <input type="checkbox" id="select-all-menus"> Select All
            </label>
        </div>
    </div>
    <div class="card-body menu-grid">
        @foreach($menus->whereNull('parent_id') as $parent)
            @php
                $children = $menus->where('parent_id', $parent->id);
                $parentChecked = in_array($parent->id, $checkedMenus);
                $childrenChecked = $children->filter(fn ($c) => in_array($c->id, $checkedMenus))->count();
                $childrenTotal = $children->count();
            @endphp
            <div class="menu-card" data-menu-card>
                <div class="menu-card-header">
                    <button type="button" class="menu-toggle" aria-expanded="true" title="Toggle {{ $parent->name }}">
                        <span class="material-symbols-outlined">expand_more</span>
                    </button>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" name="menus[]" value="{{ $parent->id }}" class="menu-check menu-check-parent" {{ $parentChecked ? 'checked' : '' }}>
                    </label>
                    @if($parent->icon)<span class="material-symbols-outlined menu-icon">{{ $parent->icon }}</span>@endif
                    <span class="menu-card-name">{{ $parent->name }}</span>
                    @if($childrenTotal > 0)
                        <span class="badge badge-secondary menu-count">{{ $childrenChecked }}/{{ $childrenTotal }}</span>
                    @endif
                </div>
                @if($childrenTotal > 0)
                <div class="menu-card-body">
                    @foreach($children as $child)
                        <label class="menu-child menu-child-item" data-label="{{ $child->name }} {{ $child->slug }}">
                            <input type="checkbox" name="menus[]" value="{{ $child->id }}" class="menu-check menu-check-child" data-parent="{{ $parent->id }}" {{ in_array($child->id, $checkedMenus) ? 'checked' : '' }}>
                            @if($child->icon)<span class="material-symbols-outlined">{{ $child->icon }}</span>@endif
                            <span>{{ $child->name }}</span>
                        </label>
                    @endforeach
                </div>
                @endif
            </div>
        @endforeach
        <div class="menu-empty" id="menu-empty">No menus match your filter.</div>
    </div>
</div>
