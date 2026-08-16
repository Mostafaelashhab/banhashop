@props(['offer'])

@php $stale = $offer->hasStaleInventory(); @endphp

{{-- Inventory trust, stated plainly. The timestamp is real; when it is old we
     say so instead of presenting the stock number as current. --}}
<span class="freshness {{ $stale ? 'freshness--stale' : '' }}"
      title="{{ $offer->inventory_updated_at?->format('Y-m-d H:i') }}">
    <span class="freshness__dot" aria-hidden="true"></span>
    @if ($offer->inventory_updated_at === null)
        لم يتم تأكيد المخزون
    @elseif ($stale)
        المخزون محدَّث {{ $offer->inventoryAge() }} — قد يحتاج تأكيد
    @else
        المخزون محدَّث {{ $offer->inventoryAge() }}
    @endif
</span>
