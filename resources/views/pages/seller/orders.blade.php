<x-layouts.dashboard heading="طلبات المتجر">
    <form method="GET" class="row row--wrap" style="gap:8px;margin-block-end:16px">
        <label for="order-status" class="sr-only">حالة الطلب</label>
        <select id="order-status" name="status" class="select" style="width:auto;min-width:180px">
            <option value="">كل الحالات</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn--sm">تصفية</button>
        <a href="{{ route('seller.orders.index', ['view' => 'open']) }}" class="btn btn--sm">الطلبات المفتوحة فقط</a>
    </form>

    @if ($orders->isEmpty())
        <x-ui.empty title="مفيش طلبات هنا" text="أول ما عميل يطلب من متجرك هيظهر الطلب في المكان ده." />
    @else
        <div class="stack-12">
            @foreach ($orders as $sellerOrder)
                <article class="panel">
                    <div class="panel__head">
                        <div>
                            <a href="{{ route('seller.orders.show', $sellerOrder) }}" class="strong num">
                                {{ $sellerOrder->reference }}
                            </a>
                            <div class="xsmall muted">
                                {{ $sellerOrder->order->customer_name }} —
                                {{ $sellerOrder->order->shipping_zone_name }} —
                                {{ $sellerOrder->created_at?->diffForHumans() }}
                            </div>
                        </div>
                        <x-ui.badge :tone="$sellerOrder->status->tone()">{{ $sellerOrder->status->label() }}</x-ui.badge>
                    </div>

                    <div class="panel__body">
                        <ul class="small">
                            @foreach ($sellerOrder->items as $item)
                                <li class="row row--between" style="padding-block:2px">
                                    <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
                                    <span class="num">{{ money($item->line_total_cents) }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="row row--between" style="margin-block-start:10px;padding-block-start:10px;border-block-start:1px solid var(--line-2)">
                            <span class="small muted">
                                التوصيل: {{ $sellerOrder->shipping_provider_name ?? '—' }} ({{ money($sellerOrder->shipping_cents) }})
                            </span>
                            <span class="strong num">{{ money($sellerOrder->total_cents) }}</span>
                        </div>
                    </div>

                    @if ($sellerOrder->status->nextStates())
                        <div class="panel__foot">
                            <x-seller.order-actions :seller-order="$sellerOrder" />
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        {{ $orders->links() }}
    @endif
</x-layouts.dashboard>
