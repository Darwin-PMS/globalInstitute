@props(['bars' => 8])
<div class="skeleton-card" aria-hidden="true">
    <div class="skeleton skeleton-title skeleton-w-33"></div>
    <div class="skeleton-chart">
        @for ($i = 0; $i < (int) $bars; $i++)
            <div class="skeleton" style="height:{{ 35 + (($i * 17) % 60) }}%"></div>
        @endfor
    </div>
</div>
