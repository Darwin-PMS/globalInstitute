@php
    $menuUser = auth()->user();
    $menuName = $menuUser->name ?? 'Admin';
    $menuEmail = $menuUser->email ?? '';
    $menuInitial = strtoupper(substr($menuName, 0, 1));
@endphp
<div class="header-user action-menu" id="user-menu">
    <button type="button" class="action-menu-trigger header-user-trigger" aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
        <span class="header-user-avatar">{{ $menuInitial }}</span>
        <span class="header-user-name">{{ $menuName }}</span>
        <span class="material-symbols-outlined">expand_more</span>
    </button>
    <div class="action-dropdown user-dropdown" role="menu">
        <div class="user-dropdown-head">
            <span class="header-user-avatar user-dropdown-avatar">{{ $menuInitial }}</span>
            <span class="user-dropdown-meta">
                <span class="user-dropdown-name">{{ $menuName }}</span>
                @if($menuEmail)
                    <span class="user-dropdown-email">{{ $menuEmail }}</span>
                @endif
            </span>
        </div>
        <div class="action-dropdown-divider"></div>
        <a href="{{ route('admin.settings.edit') }}" class="action-dropdown-item" role="menuitem">
            <span class="material-symbols-outlined">settings</span> Settings
        </a>
        <a href="/" target="_blank" rel="noopener" class="action-dropdown-item" role="menuitem">
            <span class="material-symbols-outlined">open_in_new</span> View website
        </a>
        <div class="action-dropdown-divider"></div>
        <form method="POST" action="{{ route('admin.logout') }}" class="user-dropdown-form">
            @csrf
            <button type="submit" class="action-dropdown-item action-dropdown-danger" role="menuitem">
                <span class="material-symbols-outlined">logout</span> Sign out
            </button>
        </form>
    </div>
</div>
