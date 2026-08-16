@props(['compared', 'product', 'zone' => null, 'interactive' => false])

@php
    $offer = $compared->offer;
    $seller = $compared->seller();
    $quote = $compared->selectedQuote;
    // Inside the Livewire board the form is intercepted; everywhere else (and
    // with JavaScript off) it is an ordinary POST to the cart.
    $action = $interactive ? 'addToCart('.$offer->id.')' : null;
@endphp

<article class="offer-row {{ $compared->isBestTotal ? 'offer-row--best' : '' }}">
    <div class="offer-row__store">
        @if ($seller?->logo_path)
            <img src="{{ asset('storage/'.$seller->logo_path) }}" alt="" width="34" height="34"
                 class="offer-row__logo" loading="lazy" decoding="async">
        @else
            <span class="offer-row__logo" aria-hidden="true">{{ mb_substr($seller?->name ?? '؟', 0, 1) }}</span>
        @endif

        <div>
            <a href="{{ $seller?->url() }}" class="offer-row__name">{{ $seller?->name }}</a>
            <div class="offer-row__store-meta">
                <span>{{ $offer->condition->label() }}</span>
                @if ($offer->isLowStock())
                    <span>باقي {{ $offer->stock }} فقط</span>
                @endif
            </div>
            {{-- Freshness sits with the store, not in the action column: it is
                 information about this seller's data, and keeping it out of the
                 last column stops variable-length text from resizing buttons. --}}
            <div style="margin-block-start:4px">
                <x-offer.freshness :offer="$offer" />
            </div>
            <div class="offer-row__flags">
                @if ($compared->isBestTotal)
                    <x-ui.badge tone="success" icon="check">أقل إجمالي</x-ui.badge>
                @endif
                @if ($compared->isLowestPrice && ! $compared->isBestTotal)
                    <x-ui.badge tone="outline">أقل سعر منتج</x-ui.badge>
                @endif
                @if ($seller?->is_verified)
                    <x-ui.badge tone="info" icon="shield">متجر موثّق</x-ui.badge>
                @endif
            </div>
        </div>
    </div>

    <div class="offer-row__figures">
        <div class="offer-cell">
            <span class="offer-cell__label">سعر المنتج</span>
            <span class="offer-cell__value">
                {{ money($offer->price_cents) }}
                @if ($offer->hasDiscount())
                    <span class="price-was">{{ money($offer->compare_at_price_cents) }}</span>
                @endif
            </span>
        </div>

        <div class="offer-cell">
            <span class="offer-cell__label">التوصيل</span>
            <span class="offer-cell__value">
                @if ($quote === null)
                    <span class="muted small">لا يوصّل لمنطقتك</span>
                @elseif ($quote->isFree())
                    <span style="color:var(--good)">مجاني</span>
                @else
                    {{ money($quote->priceCents) }}
                @endif
            </span>
        </div>

        <div class="offer-cell">
            <span class="offer-cell__label">الإجمالي</span>
            <span class="offer-cell__value offer-row__total">
                {{ $compared->totalCents() !== null ? money($compared->totalCents()) : '—' }}
            </span>
        </div>

        <div class="offer-cell">
            <span class="offer-cell__label">موعد التسليم</span>
            <span class="offer-cell__value">
                @if ($quote)
                    {{ $quote->deliveryLabel() }}
                    <span class="dim xsmall" style="display:block;font-weight:500">{{ $quote->provider->name }}</span>
                @else
                    —
                @endif
            </span>
        </div>
    </div>

    <div class="offer-row__action">
        @if ($compared->deliversToZone())
            <form method="POST" action="{{ route('cart.store') }}" @if ($action) wire:submit="{{ $action }}" @endif>
                @csrf
                <input type="hidden" name="offer_id" value="{{ $offer->id }}">
                <button
                    type="submit"
                    class="btn {{ $compared->isBestTotal ? 'btn--primary' : '' }} btn--sm btn--block"
                    @if ($action)
                        wire:target="{{ $action }}"
                        wire:loading.attr="disabled"
                    @endif
                >
                    <x-ui.icon name="cart" :size="15" class="btn__icon" />
                    <span>أضف للسلة</span>
                </button>
            </form>
        @else
            <a href="{{ $seller?->url() }}" class="btn btn--sm btn--block">تفاصيل المتجر</a>
        @endif
    </div>
</article>
