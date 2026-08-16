<x-layouts.dashboard heading="الطلبات" nav="admin">
    <form method="GET" class="row row--wrap" style="gap:8px;margin-block-end:16px">
        <label for="order-q" class="sr-only">ابحث</label>
        <input type="search" id="order-q" name="q" class="input" style="max-width:260px"
               placeholder="رقم الطلب، الاسم، أو الموبايل" value="{{ request('q') }}">

        <label for="order-status" class="sr-only">الحالة</label>
        <select id="order-status" name="status" class="select" style="width:auto;min-width:170px">
            <option value="">كل الحالات</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn--sm">تصفية</button>
    </form>

    @if ($orders->isEmpty())
        <x-ui.empty title="مفيش طلبات مطابقة" />
    @else
        <div class="panel">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>الطلب</th>
                            <th>العميل</th>
                            <th>المتاجر</th>
                            <th>الإجمالي</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="table__num">
                                    <a href="{{ route('admin.orders.show', $order) }}">{{ $order->number }}</a>
                                </td>
                                <td class="small">
                                    {{ $order->customer_name }}
                                    <div class="xsmall dim num">{{ $order->customer_phone }}</div>
                                </td>
                                <td class="small">
                                    {{ $order->sellerOrders->map(fn ($so) => $so->seller->name)->join('، ') }}
                                </td>
                                <td class="table__num">{{ money($order->grand_total_cents) }}</td>
                                <td><x-ui.badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-ui.badge></td>
                                <td class="small muted">{{ $order->placed_at?->translatedFormat('j M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{ $orders->links() }}
    @endif
</x-layouts.dashboard>
