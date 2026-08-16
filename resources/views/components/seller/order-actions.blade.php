@props(['sellerOrder'])

@php $next = $sellerOrder->status->nextStates(); @endphp

{{-- Only transitions the domain actually allows are rendered, so the UI can
     never offer a move the workflow will reject. --}}
<div class="row row--wrap" style="gap:8px">
    @foreach ($next as $target)
        @if ($target === \App\Enums\SellerOrderStatus::Rejected)
            <details>
                <summary class="btn btn--sm btn--danger" style="cursor:pointer;list-style:none">رفض الطلب</summary>
                <form method="POST" action="{{ route('seller.orders.transition', $sellerOrder) }}"
                      style="margin-block-start:10px;min-width:260px">
                    @csrf
                    <input type="hidden" name="status" value="{{ $target->value }}">
                    <x-ui.field name="reason" label="سبب الرفض" required
                                placeholder="مثال: الكمية خلصت من الفرع" />
                    <button type="submit" class="btn btn--danger btn--sm" style="margin-block-start:8px">
                        تأكيد الرفض
                    </button>
                </form>
            </details>
        @else
            <form method="POST" action="{{ route('seller.orders.transition', $sellerOrder) }}">
                @csrf
                <input type="hidden" name="status" value="{{ $target->value }}">
                <button type="submit" class="btn btn--sm {{ $loop->first ? 'btn--primary' : '' }}">
                    {{ match ($target) {
                        \App\Enums\SellerOrderStatus::Accepted => 'قبول الطلب',
                        \App\Enums\SellerOrderStatus::Preparing => 'بدء التجهيز',
                        \App\Enums\SellerOrderStatus::Shipped => 'خرج للتوصيل',
                        \App\Enums\SellerOrderStatus::Delivered => 'تم التسليم',
                        \App\Enums\SellerOrderStatus::Cancelled => 'إلغاء',
                        default => $target->label(),
                    } }}
                </button>
            </form>
        @endif
    @endforeach
</div>
