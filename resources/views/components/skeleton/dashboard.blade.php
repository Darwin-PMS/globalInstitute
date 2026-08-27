{{-- Whole-dashboard placeholder: hero, tiles, two panels. --}}
<div class="skeleton-group" style="gap:24px" aria-hidden="true">
    <div class="skeleton" style="height:120px;border-radius:var(--border-radius-lg)"></div>
    <x-skeleton.stats :count="4" />
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <x-skeleton.list :rows="3" />
        <x-skeleton.list :rows="3" />
    </div>
</div>
