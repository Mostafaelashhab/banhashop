<x-layouts.app>
    <x-layout.breadcrumbs :trail="$trail" />

    <div class="product-hero">
        <div>
            <div class="gallery__main">
                <x-product.thumb :product="$product" :size="560" :eager="true" />
            </div>

            @if ($product->images->count() > 1)
                <div class="gallery__thumbs">
                    @foreach ($product->images as $image)
                        <img src="{{ asset('storage/'.$image->path) }}"
                             alt="{{ $image->alt ?? $product->displayName() }}"
                             width="62" height="62" class="gallery__thumb"
                             loading="lazy" decoding="async">
                    @endforeach
                </div>
            @endif
        </div>

        <div class="stack">
            <div>
                @if ($product->brand)
                    <p class="small muted" style="margin-block-end:2px">{{ $product->brand->name }}</p>
                @endif
                <h1>{{ $product->displayName() }}</h1>
                @if ($product->model)
                    <p class="xsmall dim" style="margin-block-start:4px">موديل: {{ $product->model }}</p>
                @endif
            </div>

            {{-- The headline number is the real total, not the sticker price.
                 That distinction is the whole point of the platform. --}}
            @if (! $board->isEmpty())
                <div>
                    <div class="price-headline">
                        <span class="price-headline__amount">
                            {{ money($board->cheapestTotal() ?? $board->lowestPrice()) }}
                        </span>
                        <span class="price-headline__label">
                            @if ($zone && $board->cheapestTotal() !== null)
                                أقل إجمالي شامل التوصيل إلى {{ $zone->name }}
                            @else
                                أقل سعر معروض
                            @endif
                        </span>
                    </div>
                    <p class="small muted" style="margin-block-start:4px">
                        متاح لدى {{ $board->sellersCount() }}
                        {{ $board->sellersCount() === 1 ? 'متجر' : 'متاجر' }} في {{ config('banha.city') }}
                    </p>
                </div>
            @endif

            @if ($variants->count() > 1)
                <div>
                    <p class="field__label">الإصدارات المتاحة</p>
                    <div class="variant-list">
                        @foreach ($variants as $variant)
                            <a href="{{ $variant->url() }}" class="variant"
                               @if ($variant->id === $product->id) aria-current="page" @endif>
                                {{ $variant->variant_label ?: $variant->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (! empty($product->highlights))
                <ul class="highlights">
                    @foreach ($product->highlights as $highlight)
                        <li>
                            <x-ui.icon name="check" :size="14" />
                            <span>{{ $highlight }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if (! $zone)
                <x-ui.alert tone="info">
                    اختر منطقة التوصيل من أعلى الصفحة عشان نحسب السعر النهائي بدقة.
                </x-ui.alert>
            @endif
        </div>
    </div>

    {{-- Offer comparison: the primary interaction on this page. --}}
    <section class="section" id="offers">
        <div class="section__head">
            <h2>عروض المتاجر</h2>
        </div>

        {{-- Rendered server-side on first paint — the offers, prices and totals
             are in the initial HTML — then Livewire takes over for sorting,
             zone changes and live stock. --}}
        <livewire:offer-board :product="$product" />

        @if ($zone && ! $board->isEmpty() && $board->deliverableCount() < $board->count())
            <p class="small muted" style="margin-block-start:10px">
                {{ $board->count() - $board->deliverableCount() }} عرض غير معروض بسعر نهائي لأن المتجر لا يوصّل إلى {{ $zone->name }}.
            </p>
        @endif
    </section>

    @if ($product->attributes->isNotEmpty() || $product->description)
        <section class="section split">
            <div class="panel">
                <div class="panel__head"><h2>المواصفات</h2></div>
                <div class="panel__body">
                    @if ($product->attributes->isNotEmpty())
                        <div class="specs">
                            @foreach ($product->attributes as $attribute)
                                <div class="specs__item">
                                    <span class="specs__label">{{ $attribute->name }}</span>
                                    <span class="specs__value">{{ $attribute->value }}</span>
                                </div>
                            @endforeach
                            @if ($product->barcode)
                                <div class="specs__item">
                                    <span class="specs__label">الباركود</span>
                                    <span class="specs__value num">{{ $product->barcode }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($product->description)
                        <div style="margin-block-start:{{ $product->attributes->isNotEmpty() ? '16px' : '0' }}">
                            <p class="muted">{{ $product->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <aside class="panel">
                <div class="panel__head"><h2>كيف نحسب السعر النهائي</h2></div>
                <div class="panel__body stack-12">
                    <p class="small muted">
                        الإجمالي = سعر المنتج لدى المتجر + تكلفة التوصيل لمنطقتك.
                        الترتيب الافتراضي بيبدأ بأقل إجمالي، وبعدين الأسرع توصيلًا، وبعدين أحدث تحديث للمخزون.
                    </p>
                    <p class="small muted">
                        كل رقم مخزون مكتوب جنبه آخر مرة المتجر حدّثه فيها. لو التحديث قديم بنقولك بوضوح.
                    </p>
                </div>
            </aside>
        </section>
    @endif
</x-layouts.app>
