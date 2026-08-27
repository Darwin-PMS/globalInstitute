{{-- Stat tiles. Matches .stats-bar so nothing shifts when the real figures land. --}}
@props(['count' => 4])
<div class="skeleton-stats" aria-hidden="true">
    @for ($i = 0; $i < (int) $count; $i++)
        <div class="skeleton-card skeleton-card-sm">
            <div class="skeleton-row">
                <div class="skeleton skeleton-thumb"></div>
                <div class="skeleton-stack">
                    <div class="skeleton skeleton-title skeleton-w-50"></div>
                    <div class="skeleton skeleton-text skeleton-w-75" style="margin-top:8px"></div>
                </div>
            </div>
        </div>
    @endfor
</div>
