<x-layouts.app>
    <div style="max-width:760px">
        <x-layout.breadcrumbs :trail="[
            ['label' => 'الرئيسية', 'url' => route('home')],
            ['label' => 'انضم كتاجر', 'url' => route('sell')],
        ]" />

        <h1>اعرض منتجاتك على عملاء {{ config('banha.city') }}</h1>
        <p class="lead" style="margin-block-start:8px">
            من غير ما تبني موقع، ومن غير عمولات معقدة. تضيف عرضك على منتج موجود في
            الكتالوج، وتدير السعر والمخزون والطلبات من لوحة بسيطة.
        </p>

        <section class="section">
            <div class="panel">
                <div class="panel__body stack-12">
                    <div>
                        <h2 style="font-size:1rem">إزاي تبدأ</h2>
                        <ol class="stack-8" style="margin-block-start:10px">
                            <li class="row row--top" style="gap:9px">
                                <span class="step-num" aria-hidden="true">١</span>
                                <span class="small">كلمنا على {{ config('banha.support_phone') }} وهنفتح حساب المتجر بنفسنا.</span>
                            </li>
                            <li class="row row--top" style="gap:9px">
                                <span class="step-num" aria-hidden="true">٢</span>
                                <span class="small">تدخل على لوحة المتجر وتدوّر على المنتج في الكتالوج.</span>
                            </li>
                            <li class="row row--top" style="gap:9px">
                                <span class="step-num" aria-hidden="true">٣</span>
                                <span class="small">تضيف سعرك والكمية المتاحة — ودي كل الخطوات.</span>
                            </li>
                        </ol>
                    </div>

                    <p class="xsmall muted">
                        بنفتح حسابات المتاجر يدويًا في المرحلة دي عشان نتأكد من بيانات كل متجر
                        ونساعدك في أول رفع للمنتجات.
                    </p>
                </div>
            </div>
        </section>

        <section class="section">
            <h2>إيه اللي بتحصل عليه</h2>
            <ul class="highlights" style="margin-block-start:10px">
                <li><x-ui.icon name="check" :size="14" /><span>ظهور في نتائج بحث عملاء {{ config('banha.city') }} على نفس المنتج مع باقي المتاجر.</span></li>
                <li><x-ui.icon name="check" :size="14" /><span>تحكم كامل في السعر والمخزون وحالة كل عرض.</span></li>
                <li><x-ui.icon name="check" :size="14" /><span>تحديد مناطق التوصيل وشركات الشحن اللي بتتعامل معاها.</span></li>
                <li><x-ui.icon name="check" :size="14" /><span>استقبال الطلبات وقبولها أو رفضها من لوحة واحدة.</span></li>
            </ul>
        </section>

        <div class="row" style="margin-block-start:28px;gap:10px">
            <a href="tel:{{ config('banha.support_phone') }}" class="btn btn--primary">
                <x-ui.icon name="phone" :size="16" class="btn__icon" />
                كلمنا لفتح حساب المتجر
            </a>
            <a href="{{ route('login') }}" class="btn">عندك حساب متجر؟ سجّل الدخول</a>
        </div>
    </div>
</x-layouts.app>
