@props(['title', 'text' => null])

{{-- An empty state states the fact plainly and offers the next useful action.
     No illustration, no oversized icon, no filler. --}}
<div {{ $attributes->merge(['class' => 'empty']) }}>
    <p class="empty__title">{{ $title }}</p>
    @if ($text)
        <p class="empty__text">{{ $text }}</p>
    @endif
    @isset($action)
        <div class="empty__action">{{ $action }}</div>
    @endisset
</div>
