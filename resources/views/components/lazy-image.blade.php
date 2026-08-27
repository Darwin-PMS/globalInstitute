@props([
    'src' => null,
    'alt' => '',
    'width' => null,
    'height' => null,
    'class' => '',
    'ratio' => null,
    'eager' => false,
    'avatar' => false,
    'placeholder' => true,
])

@php
    $imgClass = 'lazy-img' . ($avatar ? ' avatar' : '') . ($class ? ' ' . $class : '');
    $aspectStyle = $ratio ? 'aspect-ratio:' . $ratio . ';' : '';
    $dimStyle = '';
    if ($width && $height) {
        $dimStyle = 'width:' . $width . 'px;height:' . $height . 'px;';
    } elseif ($width) {
        $dimStyle = 'width:' . $width . 'px;';
    }
@endphp

@if($src)
    <img
        @if($eager)
            src="{{ $src }}"
            loading="eager"
            fetchpriority="high"
            decoding="sync"
        @else
            src="{{ $placeholder ? 'data:image/svg+xml,' . urlencode('<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\' fill=\'%23f1f3f5\'><rect width=\'400\' height=\'300\'/><text x=\'200\' y=\'150\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23adb5bd\' font-family=\'sans-serif\' font-size=\'14\'>Loading...</text></svg>') : $src }}"
            data-src="{{ $src }}"
            loading="lazy"
            decoding="async"
        @endif
        alt="{{ $alt }}"
        class="{{ $imgClass }}"
        @if($width) width="{{ $width }}" @endif
        @if($height) height="{{ $height }}" @endif
        @if($dimStyle || $aspectStyle) style="{{ $dimStyle }}{{ $aspectStyle }}" @endif
    >
@else
    <div class="lazy-placeholder {{ $avatar ? 'avatar' : '' }}" @if($dimStyle || $aspectStyle) style="{{ $dimStyle }}{{ $aspectStyle }}background:var(--gray-100);display:flex;align-items:center;justify-content:center;" @endif>
        <span class="material-symbols-outlined" style="font-size:24px;color:var(--gray-400);">image</span>
    </div>
@endif
