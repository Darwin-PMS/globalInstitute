@props(['width' => '100', 'height' => null, 'class' => ''])
<div class="skeleton skeleton-text skeleton-w-{{ $width }} {{ $class }}"
     @if($height) style="height: {{ $height }}" @endif></div>
