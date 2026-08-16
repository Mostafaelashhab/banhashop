<x-layouts.dashboard heading="إضافة عرض">
    {{-- Step 1 — find the product in the shared catalog. Sellers never create
         catalog entries directly; that is what keeps duplicates out. --}}
    <section class="panel" style="margin-block-end:16px">
        <div class="panel__head"><h2>١. دوّر على المنتج في الكتالوج</h2></div>
        <div class="panel__body">
            <form method="GET" action="{{ route('seller.offers.create') }}" class="row" style="gap:8px">
                <label for="catalog-q" class="sr-only">ابحث في الكتالوج</label>
                <input type="search" id="catalog-q" name="q" class="input"
                       placeholder="اسم المنتج، الماركة، أو الباركود" value="{{ $term }}">
                <button type="submit" class="btn btn--primary">بحث</button>
            </form>

            @if ($term !== '')
                @if ($results->isEmpty())
                    <div style="margin-block-start:16px">
                        <x-ui.alert tone="warn" title="المنتج ده مش في الكتالوج">
                            ابعته لفريق الكتالوج للمراجعة، وأول ما يتوافق عليه هتقدر تضيف عرضك عليه.
                        </x-ui.alert>

                        <details style="margin-block-start:12px">
                            <summary class="strong small" style="cursor:pointer">طلب إضافة منتج جديد</summary>
                            <form method="POST" action="{{ route('seller.catalog.request') }}" style="margin-block-start:12px">
                                @csrf
                                <x-ui.field name="name" label="اسم المنتج" :value="$term" required />
                                <x-ui.select-field
                                    name="category_id"
                                    label="القسم"
                                    :options="$categories"
                                    placeholder="اختر القسم"
                                    required
                                />
                                <div class="field-grid field-grid--2">
                                    <x-ui.field name="model" label="الموديل" />
                                    <x-ui.field name="barcode" label="الباركود" inputmode="numeric" />
                                </div>
                                <x-ui.field name="description" label="وصف مختصر" type="textarea" />
                                <button type="submit" class="btn btn--primary" style="margin-block-start:12px">
                                    إرسال للمراجعة
                                </button>
                            </form>
                        </details>
                    </div>
                @else
                    <ul class="stack-8" style="margin-block-start:16px">
                        @foreach ($results as $result)
                            <li class="row row--between row--wrap"
                                style="gap:10px;padding:10px 12px;border:1px solid var(--line);border-radius:var(--radius)">
                                <div>
                                    <p class="strong small">{{ $result->displayName() }}</p>
                                    <p class="xsmall muted">
                                        {{ $result->brand?->name }}
                                        @if ($result->offers_count)
                                            — {{ $result->sellers_count }} متجر يعرضه، يبدأ من {{ money($result->min_price_cents) }}
                                        @else
                                            — لسه مفيش عروض عليه
                                        @endif
                                    </p>
                                </div>
                                <a href="{{ route('seller.offers.create', ['product' => $result->slug, 'q' => $term]) }}"
                                   class="btn btn--sm">اختر</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </div>
    </section>

    {{-- Step 2 — price it. --}}
    @if ($product)
        <section class="panel">
            <div class="panel__head"><h2>٢. حدد سعرك والكمية</h2></div>
            <div class="panel__body">
                <div class="row" style="gap:12px;padding-block-end:14px;border-block-end:1px solid var(--line-2);margin-block-end:16px">
                    <div style="width:56px;height:56px;flex:none">
                        <x-product.thumb :product="$product" :size="56" class="cart-line__media" />
                    </div>
                    <div>
                        <p class="strong">{{ $product->displayName() }}</p>
                        <p class="xsmall muted">
                            @if ($product->offers_count)
                                أقل سعر معروض حاليًا {{ money($product->min_price_cents) }} من {{ $product->sellers_count }} متجر
                            @else
                                هتكون أول متجر يعرض المنتج ده
                            @endif
                        </p>
                    </div>
                </div>

                @if ($existing)
                    <x-ui.alert tone="warn">
                        عندك عرض على المنتج ده بالفعل.
                        <a href="{{ route('seller.offers.edit', $existing) }}">افتحه للتعديل</a>.
                    </x-ui.alert>
                @else
                    <form method="POST" action="{{ route('seller.offers.store') }}">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        <div class="field-grid field-grid--2">
                            <x-ui.field name="price" label="سعرك (ج.م)" type="number" step="0.01" required inputmode="decimal" />
                            <x-ui.field name="compare_at_price" label="السعر قبل الخصم (ج.م)" type="number" step="0.01"
                                        hint="اختياري — يظهر مشطوبًا بجوار سعرك." />
                        </div>

                        <div class="field-grid field-grid--2">
                            <x-ui.field name="stock" label="الكمية المتاحة" type="number" value="1" required inputmode="numeric" />
                            <x-ui.select-field
                                name="condition"
                                label="حالة المنتج"
                                :options="collect($conditions)->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()"
                                selected="new"
                                required
                            />
                        </div>

                        <div class="field-grid field-grid--2">
                            <x-ui.field name="sku" label="كود المنتج عندك (SKU)" hint="اختياري — للاستخدام الداخلي." />
                            <x-ui.field name="note" label="ملاحظة للعميل" hint="اختياري — مثال: ضمان الوكيل سنة." />
                        </div>

                        <button type="submit" class="btn btn--primary btn--lg" style="margin-block-start:16px">
                            نشر العرض
                        </button>
                    </form>
                @endif
            </div>
        </section>
    @endif
</x-layouts.dashboard>
