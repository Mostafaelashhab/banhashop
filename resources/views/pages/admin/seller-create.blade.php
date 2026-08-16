<x-layouts.dashboard heading="إضافة متجر" nav="admin">
    <div style="max-width:720px">
        <x-ui.alert tone="info" style="margin-block-end:16px">
            المتاجر الأولى بتتضاف يدويًا بالكامل. املأ البيانات وهنجهز حساب دخول للمتجر على طول —
            من غير ما التاجر يعدّي على أي خطوات تسجيل.
        </x-ui.alert>

        <form method="POST" action="{{ route('admin.sellers.store') }}">
            @csrf

            <div class="stack">
                <section class="panel">
                    <div class="panel__head"><h2>بيانات المتجر</h2></div>
                    <div class="panel__body">
                        <x-ui.field name="name" label="اسم المتجر" required />
                        <x-ui.field name="description" label="نبذة" type="textarea" />
                        <x-ui.field name="address_line" label="عنوان الفرع" required />
                        <x-ui.select-field
                            name="shipping_zone_id"
                            label="منطقة الفرع"
                            :options="$zones->pluck('name', 'id')->all()"
                            placeholder="اختر المنطقة"
                            required
                        />
                        <label class="check" style="margin-block-start:12px">
                            <input type="checkbox" name="is_verified" value="1">
                            <span>تفعيل شارة "متجر موثّق"</span>
                        </label>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel__head"><h2>حساب الدخول</h2></div>
                    <div class="panel__body">
                        <div class="field-grid field-grid--2">
                            <x-ui.field name="owner_name" label="اسم المسؤول" required />
                            <x-ui.field name="phone" label="رقم الموبايل" required inputmode="tel" placeholder="01xxxxxxxxx" />
                        </div>
                        <div class="field-grid field-grid--2">
                            <x-ui.field name="email" label="البريد الإلكتروني" type="email" required />
                            <x-ui.field name="password" label="كلمة مرور مبدئية" type="password" required
                                        hint="سلّمها للتاجر وخليه يغيرها." />
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel__head"><h2>التوصيل</h2></div>
                    <div class="panel__body">
                        <p class="field__label">المناطق اللي المتجر بيوصّلها</p>
                        <div class="field-grid field-grid--2" style="margin-block-end:14px">
                            @foreach ($zones as $zone)
                                <label class="check">
                                    <input type="checkbox" name="zones[]" value="{{ $zone->id }}">
                                    <span>{{ $zone->name }}</span>
                                </label>
                            @endforeach
                        </div>

                        <p class="field__label">شركات التوصيل</p>
                        <div class="stack-8">
                            @foreach ($providers as $provider)
                                <label class="check">
                                    <input type="checkbox" name="providers[]" value="{{ $provider->id }}">
                                    <span>{{ $provider->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </section>

                <div>
                    <button type="submit" class="btn btn--primary btn--lg">إنشاء المتجر</button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.dashboard>
