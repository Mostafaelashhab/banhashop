<x-layouts.dashboard heading="مؤشرات المنصة" nav="admin">
    <div class="stack">
        {{-- The headline metric is competition per product, not signups. --}}
        <section class="panel">
            <div class="panel__head">
                <h2>المنافسة على المنتجات</h2>
                <span class="small muted num">{{ $productsWithOffers }} / {{ $productsTotal }} منتج عليه عرض</span>
            </div>
            <div class="panel__body">
                <div class="stat-row">
                    <div class="stat">
                        <p class="stat__label">بدون عروض</p>
                        <p class="stat__value">{{ $competition['none'] }}</p>
                        <p class="stat__note">فرصة لضم متاجر</p>
                    </div>
                    <div class="stat">
                        <p class="stat__label">متجر واحد</p>
                        <p class="stat__value">{{ $competition['one'] }}</p>
                        <p class="stat__note">لا توجد مقارنة بعد</p>
                    </div>
                    <div class="stat">
                        <p class="stat__label">متجران</p>
                        <p class="stat__value">{{ $competition['two'] }}</p>
                        <p class="stat__note">بداية منافسة حقيقية</p>
                    </div>
                    <div class="stat">
                        <p class="stat__label">٣ متاجر فأكثر</p>
                        <p class="stat__value">{{ $competition['three_plus'] }}</p>
                        <p class="stat__note">المقارنة هنا أقوى قيمة</p>
                    </div>
                </div>

                <p class="small muted" style="margin-block-start:14px">
                    هدف النمو هو تحريك المنتجات من اليسار لليمين في الجدول ده — مش زيادة عدد المنتجات وحدها.
                </p>
            </div>
        </section>

        <div class="stat-row">
            <div class="stat">
                <p class="stat__label">متاجر نشطة</p>
                <p class="stat__value">{{ $activeSellers }}</p>
                @if ($pendingSellers)
                    <p class="stat__note">{{ $pendingSellers }} بانتظار التفعيل</p>
                @endif
            </div>
            <div class="stat">
                <p class="stat__label">طلبات مفتوحة</p>
                <p class="stat__value">{{ $openSellerOrders }}</p>
            </div>
            <div class="stat">
                <p class="stat__label">طلبات هذا الشهر</p>
                <p class="stat__value">{{ $ordersThisMonth }}</p>
                <p class="stat__note num">{{ money($revenueThisMonth) }}</p>
            </div>
            <div class="stat">
                <p class="stat__label">نسبة قبول المتاجر</p>
                <p class="stat__value">{{ $acceptanceRate !== null ? $acceptanceRate.'%' : '—' }}</p>
                <p class="stat__note">
                    {{ $acceptanceRate === null ? 'بيانات غير كافية بعد' : 'محسوبة من طلبات حقيقية' }}
                </p>
            </div>
        </div>

        @if ($pendingProducts > 0)
            <x-ui.alert tone="warn">
                فيه {{ $pendingProducts }} منتج مقدَّم من المتاجر بانتظار مراجعة الكتالوج.
                <a href="{{ route('admin.products.index', ['status' => 'pending']) }}">راجعهم الآن</a>.
            </x-ui.alert>
        @endif

        {{-- Demand with no supply: the seller-acquisition worklist. --}}
        <section class="panel">
            <div class="panel__head">
                <h2>أكثر المنتجات طلبًا وغير متوفرة</h2>
                <a href="{{ route('admin.product-requests.index') }}" class="small">القائمة كاملة</a>
            </div>

            @if ($topRequests->isEmpty())
                <div class="panel__body"><p class="small muted">مفيش طلبات مفتوحة.</p></div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr><th>المنتج المطلوب</th><th>عدد الطلبات</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($topRequests as $request)
                                <tr>
                                    <td>{{ $request->query_text }}</td>
                                    <td class="table__num strong">{{ $request->requests }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts.dashboard>
