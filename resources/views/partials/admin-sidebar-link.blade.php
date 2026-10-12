{{--
    Renders a single admin sidebar link.

    Expects:
      $item         menu array from SidebarHelper::buildTree()
      $currentRoute current route name (for the active state)
      $fullLoad     true for links that leave the Blade admin (e.g. Dashboard,
                    which loads the separate SPA shell) so spa.js leaves them
                    alone and the browser performs a real navigation.
--}}
@php
    $url = '#';
    if (!empty($item['route_name'])) {
        $url = route($item['route_name']);
    } elseif (!empty($item['url'])) {
        $url = preg_match('#^https?://#', $item['url']) ? $item['url'] : url($item['url']);
    } elseif ($item['is_spa'] ?? false) {
        $url = '#/' . $item['slug'];
    }

    $itemActive = ($currentRoute ?? '') === ($item['route_name'] ?? '');
    $linkClass = 'nav-link' . ($itemActive ? ' active' : '');
    $fullLoad = $fullLoad ?? false;
@endphp

<a href="{{ $url }}"
   class="{{ $linkClass }}"
   data-route="{{ $item['slug'] }}"
   @if ($fullLoad) data-ajax="false" @endif
   @if (($item['target'] ?? '_self') === '_blank') target="_blank" rel="noopener" @endif
   @if ($itemActive) aria-current="page" @endif>
    @if (!empty($item['icon']))
        <span class="material-symbols-outlined">{{ $item['icon'] }}</span>
    @endif
    <span class="nav-text">{{ $item['name'] }}</span>
</a>
