<x-layouts.app>
    <section class="hero">
        <h1 class="hero__title">قارن أسعار متاجر {{ config('banha.city') }} قبل ما تشتري</h1>
        <p class="hero__text">
            منتج واحد، عروض من أكتر من متجر محلي، وسعر نهائي واضح شامل التوصيل.
            أرخص سعر معروض مش دايمًا أرخص صفقة — إحنا بنوريك الفرق.
        </p>

        <ol class="hero__steps">
            <li class="hero__step">
                <span class="hero__step-num" aria-hidden="true">١</span>
                <span class="hero__step-text">ابحث عن المنتج في الكتالوج</span>
            </li>
            <li class="hero__step">
                <span class="hero__step-num" aria-hidden="true">٢</span>
                <span class="hero__step-text">قارن عروض المتاجر المحلية</span>
            </li>
            <li class="hero__step">
                <span class="hero__step-num" aria-hidden="true">٣</span>
                <span class="hero__step-text">شوف السعر النهائي شامل التوصيل</span>
            </li>
            <li class="hero__step">
                <span class="hero__step-num" aria-hidden="true">٤</span>
                <span class="hero__step-text">اطلب وادفع عند الاستلام</span>
            </li>
        </ol>
    </section>

    @if ($categories->isNotEmpty())
        <section class="section">
            <div class="section__head">
                <h2>الأقسام</h2>
                <a href="{{ route('products.index') }}" class="section__link">كل المنتجات</a>
            </div>

            @php
                // Presentational only, and every category still renders without
                // an entry here — an unmapped slug falls back to the tag icon.
                $categoryIcons = [
                    'mobile-phones'   => 'phone',
                    'computers'       => 'grid',
                    'electronics'     => 'layers',
                    'home-appliances' => 'package',
                ];
            @endphp

            <div class="category-strip">
                @foreach ($categories as $category)
                    <a href="{{ route('categories.show', $category->slug) }}" class="category-tile">
                        <span class="category-tile__icon">
                            <x-ui.icon :name="$categoryIcons[$category->slug] ?? 'tag'" :size="19" />
                        </span>
                        <span class="category-tile__body">
                            <span class="category-tile__name">{{ $category->name }}</span>
                            <span class="category-tile__count num">{{ $category->products_count }} منتج</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($competitive->isNotEmpty())
        <section class="section">
            <div class="section__head">
                <div>
                    <h2>عليها منافسة بين المتاجر</h2>
                    <p class="small muted" style="margin-block-start:2px">
                        منتجات معروضة من أكتر من متجر — هنا المقارنة بتفرق فعلًا.
                    </p>
                </div>
            </div>

            <x-product.grid :products="$competitive" />
        </section>
    @endif

    @if ($newest->isNotEmpty())
        <section class="section">
            <div class="section__head">
                <h2>أحدث ما أضافته المتاجر</h2>
                <a href="{{ route('products.index') }}" class="section__link">تصفح الكل</a>
            </div>

            <x-product.grid :products="$newest" />
        </section>
    @endif

    @if ($stores->isNotEmpty())
        <section class="section">
            <div class="section__head">
                <h2>متاجر {{ config('banha.city') }}</h2>
                <a href="{{ route('stores.index') }}" class="section__link">كل المتاجر</a>
            </div>

            <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
                @foreach ($stores as $store)
                    <x-seller.card :seller="$store" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="section">
        <div class="panel">
            <div class="panel__body">
                <h2 style="font-size:1.05rem">مش لاقي المنتج اللي بتدور عليه؟</h2>
                <p class="muted small" style="margin-block-start:6px;max-width:60ch">
                    اطلبه وهنستخدم طلبات العملاء دي عشان نقنع متاجر {{ config('banha.city') }} تضيفه.
                </p>
                <a href="{{ route('product-requests.create') }}" class="btn btn--primary" style="margin-block-start:14px">
                    اطلب منتجًا
                </a>
            </div>
        </div>
    </section>
</x-layouts.app>
