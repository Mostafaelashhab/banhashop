<x-layouts.dashboard heading="الشحن والمناطق" nav="admin">
    <div class="stack">
        <x-ui.alert tone="info">
            الأسعار هنا هي أسعار المنصة العامة لكل شركة توصيل في كل منطقة.
            أسعار "توصيل المتجر" بيحددها كل متجر من لوحته، وبتغلب السعر العام.
        </x-ui.alert>

        <div class="split">
            <div class="stack-12">
                @foreach ($providers as $provider)
                    <section class="panel">
                        <div class="panel__head">
                            <div>
                                <h2>{{ $provider->name }}</h2>
                                <p class="xsmall muted">{{ $provider->type->label() }}</p>
                            </div>
                            @if (! $provider->is_active)
                                <x-ui.badge tone="warning">غير مفعّل</x-ui.badge>
                            @endif
                        </div>

                        @php $providerRates = $rates->get($provider->id, collect()); @endphp

                        @if ($provider->isSellerOwned())
                            <div class="panel__body">
                                <p class="small muted">
                                    شركة توصيل تخص المتاجر — الأسعار بتتحدد من لوحة كل متجر.
                                </p>
                            </div>
                        @elseif ($providerRates->isEmpty())
                            <div class="panel__body">
                                <p class="small muted">مفيش أسعار محددة لهذه الشركة بعد.</p>
                            </div>
                        @else
                            <div class="table-wrap">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>المنطقة</th>
                                            <th>السعر</th>
                                            <th>مجاني فوق</th>
                                            <th>المدة</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($providerRates as $rate)
                                            <tr>
                                                <td>{{ $rate->zone->name }}</td>
                                                <td class="table__num">{{ money($rate->price_cents) }}</td>
                                                <td class="table__num">
                                                    {{ $rate->free_over_cents ? money($rate->free_over_cents) : '—' }}
                                                </td>
                                                <td class="small">{{ $rate->eta_min_hours }}–{{ $rate->eta_max_hours }} ساعة</td>
                                                <td>
                                                    <form method="POST" action="{{ route('admin.shipping.rates.destroy', $rate) }}">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="icon-btn" aria-label="حذف السعر">
                                                            <x-ui.icon name="trash" :size="16" />
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                @endforeach
            </div>

            <aside class="stack-12">
                <section class="panel">
                    <div class="panel__head"><h2>إضافة سعر توصيل</h2></div>
                    <div class="panel__body">
                        <form method="POST" action="{{ route('admin.shipping.rates.store') }}">
                            @csrf
                            <x-ui.select-field name="shipping_provider_id" label="شركة التوصيل"
                                               :options="$providers->pluck('name', 'id')->all()"
                                               placeholder="اختر الشركة" required />
                            <x-ui.select-field name="shipping_zone_id" label="المنطقة"
                                               :options="$zones->pluck('name', 'id')->all()"
                                               placeholder="اختر المنطقة" required />
                            <x-ui.field name="price" label="السعر (ج.م)" type="number" step="0.01" required />
                            <x-ui.field name="free_over" label="مجاني فوق (ج.م)" type="number" step="0.01" />
                            <div class="field-grid field-grid--2">
                                <x-ui.field name="eta_min_hours" label="أقل مدة (ساعة)" type="number" value="24" required />
                                <x-ui.field name="eta_max_hours" label="أقصى مدة (ساعة)" type="number" value="48" required />
                            </div>
                            <x-ui.field name="same_day_cutoff" label="آخر موعد للطلب في نفس اليوم" type="time"
                                        hint="اختياري — بعده بنحسب التسليم من اليوم التالي." />
                            <button type="submit" class="btn btn--primary" style="margin-block-start:14px">حفظ السعر</button>
                        </form>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel__head"><h2>المناطق</h2></div>
                    <div class="panel__body">
                        <ul class="stack-8 small" style="margin-block-end:14px">
                            @foreach ($zones as $zone)
                                <li class="row row--between">
                                    <span>{{ $zone->name }}</span>
                                    @if (! $zone->is_active)
                                        <x-ui.badge tone="warning">موقوفة</x-ui.badge>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        <details>
                            <summary class="small strong" style="cursor:pointer">إضافة منطقة</summary>
                            <form method="POST" action="{{ route('admin.shipping.zones.store') }}" style="margin-block-start:12px">
                                @csrf
                                <x-ui.field name="name" label="اسم المنطقة" required />
                                <x-ui.field name="description" label="وصف" type="textarea" />
                                <x-ui.field name="position" label="الترتيب" type="number" value="0" />
                                <label class="check" style="margin-block-start:10px">
                                    <input type="checkbox" name="is_active" value="1" checked>
                                    <span>نشطة</span>
                                </label>
                                <button type="submit" class="btn btn--sm btn--primary" style="margin-block-start:12px">إضافة</button>
                            </form>
                        </details>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel__head"><h2>إضافة شركة توصيل</h2></div>
                    <div class="panel__body">
                        <form method="POST" action="{{ route('admin.shipping.providers.store') }}">
                            @csrf
                            <x-ui.field name="name" label="اسم الشركة" required />
                            <x-ui.select-field name="type" label="النوع"
                                               :options="collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()"
                                               selected="third_party" required />
                            <x-ui.field name="description" label="وصف مختصر" />
                            <label class="check" style="margin-block-start:10px">
                                <input type="checkbox" name="is_active" value="1" checked>
                                <span>مفعّلة</span>
                            </label>
                            <button type="submit" class="btn btn--sm btn--primary" style="margin-block-start:12px">إضافة</button>
                        </form>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.dashboard>
