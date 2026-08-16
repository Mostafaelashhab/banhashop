@props(['title', 'text' => null, 'illustration' => null])

{{-- An empty state states the fact plainly and offers the next useful action.

     The illustration is opt-in and stays that way. It belongs on the few
     customer-facing dead ends that are a moment in a journey — an empty cart,
     a search that found nothing — where the page is genuinely bare and the
     picture softens it. It does not belong on an admin work queue: "مفيش
     منتجات مطابقة" after a filter is a result, not a disappointment, and a
     drawing there is noise between someone and their job. --}}
<div {{ $attributes->merge(['class' => 'empty']) }}>
    @if ($illustration)
        <img src="{{ asset('assets/img/empty/'.$illustration.'.svg') }}" alt=""
             class="empty__art" loading="lazy" decoding="async">
    @endif

    <p class="empty__title">{{ $title }}</p>
    @if ($text)
        <p class="empty__text">{{ $text }}</p>
    @endif
    @isset($action)
        <div class="empty__action">{{ $action }}</div>
    @endisset
</div>
