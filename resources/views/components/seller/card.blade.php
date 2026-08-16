@props(['seller', 'showZones' => false])

<article class="seller-card">
    @if ($seller->logo_path)
        <img src="{{ asset('storage/'.$seller->logo_path) }}" alt="" width="46" height="46"
             class="seller-card__logo" loading="lazy" decoding="async">
    @else
        <span class="seller-card__logo" aria-hidden="true">{{ mb_substr($seller->name, 0, 1) }}</span>
    @endif

    <div style="min-width:0">
        <a href="{{ $seller->url() }}" class="seller-card__name">{{ $seller->name }}</a>

        <div class="seller-card__meta num">
            {{ $seller->active_offers_count }} عرض متاح
        </div>

        @if ($seller->is_verified)
            <div style="margin-block-start:6px">
                <x-ui.badge tone="info" icon="shield">متجر موثّق</x-ui.badge>
            </div>
        @endif

        @if ($showZones && $seller->relationLoaded('zones') && $seller->zones->isNotEmpty())
            <p class="xsmall muted" style="margin-block-start:6px">
                يوصّل إلى: {{ $seller->zones->pluck('name')->join('، ') }}
            </p>
        @endif
    </div>
</article>
