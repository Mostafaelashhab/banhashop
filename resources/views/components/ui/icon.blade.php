@props(['name', 'size' => 18])

{{-- One sprite, referenced by <use>. No emoji, no icon font, no per-icon HTTP
     request. Decorative by default; give the parent control an aria-label. --}}
<svg
    width="{{ $size }}"
    height="{{ $size }}"
    aria-hidden="true"
    focusable="false"
    {{ $attributes->merge(['class' => 'icon']) }}
><use href="#i-{{ $name }}"/></svg>
