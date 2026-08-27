{{--
    Section overlay. Put it inside a container that also has .is-loading, or let
    Loading.showLoader() add it at runtime.

    Pass label="" for a spinner with no caption. (Blade props fall back to their
    default when given null, so an empty string is how you suppress it.)
--}}
@props(['label' => 'Loading…'])
<div class="section-loader" role="status">
    <span class="spinner" aria-hidden="true"></span>
    @if($label)<span class="section-loader-label">{{ $label }}</span>@endif
</div>
