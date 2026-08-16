<x-layouts.dashboard heading="طلب {{ $sellerOrder->reference }}">
    <x-slot:actions>
        <a href="{{ route('seller.orders.index') }}" class="btn btn--sm">كل الطلبات</a>
    </x-slot:actions>

    <div class="split">
        <div class="stack-12">
            <section class="panel">
                <div class="panel__head">
                    <h2>المنتجات</h2>
                    <x-ui.badge :tone="$sellerOrder->status->tone()">{{ $sellerOrder->status->label() }}</x-ui.badge>
                </div>
                <div class="panel__body">
                    @foreach ($sellerOrder->items as $item)
                        <div class="row row--between row--top" style="padding-block:8px;gap:12px">
                            <div>
                                <p class="strong small">
                                    {{ $item->product_name }}
                                    @if ($item->variant_label) — {{ $item->variant_label }} @endif
                                </p>
                                <p class="xsmall muted num">{{ money($item->unit_price_cents) }} × {{ $item->quantity }}</p>
                            </div>
                            <span class="num strong nowrap">{{ money($item->line_total_cents) }}</span>
                        </div>
                    @endforeach

                    <div class="totals" style="margin-block-start:8px;border-block-start:1px solid var(--line-2);padding-block-start:10px">
                        <div class="totals__line">
                            <span class="muted">إجمالي المنتجات</span>
                            <span class="totals__value">{{ money($sellerOrder->items_total_cents) }}</span>
                        </div>
                        <div class="totals__line">
                            <span class="muted">التوصيل — {{ $sellerOrder->shipping_provider_name ?? '—' }}</span>
                            <span class="totals__value">{{ money($sellerOrder->shipping_cents) }}</span>
                        </div>
                        <div class="totals__line totals__line--grand">
                            <span>الإجمالي المطلوب تحصيله</span>
                            <span class="totals__value">{{ money($sellerOrder->total_cents) }}</span>
                        </div>
                    </div>
                </div>

                @if ($sellerOrder->status->nextStates())
                    <div class="panel__foot">
                        <x-seller.order-actions :seller-order="$sellerOrder" />
                    </div>
                @endif
            </section>
        </div>

        <aside class="stack-12">
            <section class="panel">
                <div class="panel__head"><h3>بيانات التوصيل</h3></div>
                <div class="panel__body small">
                    <p class="strong">{{ $sellerOrder->order->customer_name }}</p>
                    <p class="muted">{{ $sellerOrder->order->shippingAddressLine() }}</p>
                    <p class="num">
                        <a href="tel:{{ $sellerOrder->order->customer_phone }}">{{ $sellerOrder->order->customer_phone }}</a>
                    </p>
                    @if ($sellerOrder->order->shipping_landmark)
                        <p class="xsmall dim">علامة مميزة: {{ $sellerOrder->order->shipping_landmark }}</p>
                    @endif
                    @if ($sellerOrder->order->shipping_notes)
                        <p class="xsmall dim">ملاحظات: {{ $sellerOrder->order->shipping_notes }}</p>
                    @endif
                </div>
            </section>

            <section class="panel">
                <div class="panel__head"><h3>الشحن</h3></div>
                <div class="panel__body small stack-8">
                    <p>
                        <span class="muted">الشركة:</span>
                        {{ $sellerOrder->shipping_provider_name ?? 'غير محدد' }}
                    </p>
                    @if ($sellerOrder->promised_at)
                        <p>
                            <span class="muted">الموعد الموعود:</span>
                            {{ $sellerOrder->promised_at->translatedFormat('j F — g:i a') }}
                        </p>
                    @endif
                    <p>
                        <span class="muted">طريقة الدفع:</span>
                        {{ $sellerOrder->order->payment_method->label() }}
                    </p>
                </div>
            </section>
        </aside>
    </div>
</x-layouts.dashboard>
