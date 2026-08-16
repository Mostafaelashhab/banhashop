<nav class="dash-nav" aria-label="قائمة لوحة الإدارة">
    <div class="dash-nav__group">
        <p class="dash-nav__title">المنصة</p>
        <a href="{{ route('admin.dashboard') }}" class="dash-nav__link"
           @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>
            <x-ui.icon name="chart" :size="17" /> المؤشرات
        </a>
        <a href="{{ route('admin.orders.index') }}" class="dash-nav__link"
           @if (request()->routeIs('admin.orders.*')) aria-current="page" @endif>
            <x-ui.icon name="inbox" :size="17" /> الطلبات
        </a>
    </div>

    <div class="dash-nav__group">
        <p class="dash-nav__title">الكتالوج</p>
        <a href="{{ route('admin.products.index') }}" class="dash-nav__link"
           @if (request()->routeIs('admin.products.*')) aria-current="page" @endif>
            <x-ui.icon name="package" :size="17" /> المنتجات
        </a>
        <a href="{{ route('admin.categories.index') }}" class="dash-nav__link"
           @if (request()->routeIs('admin.categories.*')) aria-current="page" @endif>
            <x-ui.icon name="layers" :size="17" /> الأقسام
        </a>
        <a href="{{ route('admin.product-requests.index') }}" class="dash-nav__link"
           @if (request()->routeIs('admin.product-requests.*')) aria-current="page" @endif>
            <x-ui.icon name="list" :size="17" /> طلبات المنتجات
        </a>
    </div>

    <div class="dash-nav__group">
        <p class="dash-nav__title">التشغيل</p>
        <a href="{{ route('admin.sellers.index') }}" class="dash-nav__link"
           @if (request()->routeIs('admin.sellers.*')) aria-current="page" @endif>
            <x-ui.icon name="store" :size="17" /> المتاجر
        </a>
        <a href="{{ route('admin.shipping.index') }}" class="dash-nav__link"
           @if (request()->routeIs('admin.shipping.*')) aria-current="page" @endif>
            <x-ui.icon name="truck" :size="17" /> الشحن والمناطق
        </a>
    </div>
</nav>
