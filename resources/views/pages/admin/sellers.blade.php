<x-layouts.dashboard heading="المتاجر" nav="admin">
    <x-slot:actions>
        <a href="{{ route('admin.sellers.create') }}" class="btn btn--primary btn--sm">
            <x-ui.icon name="plus" :size="15" class="btn__icon" />
            إضافة متجر
        </a>
    </x-slot:actions>

    <form method="GET" class="row" style="gap:8px;margin-block-end:16px">
        <label for="seller-status" class="sr-only">حالة المتجر</label>
        <select id="seller-status" name="status" class="select" style="width:auto;min-width:160px">
            <option value="">كل الحالات</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn--sm">تصفية</button>
    </form>

    @if ($sellers->isEmpty())
        <x-ui.empty title="مفيش متاجر" text="ابدأ بإضافة أول متجر محلي يدويًا.">
            <x-slot:action>
                <a href="{{ route('admin.sellers.create') }}" class="btn btn--primary">إضافة متجر</a>
            </x-slot:action>
        </x-ui.empty>
    @else
        <div class="panel">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>المتجر</th>
                            <th>المسؤول</th>
                            <th>المنطقة</th>
                            <th>عروض نشطة</th>
                            <th>الطلبات</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sellers as $seller)
                            <tr>
                                <td>
                                    <a href="{{ $seller->url() }}" class="strong">{{ $seller->name }}</a>
                                    @if ($seller->is_verified)
                                        <x-ui.badge tone="info">موثّق</x-ui.badge>
                                    @endif
                                </td>
                                <td class="small">
                                    {{ $seller->user?->name }}
                                    <div class="xsmall dim num">{{ $seller->user?->phone }}</div>
                                </td>
                                <td class="small">{{ $seller->primaryLocation?->zone?->name ?? '—' }}</td>
                                <td class="table__num">{{ $seller->active_offers_count }}</td>
                                <td class="table__num">
                                    {{ $seller->orders_count }}
                                    @if ($seller->acceptanceRate() !== null)
                                        <div class="xsmall dim">قبول {{ $seller->acceptanceRate() }}%</div>
                                    @endif
                                </td>
                                <td><x-ui.badge :tone="$seller->status->tone()">{{ $seller->status->label() }}</x-ui.badge></td>
                                <td>
                                    <div class="table__actions">
                                        <a href="{{ route('admin.sellers.edit', $seller) }}" class="btn btn--sm">تعديل</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{ $sellers->links() }}
    @endif
</x-layouts.dashboard>
