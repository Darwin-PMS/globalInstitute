{{--
    Dynamic Admin Sidebar
    Reads from admin_menus table, filtered by user permissions.

    Usage: @include('partials.admin-sidebar')
    Or:    @include('partials.admin-sidebar', ['compact' => true])
--}}
@php
    use App\Helpers\SidebarHelper;

    $sidebarTree = SidebarHelper::forUser(auth()->user());
    $currentRoute = request()->route()->getName() ?? '';
    $compactMode = $compact ?? false;
@endphp

@foreach ($sidebarTree as $section)
    @php
        // Section header (parent with no route)
        $isActive = false;
        if (!empty($section['children'])) {
            $childRoutes = array_column($section['children'], 'route_name');
            $isActive = in_array($currentRoute, array_filter($childRoutes));
        }
    @endphp

    <div class="nav-section{{ $isActive ? ' section-active' : '' }}">
        <div class="nav-section-label">{{ $section['name'] }}</div>

        @if (!empty($section['children']))
            @foreach ($section['children'] as $item)
                @php
                    $url = '#';
                    if (!empty($item['route_name'])) {
                        $url = route($item['route_name']);
                    } elseif (!empty($item['url'])) {
                        $url = $item['url'];
                    } elseif ($item['is_spa'] ?? false) {
                        $url = '#/' . $item['slug'];
                    }

                    $itemActive = $currentRoute === ($item['route_name'] ?? '');
                    $linkClass = 'nav-link' . ($itemActive ? ' active' : '');
                @endphp

                <a href="{{ $url }}"
                   class="{{ $linkClass }}"
                   data-route="{{ $item['slug'] }}"
                   @if($item['target'] === '_blank') target="_blank" rel="noopener" @endif
                   @if($itemActive) aria-current="page" @endif>
                    @if (!empty($item['icon']))
                        <span class="material-symbols-outlined">{{ $item['icon'] }}</span>
                    @endif
                    <span class="nav-text">{{ $item['name'] }}</span>
                </a>
            @endforeach
        @endif
    </div>
@endforeach
