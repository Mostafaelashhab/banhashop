<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b5c4b">

    <x-seo :seo="$seo" />

    {{-- SVG first for anything modern; the .ico stays as the fallback older
         browsers and some crawlers still ask for by convention. --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/logo-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset_v('assets/css/app.css') }}">
</head>
<body>
    <a href="#main" class="skip-link">تخطَّ إلى المحتوى</a>

    <x-ui.icon-sprite />

    <x-site-header />

    <main id="main" class="page" tabindex="-1">
        <div class="container">
            <x-ui.flash />
            {{ $slot }}
        </div>
    </main>

    <x-layout.site-footer />

    <x-layout.bottom-nav />
</body>
</html>
