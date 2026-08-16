<x-layouts.dashboard heading="الطلب {{ $order->number }}" nav="admin">
    <x-slot:actions>
        <x-ui.badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-ui.badge>
    </x-slot:actions>

    <div class="split">
        <div class="stack-12">
            @foreach ($order->sellerOrders as $sellerOrder)
                <section class="panel">
                    <div class="panel__head">
                        <div>
                            <a href="{{ route('stores.show', $sellerOrder->seller->slug) }}" class="strong">
                                {{ $sellerOrder->seller->name }}
                            </a>
                            <div class="xsmall dim num">{{ $sellerOrder->reference }}</div>
                        </div>
                        <x-ui.badge :tone="$sellerOrder->status->tone()">{{ $sellerOrder->status->label() }}</x-ui.badge>
                    </div>
                    <div class="panel__body">
                        @foreach ($sellerOrder->items as $item)
                            <div class="row row--between small" style="padding-block:3px">
                                <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
                                <span class="num">{{ money($item->line_total_cents) }}</span>
                            </div>
                        @endforeach
                        <div class="row row--between small" style="margin-block-start:8px;padding-block-start:8px;border-block-start:1px solid var(--line-2)">
                            <span class="muted">
                                التوصيل — {{ $sellerOrder->shipping_provider_name ?? '—' }}
                            </span>
                            <span class="num">{{ money($sellerOrder->shipping_cents) }}</span>
                        </div>
                        <div class="row row--between" style="margin-block-start:6px">
                            <span class="strong">إجمالي المتجر</span>
                            <span class="strong num">{{ money($sellerOrder->total_cents) }}</span>
                        </div>

                        @if ($sellerOrder->rejection_reason)
                            <x-ui.alert tone="bad" style="margin-block-start:10px">
                                سبب الرفض: {{ $sellerOrder->rejection_reason }}
                            </x-ui.alert>
                        @endif
                    </div>
                </section>
            @endforeach

            <section class="panel">
                <div class="panel__head"><h2>السجل</h2></div>
                <div class="panel__body">
                    <ol class="timeline">
                        @foreach ($order->events as $event)
                            <li class="timeline__item timeline__item--done">
                                <p class="timeline__title small">{{ $event->note ?: $event->status }}</p>
                                <p class="timeline__time">{{ $event->created_at?->translatedFormat('j F — g:i a') }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        </div>

        <aside class="stack-12">
            <section class="panel">
                <div class="panel__head"><h3>العميل</h3></div>
                <div class="panel__body small">
                    <p class="strong">{{ $order->customer_name }}</p>
                    <p class="num"><a href="tel:{{ $order->customer_phone }}">{{ $order->customer_phone }}</a></p>
                    <p class="muted">{{ $order->shippingAddressLine() }}</p>
                </div>
            </section>

            <section class="panel">
                <div class="panel__head"><h3>المبالغ</h3></div>
                <div class="panel__body totals">
                    <div class="totals__line">
                        <span class="muted">المنتجات</span>
                        <span class="totals__value">{{ money($order->items_total_cents) }}</span>
                    </div>
                    <div class="totals__line">
                        <span class="muted">التوصيل</span>
                        <span class="totals__value">{{ money($order->shipping_total_cents) }}</span>
                    </div>
                    <div class="totals__line totals__line--grand">
                        <span>الإجمالي</span>
                        <span class="totals__value">{{ money($order->grand_total_cents) }}</span>
                    </div>
                    <p class="xsmall muted" style="margin-block-start:8px">
                        {{ $order->payment_method->label() }} — {{ $order->payment_status->label() }}
                    </p>
                </div>
            </section>
        </aside>
    </div>
</x-layouts.dashboard>
