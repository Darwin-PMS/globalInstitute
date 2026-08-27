{{-- Form placeholder on the design system's two-column grid. --}}
@props(['fields' => 6, 'columns' => 2, 'actions' => true])
<div class="skeleton-card" aria-hidden="true">
    <div class="{{ (int) $columns === 2 ? 'form-grid-2' : '' }}">
        @for ($i = 0; $i < (int) $fields; $i++)
            <div class="form-group">
                <div class="skeleton skeleton-text skeleton-w-33" style="margin-bottom:8px"></div>
                <div class="skeleton skeleton-input skeleton-w-100"></div>
            </div>
        @endfor
    </div>
    @if ($actions)
        <div class="skeleton-row" style="justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid var(--color-border)">
            <div class="skeleton skeleton-btn" style="width:90px"></div>
            <div class="skeleton skeleton-btn"></div>
        </div>
    @endif
</div>
