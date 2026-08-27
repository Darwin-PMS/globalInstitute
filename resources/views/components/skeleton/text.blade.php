@props(['lines' => 3])
@php $widths = ['100', '75', '66', '50']; @endphp
<div class="skeleton-group" aria-hidden="true">
    @for ($i = 0; $i < (int) $lines; $i++)
        <div class="skeleton skeleton-line skeleton-w-{{ $widths[$i % 4] }}"></div>
    @endfor
</div>
