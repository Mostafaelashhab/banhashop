@props(['categories'])

<header class="site-header">
    <div class="site-header__top">
        <div class="container site-header__bar">
            <a href="{{ route('home') }}" class="brand" aria-label="{{ config('app.name') }} — الصفحة الرئيسية">
                <img src="{{ asset('assets/img/logo-mark.svg') }}" alt="" class="brand__mark"
                     width="34" height="34" decoding="async">
                <span class="brand__text">
                    <span class="brand__name">بنها شوب</span>
                    {{-- Not the city: the brand name already ends in it, and
                         "بنها شوب / بنها" just said the word twice. The sub-line
                         earns its place by saying what the site is instead. --}}
                    <span class="brand__sub">مقارنة أسعار</span>
                </span>
            </a>

            <div class="header-search">
                <x-search.form />
            </div>

            {{-- Delivery pricing is meaningless without a destination, so the
                 zone is a labelled header control. Changing it re-prices the
                 offer board in place rather than reloading the page. --}}
            <livewire:zone-picker />

            <nav class="header-actions" aria-label="حسابي">
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="header-action">
                            <span class="header-action__icon"><x-ui.icon name="settings" :size="19" /></span>
                            <span class="header-action__label">لوحة الإدارة</span>
                        </a>
                    @elseif (auth()->user()->isSeller())
                        <a href="{{ route('seller.dashboard') }}" class="header-action">
                            <span class="header-action__icon"><x-ui.icon name="store" :size="19" /></span>
                            <span class="header-action__label">لوحة المتجر</span>
                        </a>
                    @endif

                    <a href="{{ route('account.orders') }}" class="header-action">
                        <span class="header-action__icon"><x-ui.icon name="user" :size="19" /></span>
                        <span class="header-action__label">طلباتي</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="header-action">
                        <span class="header-action__icon"><x-ui.icon name="user" :size="19" /></span>
                        <span class="header-action__label">تسجيل الدخول</span>
                    </a>
                @endauth

                <livewire:cart-counter />
            </nav>
        </div>
    </div>

    @if (count($categories))
        <div class="nav-bar">
            <nav class="container" aria-label="الأقسام">
                <ul class="nav-bar__list">
                    <li>
                        <a href="{{ route('products.index') }}" class="nav-bar__link"
                           @if (request()->routeIs('products.index')) aria-current="page" @endif>كل المنتجات</a>
                    </li>
                    @foreach ($categories as $category)
                        <li>
                            <a href="{{ route('categories.show', $category['slug']) }}" class="nav-bar__link"
                               @if (request()->routeIs('categories.show') && request()->route('category')?->id === $category['id']) aria-current="page" @endif>
                                {{ $category['name'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    @endif
</header>
