<x-layouts.dashboard heading="تعديل العرض">
    <div class="split">
        <section class="panel">
            <div class="panel__head">
                <h2>{{ $offer->product->displayName() }}</h2>
                <a href="{{ route('products.show', $offer->product->slug) }}" class="small">عرض الصفحة</a>
            </div>
            <div class="panel__body">
                <form method="POST" action="{{ route('seller.offers.update', $offer) }}">
                    @csrf @method('PUT')

                    <div class="field-grid field-grid--2">
                        <x-ui.field name="price" label="السعر (ج.م)" type="number" step="0.01"
                                    :value="\App\Support\Money::decimal($offer->price_cents)" required inputmode="decimal" />
                        <x-ui.field name="compare_at_price" label="السعر قبل الخصم (ج.م)" type="number" step="0.01"
                                    :value="$offer->compare_at_price_cents ? \App\Support\Money::decimal($offer->compare_at_price_cents) : null" />
                    </div>

                    <div class="field-grid field-grid--2">
                        <x-ui.field name="stock" label="الكمية المتاحة" type="number" :value="$offer->stock" required inputmode="numeric" />
                        <x-ui.select-field
                            name="status"
                            label="حالة العرض"
                            :options="collect(\App\Enums\OfferStatus::sellerSelectable())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()"
                            :selected="$offer->status->value"
                            hint="لو الكمية صفر، العرض بيتحول تلقائيًا إلى غير متوفر."
                            required
                        />
                    </div>

                    <div class="field-grid field-grid--2">
                        <x-ui.field name="sku" label="كود المنتج عندك (SKU)" :value="$offer->sku" />
                        <x-ui.field name="note" label="ملاحظة للعميل" :value="$offer->note" />
                    </div>

                    <div class="row" style="gap:8px;margin-block-start:16px">
                        <button type="submit" class="btn btn--primary">حفظ التعديلات</button>
                        <a href="{{ route('seller.offers.index') }}" class="btn">رجوع</a>
                    </div>
                </form>

                <hr>

                <form method="POST" action="{{ route('seller.offers.destroy', $offer) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn--danger btn--sm">حذف العرض نهائيًا</button>
                    <p class="field__hint">لو المنتج خلص مؤقتًا، الأفضل توقف العرض بدل ما تحذفه.</p>
                </form>
            </div>
        </section>

        <aside class="stack-12">
            <section class="panel">
                <div class="panel__head"><h3>حالة المخزون</h3></div>
                <div class="panel__body stack-8">
                    <x-offer.freshness :offer="$offer" />
                    <form method="POST" action="{{ route('seller.offers.confirm', $offer) }}">
                        @csrf
                        <button type="submit" class="btn btn--block">
                            <x-ui.icon name="check" :size="15" class="btn__icon" />
                            المخزون لسه صحيح
                        </button>
                    </form>
                    <p class="xsmall muted">
                        التأكيد بيحدّث الوقت اللي بيظهر للعميل من غير ما يغيّر أي رقم.
                    </p>
                </div>
            </section>

            {{-- Real history, not a decorative activity feed. --}}
            <section class="panel">
                <div class="panel__head"><h3>سجل التغييرات</h3></div>
                <div class="panel__body">
                    @if ($logs->isEmpty())
                        <p class="small muted">مفيش تغييرات مسجلة.</p>
                    @else
                        <ol class="timeline">
                            @foreach ($logs as $log)
                                <li class="timeline__item timeline__item--done">
                                    <p class="timeline__title small">
                                        @if ($log->stock_before !== $log->stock_after)
                                            المخزون: {{ $log->stock_before }} ← {{ $log->stock_after }}
                                        @elseif ($log->price_cents_before !== $log->price_cents_after)
                                            السعر: {{ money($log->price_cents_before) }} ← {{ money($log->price_cents_after) }}
                                        @else
                                            تأكيد المخزون
                                        @endif
                                    </p>
                                    <p class="timeline__time">{{ $log->created_at?->diffForHumans() }}</p>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</x-layouts.dashboard>
