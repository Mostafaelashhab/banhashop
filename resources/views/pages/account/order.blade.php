<x-layouts.app>
    <nav class="breadcrumbs" aria-label="مسار التصفح">
        <ol>
            <li><a href="{{ route('account.orders') }}">طلباتي</a></li>
            <li><span aria-current="page">{{ $order->number }}</span></li>
        </ol>
    </nav>

    <div class="row row--between row--wrap" style="margin-block-end:18px;gap:12px">
        <div>
            <h1 class="num">الطلب {{ $order->number }}</h1>
            <p class="small muted">{{ $order->placed_at?->translatedFormat('j F Y — g:i a') }}</p>
        </div>
        <x-ui.badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-ui.badge>
    </div>

    <div class="split">
        <div class="stack">
            {{-- One card per store: fulfilment is per seller, and the customer
                 sees exactly that. --}}
            @foreach ($order->sellerOrders as $sellerOrder)
                <section class="panel">
                    <div class="panel__head">
                        <div class="row">
                            <x-ui.icon name="store" :size="17" />
                            <a href="{{ route('stores.show', $sellerOrder->seller->slug) }}" class="strong">
                                {{ $sellerOrder->seller->name }}
                            </a>
                        </div>
                        <x-ui.badge :tone="$sellerOrder->status->tone()">{{ $sellerOrder->status->label() }}</x-ui.badge>
                    </div>

                    <div class="panel__body">
                        @foreach ($sellerOrder->items as $item)
                            <div class="row row--between row--top" style="padding-block:8px;gap:12px">
                                <div style="min-width:0">
                                    <a href="{{ route('products.show', $item->product_slug) }}" class="strong small">
                                        {{ $item->product_name }}
                                        @if ($item->variant_label) — {{ $item->variant_label }} @endif
                                    </a>
                                    <p class="xsmall muted num">
                                        {{ money($item->unit_price_cents) }} × {{ $item->quantity }}
                                    </p>
                                </div>
                                <span class="num nowrap strong">{{ money($item->line_total_cents) }}</span>
                            </div>
                        @endforeach

                        <div class="totals" style="margin-block-start:8px;border-block-start:1px solid var(--line-2);padding-block-start:8px">
                            <div class="totals__line">
                                <span class="muted">
                                    التوصيل
                                    @if ($sellerOrder->shipping_provider_name)
                                        <span class="dim">— {{ $sellerOrder->shipping_provider_name }}</span>
                                    @endif
                                </span>
                                <span class="totals__value">{{ money($sellerOrder->shipping_cents) }}</span>
                            </div>
                            <div class="totals__line">
                                <span class="strong">إجمالي هذا المتجر</span>
                                <span class="totals__value strong">{{ money($sellerOrder->total_cents) }}</span>
                            </div>
                        </div>

                        @if ($sellerOrder->promised_at)
                            <p class="xsmall muted" style="margin-block-start:8px">
                                موعد التسليم المتوقع: {{ $sellerOrder->promised_at->translatedFormat('j F — g:i a') }}
                            </p>
                        @endif

                        @if ($sellerOrder->rejection_reason)
                            <x-ui.alert tone="bad" style="margin-block-start:10px">
                                سبب الرفض: {{ $sellerOrder->rejection_reason }}
                            </x-ui.alert>
                        @endif
                    </div>
                </section>
            @endforeach

            <section class="panel">
                <div class="panel__head"><h2>سجل الطلب</h2></div>
                <div class="panel__body">
                    <ol class="timeline">
                        @foreach ($order->events as $event)
                            <li class="timeline__item timeline__item--done">
                                <p class="timeline__title">{{ $event->note ?: $event->status }}</p>
                                <p class="timeline__time">{{ $event->created_at?->translatedFormat('j F — g:i a') }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        </div>

        <aside class="stack-12">
            <section class="panel">
                <div class="panel__head"><h3>الإجمالي</h3></div>
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
                        <span>المطلوب دفعه</span>
                        <span class="totals__value">{{ money($order->grand_total_cents) }}</span>
                    </div>
                    <p class="xsmall muted" style="margin-block-start:8px">
                        {{ $order->payment_method->label() }} — {{ $order->payment_status->label() }}
                    </p>
                </div>
            </section>

            <section class="panel">
                <div class="panel__head"><h3>عنوان التوصيل</h3></div>
                <div class="panel__body small">
                    <p class="strong">{{ $order->customer_name }}</p>
                    <p class="muted">{{ $order->shippingAddressLine() }}</p>
                    <p class="muted num">{{ $order->customer_phone }}</p>
                    @if ($order->shipping_landmark)
                        <p class="xsmall dim">علامة مميزة: {{ $order->shipping_landmark }}</p>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</x-layouts.app>
