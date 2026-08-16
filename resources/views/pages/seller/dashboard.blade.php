<x-layouts.dashboard heading="نظرة عامة">
    <div class="stack">
        <div class="stat-row">
            <div class="stat">
                <p class="stat__label">عروض نشطة</p>
                <p class="stat__value">{{ $activeOffers }}</p>
            </div>
            <div class="stat">
                <p class="stat__label">طلبات بانتظار الرد</p>
                <p class="stat__value">{{ $pendingOrders }}</p>
                @if ($pendingOrders > 0)
                    <p class="stat__note"><a href="{{ route('seller.orders.index', ['view' => 'open']) }}">افتح الطلبات</a></p>
                @endif
            </div>
            <div class="stat">
                <p class="stat__label">محتاج تأكيد مخزون</p>
                <p class="stat__value">{{ $staleOffers }}</p>
                <p class="stat__note">مر عليها أكثر من {{ config('banha.inventory.stale_after_hours') }} ساعة</p>
            </div>
            <div class="stat">
                <p class="stat__label">غير متوفر</p>
                <p class="stat__value">{{ $outOfStock }}</p>
            </div>
        </div>

        @if ($seller->orders_count >= 5)
            <x-ui.alert tone="info">
                نسبة قبولك للطلبات {{ $seller->acceptanceRate() }}% من إجمالي {{ $seller->orders_count }} طلب.
                الرقم ده محسوب من طلبات حقيقية، وبيأثر على ترتيب عروضك مستقبلًا.
            </x-ui.alert>
        @endif

        {{-- The single most useful thing a store can do daily: confirm that
             its stock numbers are still true. --}}
        @if ($needsAttention->isNotEmpty())
            <section class="panel">
                <div class="panel__head">
                    <h2>عروض محتاجة تأكيد المخزون</h2>
                    <a href="{{ route('seller.offers.index') }}" class="small">كل العروض</a>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>السعر</th>
                                <th>المخزون</th>
                                <th>آخر تحديث</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($needsAttention as $offer)
                                <tr>
                                    <td>{{ $offer->product->displayName() }}</td>
                                    <td class="table__num">{{ money($offer->price_cents) }}</td>
                                    <td class="table__num">{{ $offer->stock }}</td>
                                    <td class="small muted">{{ $offer->inventoryAge() ?? 'لم يُحدَّث' }}</td>
                                    <td>
                                        <div class="table__actions">
                                            <form method="POST" action="{{ route('seller.offers.confirm', $offer) }}">
                                                @csrf
                                                <button type="submit" class="btn btn--sm">
                                                    <x-ui.icon name="check" :size="14" class="btn__icon" />
                                                    المخزون صحيح
                                                </button>
                                            </form>
                                            <a href="{{ route('seller.offers.edit', $offer) }}" class="btn btn--sm">تعديل</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="panel">
            <div class="panel__head">
                <h2>أحدث الطلبات</h2>
                <a href="{{ route('seller.orders.index') }}" class="small">كل الطلبات</a>
            </div>

            @if ($recentOrders->isEmpty())
                <div class="panel__body">
                    <p class="muted small">لسه مفيش طلبات على متجرك.</p>
                </div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>الطلب</th>
                                <th>العميل</th>
                                <th>المنتجات</th>
                                <th>الإجمالي</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentOrders as $sellerOrder)
                                <tr>
                                    <td class="table__num">
                                        <a href="{{ route('seller.orders.show', $sellerOrder) }}">{{ $sellerOrder->reference }}</a>
                                    </td>
                                    <td>{{ $sellerOrder->order->customer_name }}</td>
                                    <td class="small muted">{{ $sellerOrder->items->sum('quantity') }} قطعة</td>
                                    <td class="table__num">{{ money($sellerOrder->total_cents) }}</td>
                                    <td>
                                        <x-ui.badge :tone="$sellerOrder->status->tone()">{{ $sellerOrder->status->label() }}</x-ui.badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts.dashboard>
