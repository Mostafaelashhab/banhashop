@props(['product', 'eager' => false])

<article class="product-card">
    <a href="{{ $product->url() }}" class="product-card__media" tabindex="-1" aria-hidden="true">
        <x-product.thumb :product="$product" :size="300" :eager="$eager" />
    </a>

    <div class="product-card__body">
        @if ($product->brand)
            <span class="product-card__brand">{{ $product->brand->name }}</span>
        @endif

        <a href="{{ $product->url() }}" class="product-card__title clamp-2">{{ $product->displayName() }}</a>

        <div class="product-card__price">
            @if ($product->offers_count > 0)
                <span class="product-card__from">يبدأ من</span>
                <div class="product-card__amount">{{ money($product->min_price_cents) }}</div>
                <span class="product-card__offers">
                    {{ arabic_count((int) $product->sellers_count, 'متجر واحد', 'متجرين', 'متاجر', 'متجرًا') }} في {{ config('banha.city') }}
                </span>
            @else
                <span class="product-card__empty">لا توجد عروض حاليًا</span>
            @endif
        </div>
    </div>
</article>
