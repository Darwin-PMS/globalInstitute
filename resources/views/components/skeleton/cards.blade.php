{{-- Card grid: courses, gallery, recordings. --}}
@props(['count' => 6, 'media' => true])
<div class="skeleton-grid" aria-hidden="true">
    @for ($i = 0; $i < (int) $count; $i++)
        <div class="skeleton-card">
            @if ($media)
                <div class="skeleton" style="height:130px;margin:-20px -20px 16px;border-radius:var(--border-radius) var(--border-radius) 0 0"></div>
            @endif
            <div class="skeleton skeleton-title skeleton-w-75"></div>
            <div class="skeleton skeleton-text skeleton-w-50" style="margin-top:10px"></div>
            <div class="skeleton-row" style="margin-top:16px">
                <div class="skeleton skeleton-badge"></div>
                <div class="skeleton skeleton-text skeleton-w-25" style="margin-left:auto"></div>
            </div>
        </div>
    @endfor
</div>
