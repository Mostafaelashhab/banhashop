@props(['seller', 'showZones' => false])

<article class="seller-card">
    @if ($seller->logo_path)
        <img src="{{ asset('storage/'.$seller->logo_path) }}" alt="" width="46" height="46"
             class="seller-card__logo" loading="lazy" decoding="async">
    @else
        <span class="seller-card__logo" aria-hidden="true">{{ mb_substr($seller->name, 0, 1) }}</span>
    @endif

    <div class="seller-card__body">
        <a href="{{ $seller->url() }}" class="seller-card__name">{{ $seller->name }}</a>

        {{-- The badge shares the meta line rather than claiming one of its own:
             on its own line it made every verified store's card a line taller
             than an unverified one, and a grid row sizes to its tallest card. --}}
        <div class="seller-card__meta">
            <span class="num">{{ $seller->active_offers_count }} عرض متاح</span>

            @if ($seller->is_verified)
                <x-ui.badge tone="info" icon="shield">متجر موثّق</x-ui.badge>
            @endif
        </div>

        @if ($showZones && $seller->relationLoaded('zones') && $seller->zones->isNotEmpty())
            <p class="xsmall muted">
                يوصّل إلى: {{ $seller->zones->pluck('name')->join('، ') }}
            </p>
        @endif
    </div>
</article>
