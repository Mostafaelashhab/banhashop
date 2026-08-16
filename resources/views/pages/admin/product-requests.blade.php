<x-layouts.dashboard heading="طلبات المنتجات" nav="admin">
    <x-ui.alert tone="info" style="margin-block-end:16px">
        دي طلبات عملاء على منتجات مش موجودة في الكتالوج أو مفيش عليها عروض.
        استخدمها كقائمة أولويات وأنت بتكلم متاجر {{ config('banha.city') }}.
    </x-ui.alert>

    <form method="GET" class="row" style="gap:8px;margin-block-end:16px">
        <label for="req-status" class="sr-only">الحالة</label>
        <select id="req-status" name="status" class="select" style="width:auto;min-width:160px">
            <option value="">كل الحالات</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>
                    {{ \App\Models\ProductRequest::statusLabel($status) }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn btn--sm">تصفية</button>
    </form>

    @if ($grouped->isEmpty())
        <x-ui.empty title="مفيش طلبات" text="أول ما عميل يدور على منتج مش موجود، هيظهر هنا." />
    @else
        <div class="panel">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>المنتج المطلوب</th>
                            <th>عدد الطلبات</th>
                            <th>المناطق</th>
                            <th>آخر طلب</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($grouped as $row)
                            <tr>
                                <td class="strong">{{ $row->query_text }}</td>
                                <td class="table__num">{{ $row->requests }}</td>
                                <td class="small muted">
                                    @php $zones = $zonesByKey->get($row->normalized_key); @endphp
                                    @if ($zones && $zones->isNotEmpty())
                                        {{ $zones->map(fn ($count, $name) => "{$name} ({$count})")->join('، ') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="small muted">
                                    {{ \Illuminate\Support\Carbon::parse($row->last_requested)->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{ $grouped->links() }}
    @endif
</x-layouts.dashboard>
