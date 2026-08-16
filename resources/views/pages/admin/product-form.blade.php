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
            @if ($product->exists)
                {{-- The catalog carries the product photography; a store attaches
                     an offer to this record, never its own picture of it. --}}
                <section class="panel">
                    <div class="panel__head">
                        <h3>صور المنتج</h3>
                        <span class="small muted num">{{ $product->images->count() }}</span>
                    </div>
                    <div class="panel__body stack-12">
                        @error('images')
                            <x-ui.alert tone="bad">{{ $message }}</x-ui.alert>
                        @enderror
                        @error('images.*')
                            <x-ui.alert tone="bad">{{ $message }}</x-ui.alert>
                        @enderror

                        <form method="POST" action="{{ route('admin.products.images.store', $product) }}"
                              enctype="multipart/form-data" class="stack-8">
                            @csrf
                            <label class="field__label" for="product-images">إضافة صور</label>
                            <input type="file" id="product-images" name="images[]" class="input"
                                   accept="image/jpeg,image/png,image/webp" multiple required>
                            <p class="field__hint">
                                JPG أو PNG أو WebP، بحد أقصى
                                {{ \App\Services\Catalog\ProductImageService::MAX_UPLOAD_KB / 1024 }}
                                ميجابايت للصورة. بنحوّلها WebP بمقاسات متعددة تلقائيًا.
                            </p>
                            <button type="submit" class="btn btn--primary btn--sm">رفع</button>
                        </form>

                        @forelse ($product->images as $image)
                            <div class="img-manager__item">
                                <img src="{{ asset('storage/'.$image->path) }}" alt=""
                                     width="64" height="64" class="img-manager__thumb"
                                     loading="lazy" decoding="async">

                                <div class="img-manager__body">
                                    @if ($product->image_path === $image->path)
                                        <x-ui.badge tone="success" icon="check">الصورة الرئيسية</x-ui.badge>
                                    @endif

                                    <form method="POST"
                                          action="{{ route('admin.products.images.update', [$product, $image]) }}"
                                          class="stack-8">
                                        @csrf @method('PATCH')
                                        <label class="sr-only" for="alt-{{ $image->id }}">النص البديل</label>
                                        <input type="text" id="alt-{{ $image->id }}" name="alt" class="input"
                                               value="{{ $image->alt }}" maxlength="180"
                                               placeholder="وصف الصورة لقارئ الشاشة ومحركات البحث">

                                        {{-- Required by CC BY and similar licences; leave empty for
                                             our own photography. Shown on the product page. --}}
                                        <label class="sr-only" for="credit-{{ $image->id }}">مصدر الصورة</label>
                                        <input type="text" id="credit-{{ $image->id }}" name="credit" class="input"
                                               value="{{ $image->credit }}" maxlength="180"
                                               placeholder="مصدر الصورة والترخيص — سيبها فاضية لو الصورة بتاعتنا">
                                        <div class="row row--wrap" style="gap:6px">
                                            <button type="submit" class="btn btn--sm">حفظ الوصف</button>
                                            @if ($product->image_path !== $image->path)
                                                <button type="submit" name="cover" value="1" class="btn btn--sm">
                                                    اجعلها الرئيسية
                                                </button>
                                            @endif
                                        </div>
                                    </form>

                                    <p class="xsmall dim num">{{ $image->width }}×{{ $image->height }}</p>
                                </div>

                                <form method="POST"
                                      action="{{ route('admin.products.images.destroy', [$product, $image]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-btn" aria-label="حذف الصورة">
                                        <x-ui.icon name="trash" :size="17" />
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="small muted">
                                لسه مفيش صور. المنتج من غير صورة بيظهر بمربّع رمادي في الكتالوج.
                            </p>
                        @endforelse
                    </div>
                </section>
            @endif

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
