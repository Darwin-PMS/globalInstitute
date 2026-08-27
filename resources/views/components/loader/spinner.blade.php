{{-- Inline spinner. Sizes: sm | (default) | lg --}}
@props(['size' => '', 'light' => false, 'label' => null])
<span class="spinner {{ $size ? 'spinner-'.$size : '' }} {{ $light ? 'spinner-light' : '' }}"
      role="status" @if($label) aria-label="{{ $label }}" @else aria-hidden="true" @endif></span>
