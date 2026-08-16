@php
    $sorts = [
        'total' => 'الأقل إجمالًا',
        'price' => 'الأقل سعرًا',
        'fastest' => 'الأسرع توصيلًا',
    ];
@endphp

{{-- Polls only while the board is actually on screen and the tab is visible,
     so an open-but-ignored page costs nothing. --}}
<div wire:poll.45s.visible="refreshBoard">
    @if ($board->isEmpty())
        <x-ui.empty
            title="لا توجد عروض متاحة لهذا المنتج حاليًا"
            text="لسه مفيش متجر في {{ config('banha.city') }} عارض المنتج ده. اطلبه وهنتواصل مع المتاجر المحلية لتوفيره."
        >
            <x-slot:action>
                <a href="{{ route('product-requests.create', ['q' => $product->name]) }}" class="btn btn--primary">
                    اطلب هذا المنتج من متاجر {{ config('banha.city') }}
                </a>
            </x-slot:action>
        </x-ui.empty>
    @else
        @if ($addedLabel)
            <x-ui.alert tone="good" style="margin-block-end:12px">
                تمت إضافة "{{ $addedLabel }}" إلى السلة.
                <a href="{{ route('cart.index') }}" class="strong">افتح السلة</a>
            </x-ui.alert>
        @endif

        @if ($error)
            <x-ui.alert tone="bad" style="margin-block-end:12px">{{ $error }}</x-ui.alert>
        @endif

        <section class="offers" aria-labelledby="offers-heading">
            <div class="offers__toolbar">
                <h2 class="offers__count" id="offers-heading">
                    {{ $board->count() }} {{ $board->count() === 1 ? 'عرض متاح' : 'عروض متاحة' }}
                    @if ($zone)
                        <span class="muted small">— الأسعار شاملة التوصيل إلى {{ $zone->name }}</span>
                    @endif
                </h2>

                {{-- Real links, so sorting still works without JavaScript and
                     stays crawlable; Livewire intercepts the click when it can.
                     The href is built from the product URL, never from the
                     current request — during a Livewire update the "current
                     request" is the /livewire/update endpoint. --}}
                <div class="offers__sorts" role="group" aria-label="ترتيب العروض">
                    @foreach ($sorts as $key => $label)
                        <a
                            href="{{ $product->url() }}?sort={{ $key }}#offers-heading"
                            wire:click.prevent="sortBy('{{ $key }}')"
                            class="offers__sort"
                            rel="nofollow"
                            @if ($board->sort === $key) aria-current="true" @endif
                        >{{ $label }}</a>
                    @endforeach
                </div>
            </div>

            <div class="offers__head" aria-hidden="true">
                <span>المتجر</span>
                <span>سعر المنتج</span>
                <span>التوصيل</span>
                <span>الإجمالي</span>
                <span>موعد التسليم</span>
                <span></span>
            </div>

            <div wire:loading.class="offers--busy">
                @foreach ($board->offers as $compared)
                    <x-offer.row
                        :key="'offer-'.$compared->offer->id"
                        :compared="$compared"
                        :product="$product"
                        :zone="$zone"
                        interactive
                    />
                @endforeach
            </div>
        </section>

        @if ($board->cheapestPriceIsNotBestDeal() && $board->best())
            <div class="deal-note">
                <x-ui.icon name="info" :size="17" />
                <p>
                    أرخص سعر معروض للمنتج هو {{ money($board->lowestPrice()) }}،
                    لكن أقل إجمالي بعد التوصيل هو {{ money($board->cheapestTotal()) }}
                    من {{ $board->best()->seller()?->name }}.
                </p>
            </div>
        @endif
    @endif
</div>
