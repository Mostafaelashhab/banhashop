@props([
    'product',
    'size' => 400,
    'eager' => false,
    'class' => '',
    // What slot the image occupies, so the browser can pick a rendition before
    // layout. Defaults to the catalog grid: two columns on a phone, three from
    // 640px, four from 1000px.
    'sizes' => '(min-width: 1000px) 280px, (min-width: 640px) 32vw, 46vw',
])

@php
    $path = $product->image_path;
    $alt = $product->displayName();
    $isAbsolute = \Illuminate\Support\Str::startsWith($path ?? '', ['http://', 'https://', '/']);
    $responsive = $isAbsolute ? null : \App\Support\Images\ResponsiveImage::fromPath($path);
    $url = fn (string $p) => asset('storage/'.$p);
@endphp

@if ($path)
    {{-- Explicit dimensions + a reserved aspect box: no layout shift when the
         image decodes, which is most of the CLS budget on a catalog page. --}}
    <img
        src="{{ $isAbsolute ? $path : $url($responsive?->path() ?? $path) }}"
        @if ($responsive)
            srcset="{{ $responsive->srcset($url) }}"
            sizes="{{ $sizes }}"
        @endif
        alt="{{ $alt }}"
        width="{{ $size }}"
        height="{{ $size }}"
        loading="{{ $eager ? 'eager' : 'lazy' }}"
        {{ $eager ? 'fetchpriority=high' : '' }}
        decoding="async"
        class="{{ $class }}"
    >
@else
    {{-- No invented stock photography: a generic product shot would misrepresent
         what the store is selling. A designed placeholder fills the same box a
         real photo would, so a card without one keeps its shape instead of
         opening a hole in the grid.

         alt is empty on purpose — it carries no information the product name
         beside it does not already give, so a screen reader should skip it. --}}
    <img
        src="{{ asset('assets/img/product-placeholder.svg') }}"
        alt=""
        width="{{ $size }}"
        height="{{ $size }}"
        loading="{{ $eager ? 'eager' : 'lazy' }}"
        decoding="async"
        class="thumb-placeholder {{ $class }}"
    >
@endif
