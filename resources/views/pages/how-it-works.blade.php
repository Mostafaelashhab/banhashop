<x-layouts.app>
    <div style="max-width:760px">
        <x-layout.breadcrumbs :trail="[
            ['label' => 'الرئيسية', 'url' => route('home')],
            ['label' => 'كيف تعمل المنصة', 'url' => route('pages.how-it-works')],
        ]" />

        <h1>كيف تعمل بنها شوب</h1>
        <p class="lead" style="margin-block-start:8px">
            بنها شوب مش متجر إلكتروني، ومش سوق عشوائي. إحنا كتالوج واحد لمنتجات
            {{ config('banha.city') }}، وكل متجر محلي بيضيف عرضه على نفس المنتج.
        </p>

        <section class="section">
            <h2>منتج واحد، عروض متعددة</h2>
            <p class="muted" style="margin-block-start:8px">
                المنتج بيتسجل مرة واحدة في الكتالوج المركزي. المتاجر مش بتنشئ منتجات مكررة —
                بتضيف عرض على المنتج الموجود بسعرها ومخزونها وحالة المنتج عندها.
                كده مفيش "آيفون 17 برو" مكتوب بعشر طرق مختلفة.
            </p>
        </section>

        <section class="section">
            <h2>السعر النهائي هو اللي يهم</h2>
            <p class="muted" style="margin-block-start:8px">
                أرخص سعر منتج مش دايمًا أرخص صفقة. متجر بيبيع بـ 920 جنيه وتوصيله 80
                أغلى فعليًا من متجر بيبيع بـ 950 وتوصيله 30. عشان كده كل عرض بيتعرض
                بثلاث أرقام واضحة: سعر المنتج، تكلفة التوصيل لمنطقتك، والإجمالي.
            </p>
        </section>

        <section class="section">
            <h2>ترتيب العروض شفاف</h2>
            <p class="muted" style="margin-block-start:8px">
                الترتيب الافتراضي بيبدأ بأقل إجمالي. لو فيه تعادل، الأسرع توصيلًا بيتقدّم،
                وبعدين الأحدث تحديثًا للمخزون. تقدر تغيّر الترتيب لأقل سعر منتج أو أسرع توصيل
                في أي وقت. مفيش عرض بيتقدّم لأسباب خفية.
            </p>
        </section>

        <section class="section">
            <h2>المخزون بتاريخ</h2>
            <p class="muted" style="margin-block-start:8px">
                كل عرض بيوضح آخر مرة المتجر حدّث فيها المخزون. لو التحديث بقى له فترة طويلة
                بنقول ده صراحة بدل ما نعرض رقم مش متأكدين منه.
            </p>
        </section>

        <section class="section">
            <h2>الطلب والدفع</h2>
            <p class="muted" style="margin-block-start:8px">
                لو طلبت من أكتر من متجر، الطلب بينقسم لشحنة لكل متجر — كل متجر بيأكد طلبه
                ويجهّزه. الدفع حاليًا عند الاستلام.
            </p>
        </section>

        <div class="row" style="margin-block-start:28px;gap:10px">
            <a href="{{ route('products.index') }}" class="btn btn--primary">ابدأ التصفح</a>
            <a href="{{ route('sell') }}" class="btn">عندك متجر؟ انضم لنا</a>
        </div>
    </div>
</x-layouts.app>
