@props(['categories' => null])

<footer class="site-footer">
    <div class="container">
        <div class="site-footer__grid">
            <div>
                <div class="brand" style="margin-block-end:10px">
                    <span class="brand__mark" aria-hidden="true">ب</span>
                    <span class="brand__name">بنها شوب</span>
                </div>
                <p class="small muted" style="max-width:38ch">
                    منصة محلية تجمع عروض متاجر {{ config('banha.city') }} في مكان واحد، وتوضح السعر النهائي شامل التوصيل قبل الطلب.
                </p>
            </div>

            <div>
                <h2 class="site-footer__title">التسوق</h2>
                <ul class="site-footer__list">
                    <li><a href="{{ route('products.index') }}">كل المنتجات</a></li>
                    <li><a href="{{ route('stores.index') }}">المتاجر</a></li>
                    <li><a href="{{ route('product-requests.create') }}">اطلب منتج غير متوفر</a></li>
                </ul>
            </div>

            <div>
                <h2 class="site-footer__title">للمتاجر</h2>
                <ul class="site-footer__list">
                    <li><a href="{{ route('sell') }}">انضم كتاجر</a></li>
                    <li><a href="{{ route('seller.dashboard') }}">لوحة المتجر</a></li>
                </ul>
            </div>

            <div>
                <h2 class="site-footer__title">المساعدة</h2>
                <ul class="site-footer__list">
                    <li><a href="{{ route('pages.how-it-works') }}">كيف تعمل المنصة</a></li>
                    <li><a href="tel:{{ config('banha.support_phone') }}">{{ config('banha.support_phone') }}</a></li>
                </ul>
            </div>
        </div>

        <div class="site-footer__legal">
            <span>© {{ date('Y') }} بنها شوب — جميع الحقوق محفوظة</span>
            <span>الأسعار بالجنيه المصري وتشمل ما يعلنه كل متجر.</span>
        </div>
    </div>
</footer>
