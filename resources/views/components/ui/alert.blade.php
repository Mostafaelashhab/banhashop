@props(['tone' => 'info', 'icon' => null, 'title' => null])

@php
    $icons = ['good' => 'check-circle', 'warn' => 'alert', 'bad' => 'alert', 'info' => 'info'];
    $iconName = $icon ?? ($icons[$tone] ?? 'info');
@endphp

<div {{ $attributes->merge(['class' => 'alert alert--'.$tone]) }} role="{{ in_array($tone, ['bad', 'warn']) ? 'alert' : 'status' }}">
    <x-ui.icon :name="$iconName" :size="18" />
    <div>
        @if ($title)
            <div class="strong">{{ $title }}</div>
        @endif
        {{ $slot }}
    </div>
</div>
