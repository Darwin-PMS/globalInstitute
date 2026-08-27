{{-- Row list: enquiries, submissions, recent activity. --}}
@props(['rows' => 5, 'avatar' => true])
<div class="skeleton-group" aria-hidden="true">
    @for ($i = 0; $i < (int) $rows; $i++)
        <div class="skeleton-card skeleton-card-sm">
            <div class="skeleton-row">
                @if ($avatar)<div class="skeleton skeleton-avatar"></div>@endif
                <div class="skeleton-stack">
                    <div class="skeleton skeleton-text skeleton-w-50"></div>
                    <div class="skeleton skeleton-text skeleton-w-33" style="margin-top:7px;height:9px"></div>
                </div>
                <div class="skeleton skeleton-badge"></div>
            </div>
        </div>
    @endfor
</div>
