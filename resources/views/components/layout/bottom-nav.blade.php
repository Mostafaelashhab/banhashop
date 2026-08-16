@php
    // Five destinations, no more. A bottom bar is thumb real estate on the most
    // valuable part of the screen, so anything that is not a top-level place a
    // customer returns to stays out of it.
    $items = [
        ['route' => 'home',            'active' => request()->routeIs('home'),                    'icon' => 'store', 'label' => 'الرئيسية'],
        ['route' => 'products.index',  'active' => request()->routeIs('products.index', 'categories.show'), 'icon' => 'grid',  'label' => 'الأقسام'],
        ['route' => 'search',          'active' => request()->routeIs('search'),                  'icon' => 'search', 'label' => 'البحث'],
        ['route' => 'cart.index',      'active' => request()->routeIs('cart.*'),                  'icon' => 'cart',  'label' => 'السلة'],
        auth()->check()
            ? ['route' => 'account.orders', 'active' => request()->routeIs('account.*'), 'icon' => 'user', 'label' => 'حسابي']
            : ['route' => 'login',          'active' => request()->routeIs('login'),     'icon' => 'user', 'label' => 'دخول'],
    ];
@endphp

<nav class="bottom-nav" aria-label="التنقل السريع">
    @foreach ($items as $item)
        <a href="{{ route($item['route']) }}" class="bottom-nav__link"
           @if ($item['active']) aria-current="page" @endif>
            <x-ui.icon :name="$item['icon']" :size="21" />
            <span class="bottom-nav__label">{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
