<nav class="dash-nav" aria-label="قائمة لوحة المتجر">
    <div class="dash-nav__group">
        <p class="dash-nav__title">المتجر</p>
        <a href="{{ route('seller.dashboard') }}" class="dash-nav__link"
           @if (request()->routeIs('seller.dashboard')) aria-current="page" @endif>
            <x-ui.icon name="chart" :size="17" /> نظرة عامة
        </a>
        <a href="{{ route('seller.offers.index') }}" class="dash-nav__link"
           @if (request()->routeIs('seller.offers.*')) aria-current="page" @endif>
            <x-ui.icon name="tag" :size="17" /> عروضي
        </a>
        <a href="{{ route('seller.orders.index') }}" class="dash-nav__link"
           @if (request()->routeIs('seller.orders.*')) aria-current="page" @endif>
            <x-ui.icon name="inbox" :size="17" /> الطلبات
        </a>
    </div>

    <div class="dash-nav__group">
        <p class="dash-nav__title">الإعدادات</p>
        <a href="{{ route('seller.shipping.edit') }}" class="dash-nav__link"
           @if (request()->routeIs('seller.shipping.*')) aria-current="page" @endif>
            <x-ui.icon name="truck" :size="17" /> التوصيل والمناطق
        </a>
        <a href="{{ route('seller.profile.edit') }}" class="dash-nav__link"
           @if (request()->routeIs('seller.profile.*')) aria-current="page" @endif>
            <x-ui.icon name="store" :size="17" /> بيانات المتجر
        </a>
    </div>
</nav>
