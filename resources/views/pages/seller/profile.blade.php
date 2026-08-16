<x-layouts.dashboard heading="بيانات المتجر">
    <div class="split">
        <section class="panel">
            <div class="panel__body">
                <form method="POST" action="{{ route('seller.profile.update') }}">
                    @csrf @method('PUT')

                    <x-ui.field name="name" label="اسم المتجر" :value="$seller->name" required />
                    <x-ui.field name="description" label="نبذة عن المتجر" type="textarea" :value="$seller->description"
                                hint="بتظهر للعملاء في صفحة متجرك ونتائج البحث." />

                    <div class="field-grid field-grid--2">
                        <x-ui.field name="phone" label="رقم الموبايل" :value="$seller->phone" inputmode="tel" />
                        <x-ui.field name="whatsapp" label="رقم الواتساب" :value="$seller->whatsapp" inputmode="tel" />
                    </div>

                    <x-ui.field name="address_line" label="عنوان الفرع الرئيسي"
                                :value="$seller->primaryLocation?->address_line" required />
                    <x-ui.field name="landmark" label="علامة مميزة" :value="$seller->primaryLocation?->landmark" />

                    <x-ui.select-field
                        name="shipping_zone_id"
                        label="منطقة الفرع"
                        :options="$zones->pluck('name', 'id')->all()"
                        :selected="$seller->primaryLocation?->shipping_zone_id"
                        required
                    />

                    <x-ui.field name="meta_description" label="وصف صفحة المتجر لمحركات البحث"
                                type="textarea" :value="$seller->meta_description"
                                hint="سطر أو سطرين يوضحوا نوع منتجاتك ومنطقتك." />

                    <button type="submit" class="btn btn--primary" style="margin-block-start:16px">حفظ البيانات</button>
                </form>
            </div>
        </section>

        <aside class="panel">
            <div class="panel__head"><h3>حالة المتجر</h3></div>
            <div class="panel__body stack-8 small">
                <p>
                    <span class="muted">الحالة:</span>
                    <x-ui.badge :tone="$seller->status->tone()">{{ $seller->status->label() }}</x-ui.badge>
                </p>
                <p>
                    <span class="muted">التوثيق:</span>
                    {{ $seller->is_verified ? 'متجر موثّق' : 'غير موثّق' }}
                </p>
                <p class="xsmall muted">
                    التوثيق بيتم من إدارة المنصة بعد التأكد من بيانات المتجر.
                </p>
                <p><a href="{{ $seller->url() }}">عرض صفحة متجرك كما يراها العميل</a></p>
            </div>
        </aside>
    </div>
</x-layouts.dashboard>
