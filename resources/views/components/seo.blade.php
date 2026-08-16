@props(['seo'])

{{-- Every page's metadata comes from one SeoData object. No page writes its
     own meta tags, so title/canonical/robots rules can never drift apart. --}}
<title>{{ $seo->fullTitle() }}</title>
<meta name="description" content="{{ $seo->metaDescription() }}">
<meta name="robots" content="{{ $seo->robots }}">

@if ($seo->canonical)
    <link rel="canonical" href="{{ $seo->canonical }}">
@endif

<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:type" content="{{ $seo->ogType }}">
<meta property="og:locale" content="ar_EG">
<meta property="og:title" content="{{ $seo->title ?? config('banha.seo.default_title') }}">
<meta property="og:description" content="{{ $seo->metaDescription() }}">
@if ($seo->canonical)
    <meta property="og:url" content="{{ $seo->canonical }}">
@endif
@if ($seo->ogImage)
    <meta property="og:image" content="{{ $seo->ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
@else
    <meta name="twitter:card" content="summary">
@endif
<meta name="twitter:title" content="{{ $seo->title ?? config('banha.seo.default_title') }}">
<meta name="twitter:description" content="{{ $seo->metaDescription() }}">

@foreach ($seo->structuredData as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endforeach
