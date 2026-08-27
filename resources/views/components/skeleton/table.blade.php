{{-- Data table placeholder, aligned to .admin-table cell padding. --}}
@props(['rows' => 6, 'cols' => 5, 'header' => true])
<div class="card" aria-hidden="true">
    <div class="card-body-flush">
        @if ($header)
            <div class="skeleton-table-row" style="background:var(--gray-50)">
                @for ($c = 0; $c < (int) $cols; $c++)
                    <div class="skeleton skeleton-text {{ $c === 0 ? 'skeleton-w-15' : 'skeleton-w-50' }}"
                         style="flex:{{ $c === 1 ? 2 : 1 }}"></div>
                @endfor
            </div>
        @endif

        @for ($r = 0; $r < (int) $rows; $r++)
            <div class="skeleton-table-row">
                <div class="skeleton skeleton-thumb"></div>
                <div class="skeleton-stack">
                    <div class="skeleton skeleton-text skeleton-w-50"></div>
                    <div class="skeleton skeleton-text skeleton-w-33" style="margin-top:7px;height:9px"></div>
                </div>
                @for ($c = 0; $c < max(0, (int) $cols - 3); $c++)
                    <div class="skeleton skeleton-text skeleton-w-100" style="flex:1"></div>
                @endfor
                <div class="skeleton skeleton-badge"></div>
            </div>
        @endfor
    </div>
</div>
