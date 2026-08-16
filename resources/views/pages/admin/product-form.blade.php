<x-layouts.dashboard :heading="$product->exists ? 'تعديل منتج' : 'إضافة منتج للكتالوج'" nav="admin">
    <div class="split">
        <section class="panel">
            <div class="panel__body">
                <form method="POST"
                      action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
                    @csrf
                    @if ($product->exists)
                        @method('PUT')
                    @endif

                    <x-ui.field name="name" label="اسم المنتج" :value="$product->name" required
                                hint="الاسم الرسمي للموديل — من غير اسم المتجر أو كلمات دعائية." />
                    <x-ui.field name="variant_label" label="الإصدار" :value="$product->variant_label"
                                placeholder="256 جيجا / أسود" hint="اللي بيميّز النسخة دي عن باقي نسخ نفس الموديل." />

                    <div class="field-grid field-grid--2">
                        <x-ui.select-field name="category_id" label="القسم" :options="$categories"
                                           :selected="$product->category_id" placeholder="اختر القسم" required />
                        <x-ui.select-field name="brand_id" label="الماركة" :options="$brands"
                                           :selected="$product->brand_id" placeholder="بدون ماركة" />
                    </div>

                    <div class="field-grid field-grid--3">
                        <x-ui.field name="model" label="الموديل" :value="$product->model" />
                        <x-ui.field name="mpn" label="MPN" :value="$product->mpn" />
                        <x-ui.field name="barcode" label="الباركود" :value="$product->barcode" inputmode="numeric" />
                    </div>

                    <x-ui.field name="description" label="الوصف" type="textarea" :value="$product->description" />

                    <x-ui.select-field
                        name="status"
                        label="حالة المنتج"
                        :options="collect(\App\Enums\ProductStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()"
                        :selected="$product->status?->value ?? 'draft'"
                        required
                    />

                    <hr>

                    <p class="field__label">تحسين محركات البحث</p>
                    <x-ui.field name="meta_title" label="عنوان الصفحة" :value="$product->meta_title"
                                hint="اتركه فاضي وهنستخدم اسم المنتج." />
                    <x-ui.field name="meta_description" label="وصف الصفحة" type="textarea"
                                :value="$product->meta_description" />

                    <button type="submit" class="btn btn--primary" style="margin-block-start:16px">
                        {{ $product->exists ? 'حفظ التعديلات' : 'إضافة المنتج' }}
                    </button>
                </form>
            </div>
        </section>

        <aside class="stack-12">
            @if ($product->exists && $product->status === \App\Enums\ProductStatus::Pending)
                <section class="panel">
                    <div class="panel__head"><h3>مراجعة الطلب</h3></div>
                    <div class="panel__body stack-8">
                        <p class="small muted">
                            المنتج ده مقدَّم من متجر. راجع البيانات وصححها قبل الاعتماد عشان الكتالوج يفضل نظيف.
                        </p>

                        <form method="POST" action="{{ route('admin.products.review', $product) }}">
                            @csrf
                            <input type="hidden" name="decision" value="approve">
                            <button type="submit" class="btn btn--primary btn--block">اعتماد ونشر</button>
                        </form>

                        <form method="POST" action="{{ route('admin.products.review', $product) }}">
                            @csrf
                            <input type="hidden" name="decision" value="reject">
                            <x-ui.field name="rejection_reason" label="سبب الرفض" />
                            <button type="submit" class="btn btn--danger btn--block" style="margin-block-start:8px">رفض</button>
                        </form>
                    </div>
                </section>
            @endif

            @if ($product->exists)
                <section class="panel">
                    <div class="panel__head"><h3>حالة السوق</h3></div>
                    <div class="panel__body small stack-8">
                        <p><span class="muted">عدد العروض:</span> {{ $product->offers_count }}</p>
                        <p><span class="muted">عدد المتاجر:</span> {{ $product->sellers_count }}</p>
                        <p>
                            <span class="muted">نطاق الأسعار:</span>
                            {{ $product->min_price_cents ? money($product->min_price_cents).' — '.money($product->max_price_cents) : '—' }}
                        </p>
                        @if ($product->isPublished())
                            <p><a href="{{ $product->url() }}">عرض الصفحة العامة</a></p>
                        @endif
                    </div>
                </section>
            @endif
        </aside>
    </div>
</x-layouts.dashboard>
