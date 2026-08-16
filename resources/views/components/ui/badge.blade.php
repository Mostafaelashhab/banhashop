@props(['tone' => 'neutral', 'icon' => null])

@php
    $toneClass = match ($tone) {
        'success' => 'badge--good',
        'warning' => 'badge--warn',
        'danger' => 'badge--bad',
        'info' => 'badge--info',
        'brand' => 'badge--brand',
        'outline' => 'badge--outline',
        default => '',
    };
@endphp

<span {{ $attributes->merge(['class' => trim('badge '.$toneClass)]) }}>
    @if ($icon)
        <x-ui.icon :name="$icon" :size="13" />
    @endif
    {{ $slot }}
</span>
