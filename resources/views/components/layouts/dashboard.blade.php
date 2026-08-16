@props(['heading' => null, 'nav' => 'seller'])

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b5c4b">

    <x-seo :seo="$seo" />

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="stylesheet" href="{{ asset_v('assets/css/app.css') }}">
</head>
<body>
    <a href="#main" class="skip-link">تخطَّ إلى المحتوى</a>

    <x-ui.icon-sprite />

    <header class="site-header">
        <div class="site-header__top">
            <div class="container site-header__bar">
                <a href="{{ route('home') }}" class="brand">
                    <img src="{{ asset('assets/img/logo-mark.svg') }}" alt="" class="brand__mark"
                         width="34" height="34" decoding="async">
                    <span class="brand__text">
                        <span class="brand__name">بنها شوب</span>
                        <span class="brand__sub">{{ $nav === 'admin' ? 'لوحة الإدارة' : 'لوحة المتجر' }}</span>
                    </span>
                </a>

                <div class="header-actions">
                    <a href="{{ route('home') }}" class="header-action">
                        <span class="header-action__icon"><x-ui.icon name="store" :size="19" /></span>
                        <span class="header-action__label">عرض الموقع</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="header-action" aria-label="تسجيل الخروج">
                            <span class="header-action__icon"><x-ui.icon name="logout" :size="19" /></span>
                            <span class="header-action__label" aria-hidden="true">خروج</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main id="main" class="page page--tight" tabindex="-1">
        <div class="container">
            <div class="dash">
                @if ($nav === 'admin')
                    <x-dashboard.admin-nav />
                @else
                    <x-dashboard.seller-nav />
                @endif

                <div>
                    @if ($heading)
                        <div class="row row--between row--wrap" style="margin-block-end:18px;gap:12px">
                            <h1>{{ $heading }}</h1>
                            @isset($actions)
                                <div class="row" style="gap:8px">{{ $actions }}</div>
                            @endisset
                        </div>
                    @endif

                    <x-ui.flash />

                    {{ $slot }}
                </div>
            </div>
        </div>
    </main>
</body>
</html>
