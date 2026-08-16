<x-layouts.app>
    {{-- Search is the product. Not a banner with a search box in it: the first
         thing on the page is the field, the line above it says what searching
         here gets you, and the line below is live marketplace data rather than
         a slogan. The four-step explainer that used to sit here has its own
         page; it was teaching the model to people who came to check a price. --}}
    <section class="discover">
        <h1 class="discover__title">قارن أسعار متاجر {{ config('banha.city') }} قبل ما تشتري</h1>
        <p class="discover__text">
            سعر المنتج + التوصيل = السعر النهائي. أرخص سعر معروض مش دايمًا أرخص صفقة.
        </p>

        <div class="discover__search">
            <x-search.form placeholder="ابحث عن منتج، ماركة، أو موديل…" />
        </div>

        <p class="discover__meta">
            <span class="num">{{ $marketplace['products'] }}</span> منتج عليه عروض
            <span class="discover__dot" aria-hidden="true">·</span>
            <span class="num">{{ $marketplace['stores'] }}</span> متجر في {{ config('banha.city') }}
            <span class="discover__dot" aria-hidden="true">·</span>
            <a href="{{ route('pages.how-it-works') }}">إزاي بنحسب السعر النهائي</a>
        </p>
    </section>

    @if ($competitive->isNotEmpty())
        <section class="section">
            <div class="section__head">
                <div>
                    <h2>عليها منافسة بين المتاجر</h2>
                    <p class="small muted" style="margin-block-start:2px">
                        معروضة من أكتر من متجر — هنا المقارنة بتفرق فعلًا.
                    </p>
                </div>
            </div>

            <x-product.grid :products="$competitive" />
        </section>
    @endif

    @if ($categories->isNotEmpty())
        <section class="section">
            <div class="section__head">
                <h2>الأقسام</h2>
                <a href="{{ route('products.index') }}" class="section__link">كل المنتجات</a>
            </div>

            <div class="category-strip">
                @foreach ($categories as $category)
                    <a href="{{ route('categories.show', $category->slug) }}" class="category-tile">
                        <span class="category-tile__name">{{ $category->name }}</span>
                        <span class="category-tile__count num">{{ $category->products_count }} منتج</span>
                    </a>
                @endforeach
            </div>
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

    @if ($demand->isNotEmpty())
        <section class="section">
            <div class="section__head">
                <div>
                    <h2>مطلوب في {{ config('banha.city') }}</h2>
                    <p class="small muted" style="margin-block-start:2px">
                        ناس سألت عن المنتجات دي ولسه مفيش متجر عارضها. بنستخدم الأرقام دي عشان نقنع المتاجر تضيفها.
                    </p>
                </div>
            </div>

            <ul class="demand">
                @foreach ($demand as $item)
                    <li class="demand__item">
                        <span class="demand__name">{{ $item->label }}</span>
                        <span class="demand__count num">
                            {{ arabic_count((int) $item->requests, 'طلب واحد', 'طلبين', 'طلبات', 'طلبًا') }}
                        </span>
                    </li>
                @endforeach
            </ul>

            <p class="small" style="margin-block-start:12px">
                <a href="{{ route('product-requests.create') }}" class="strong">اطلب منتج مش موجود</a>
            </p>
        </section>
    @endif

    @if ($stores->isNotEmpty())
        <section class="section">
            <div class="section__head">
                <h2>متاجر {{ config('banha.city') }}</h2>
                <a href="{{ route('stores.index') }}" class="section__link">كل المتاجر</a>
            </div>

            <div class="seller-grid">
                @foreach ($stores as $store)
                    <x-seller.card :seller="$store" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.app>
