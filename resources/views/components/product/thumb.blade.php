@props([
    'product',
    'size' => 400,
    'eager' => false,
    'class' => '',
])

@php
    $path = $product->image_path;
    $alt = $product->displayName();
@endphp

@if ($path)
    {{-- Explicit dimensions + a reserved aspect box: no layout shift when the
         image decodes, which is most of the CLS budget on a catalog page. --}}
    <img
        src="{{ \Illuminate\Support\Str::startsWith($path, ['http://', 'https://', '/']) ? $path : asset('storage/'.$path) }}"
        alt="{{ $alt }}"
        width="{{ $size }}"
        height="{{ $size }}"
        loading="{{ $eager ? 'eager' : 'lazy' }}"
        {{ $eager ? 'fetchpriority=high' : '' }}
        decoding="async"
        class="{{ $class }}"
    >
@else
    {{-- No invented stock photography. A quiet placeholder is more honest than
         a generic image that is not this product. Derived from the name alone,
         so this component never forces an extra relation to be loaded. --}}
    <span class="thumb-fallback {{ $class }}" aria-hidden="true">
        {{ mb_substr($alt, 0, 2) }}
    </span>
@endif
