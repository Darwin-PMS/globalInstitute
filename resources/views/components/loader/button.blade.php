{{--
    A submit button that shows its own busy state.

    <x-loader.button loading-text="Saving…">Save course</x-loader.button>

    The auto-wiring in blade-loading.js handles the busy state and blocks the
    second click, so no page needs its own script for this.
--}}
@props(['loadingText' => null, 'type' => 'submit', 'variant' => 'primary', 'size' => 'sm'])
<button type="{{ $type }}"
        {{ $attributes->merge(['class' => 'btn btn-'.$variant.($size ? ' btn-'.$size : '')]) }}
        @if($loadingText) data-loading-text="{{ $loadingText }}" @endif>
    {{ $slot }}
</button>
