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
    @if (empty($section['children']))
        {{-- Top-level menu that links to its own destination (e.g. Dashboard),
             rather than acting as a header for child items. --}}
        @include('partials.admin-sidebar-link', ['item' => $section, 'currentRoute' => $currentRoute, 'fullLoad' => true])
    @else
        @php
            $childRoutes = array_column($section['children'], 'route_name');
            $isActive = in_array($currentRoute, array_filter($childRoutes));
        @endphp

        <div class="nav-section{{ $isActive ? ' section-active' : '' }}">
            <div class="nav-section-label">{{ $section['name'] }}</div>

            @foreach ($section['children'] as $item)
                @include('partials.admin-sidebar-link', ['item' => $item, 'currentRoute' => $currentRoute])
            @endforeach
        </div>
    @endif
@endforeach
