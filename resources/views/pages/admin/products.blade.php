<x-layouts.dashboard heading="الكتالوج" nav="admin">
    <x-slot:actions>
        <a href="{{ route('admin.products.create') }}" class="btn btn--primary btn--sm">
            <x-ui.icon name="plus" :size="15" class="btn__icon" />
            إضافة منتج
        </a>
    </x-slot:actions>

    @if ($pendingCount > 0 && request('status') !== 'pending')
        <x-ui.alert tone="warn" style="margin-block-end:16px">
            {{ $pendingCount }} منتج مقدَّم من المتاجر بانتظار المراجعة.
            <a href="{{ route('admin.products.index', ['status' => 'pending']) }}">اعرضهم</a>.
        </x-ui.alert>
    @endif

    @if ($withoutImageCount > 0 && ! request()->boolean('no_image'))
        <x-ui.alert tone="info" style="margin-block-end:16px">
            {{ $withoutImageCount }} منتج من غير صورة — بيظهروا في الكتالوج بمربّع فاضي.
            <a href="{{ route('admin.products.index', ['no_image' => 1]) }}">اعرضهم</a>.
        </x-ui.alert>
    @endif

    <form method="GET" class="row row--wrap" style="gap:8px;margin-block-end:16px">
        <label for="prod-q" class="sr-only">ابحث</label>
        <input type="search" id="prod-q" name="q" class="input" style="max-width:260px"
               placeholder="اسم المنتج" value="{{ request('q') }}">

        <label for="prod-status" class="sr-only">الحالة</label>
        <select id="prod-status" name="status" class="select" style="width:auto;min-width:170px">
            <option value="">كل الحالات</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>

        <label class="check">
            <input type="checkbox" name="no_image" value="1" @checked(request()->boolean('no_image'))>
            <span>من غير صورة</span>
        </label>

        <button type="submit" class="btn btn--sm">تصفية</button>
    </form>

    @if ($products->isEmpty())
        <x-ui.empty title="مفيش منتجات مطابقة" />
    @else
        <div class="panel">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>القسم</th>
                            <th>الماركة</th>
                            <th>العروض</th>
                            <th>أقل سعر</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>
                                    <span class="strong">{{ $product->displayName() }}</span>
                                    @unless ($product->image_path)
                                        <x-ui.badge tone="outline">بدون صورة</x-ui.badge>
                                    @endunless
                                    @if ($product->rejection_reason)
                                        <div class="xsmall" style="color:var(--bad)">{{ $product->rejection_reason }}</div>
                                    @endif
                                </td>
                                <td class="small">{{ $product->category?->name }}</td>
                                <td class="small">{{ $product->brand?->name ?? '—' }}</td>
                                <td class="table__num">
                                    {{ $product->offers_count }}
                                    <span class="xsmall dim">({{ $product->sellers_count }} متجر)</span>
                                </td>
                                <td class="table__num">{{ $product->min_price_cents ? money($product->min_price_cents) : '—' }}</td>
                                <td><x-ui.badge :tone="$product->status->tone()">{{ $product->status->label() }}</x-ui.badge></td>
                                <td>
                                    <div class="table__actions">
                                        @if ($product->status === \App\Enums\ProductStatus::Pending)
                                            <form method="POST" action="{{ route('admin.products.review', $product) }}">
                                                @csrf
                                                <input type="hidden" name="decision" value="approve">
                                                <button type="submit" class="btn btn--sm btn--primary">اعتماد</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn--sm">تعديل</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{ $products->links() }}
    @endif
</x-layouts.dashboard>
