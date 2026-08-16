<x-layouts.app>
    <nav class="breadcrumbs" aria-label="مسار التصفح">
        <ol>
            <li><a href="{{ route('home') }}">الرئيسية</a></li>
            <li><a href="{{ route('stores.index') }}">المتاجر</a></li>
            <li><span aria-current="page">{{ $seller->name }}</span></li>
        </ol>
    </nav>

    <header class="panel" style="margin-block-end:22px">
        <div class="panel__body">
            <div class="row row--top" style="gap:14px">
                @if ($seller->logo_path)
                    <img src="{{ asset('storage/'.$seller->logo_path) }}" alt="" width="56" height="56"
                         class="seller-card__logo" style="width:56px;height:56px">
                @else
                    <span class="seller-card__logo" style="width:56px;height:56px;font-size:1.2rem" aria-hidden="true">
                        {{ mb_substr($seller->name, 0, 1) }}
                    </span>
                @endif

                <div style="min-width:0;flex:1">
                    <h1>{{ $seller->name }}</h1>

                    <div class="row row--wrap small muted" style="margin-block-start:6px;gap:12px">
                        <span class="num">{{ $seller->active_offers_count }} عرض متاح</span>
                        @if ($seller->primaryLocation?->address_line)
                            <span class="row" style="gap:4px">
                                <x-ui.icon name="map-pin" :size="14" />
                                {{ $seller->primaryLocation->address_line }}
                            </span>
                        @endif
                        @if ($seller->acceptanceRate() !== null)
                            <span class="num">نسبة قبول الطلبات {{ $seller->acceptanceRate() }}%</span>
                        @endif
                    </div>

                    @if ($seller->is_verified)
                        <div style="margin-block-start:8px">
                            <x-ui.badge tone="info" icon="shield">متجر موثّق</x-ui.badge>
                        </div>
                    @endif

                    @if ($seller->description)
                        <p class="small muted" style="margin-block-start:10px;max-width:70ch">{{ $seller->description }}</p>
                    @endif
                </div>
            </div>

            {{-- Delivery terms up front: this is what a customer wants to know
                 before browsing a store's catalog. --}}
            @if ($zone)
                <div style="margin-block-start:16px;padding-block-start:14px;border-block-start:1px solid var(--line-2)">
                    <p class="field__label">التوصيل إلى {{ $zone->name }}</p>

                    @if ($quotes->isEmpty())
                        <p class="small muted">هذا المتجر لا يوصّل إلى {{ $zone->name }} حاليًا.</p>
                    @else
                        <ul class="row row--wrap" style="gap:8px">
                            @foreach ($quotes as $quote)
                                <li class="badge badge--outline">
                                    {{ $quote->provider->name }} —
                                    {{ $quote->isFree() ? 'مجاني' : money($quote->priceCents) }} —
                                    {{ $quote->deliveryLabel() }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        </div>
    </header>

    <section>
        <div class="section__head">
            <h2>منتجات المتجر</h2>
            <span class="small muted num">{{ $products->total() }} منتج</span>
        </div>

        @if ($products->isEmpty())
            <x-ui.empty title="لا توجد منتجات معروضة حاليًا" text="المتجر ده لسه مضافش عروض نشطة." />
        @else
            <x-product.grid :products="$products" />
            {{ $products->links() }}
        @endif
    </section>
</x-layouts.app>
