<x-layouts.app>
    <div style="max-width:620px;margin-inline:auto">
        <header class="stack-8" style="margin-block-end:20px">
            <h1>اطلب منتجًا غير متوفر</h1>
            <p class="lead">
                لو مالقيتش المنتج في متاجر {{ config('banha.city') }}، اكتبه هنا.
                إحنا بنستخدم الطلبات دي فعليًا عشان نقنع المتاجر المحلية توفّره.
            </p>
        </header>

        <div class="panel">
            <div class="panel__body">
                <form method="POST" action="{{ route('product-requests.store') }}">
                    @csrf

                    <x-ui.field
                        name="query_text"
                        label="اسم المنتج"
                        :value="$query"
                        required
                        placeholder="مثال: سماعات AirPods Pro"
                    />

                    <x-ui.field
                        name="note"
                        label="تفاصيل إضافية"
                        type="textarea"
                        hint="اختياري — اللون، الحجم، السعة، أو الماركة المفضلة."
                    />

                    <x-ui.select-field
                        name="shipping_zone_id"
                        label="منطقتك"
                        :options="$zones->pluck('name', 'id')->all()"
                        :selected="$currentZone?->id"
                        hint="بيساعدنا نعرف المنطقة اللي عليها طلب."
                    />

                    <x-ui.field
                        name="contact_phone"
                        label="رقم الموبايل"
                        inputmode="tel"
                        placeholder="01xxxxxxxxx"
                        hint="اختياري — عشان نبلغك أول ما يتوفر."
                    />

                    <button type="submit" class="btn btn--primary btn--lg btn--block" style="margin-block-start:16px">
                        أرسل الطلب
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
