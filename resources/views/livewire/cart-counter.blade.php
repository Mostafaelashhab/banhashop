<a href="{{ route('cart.index') }}" class="header-action"
   aria-label="السلة{{ $count ? " — {$count} منتج" : '' }}">
    <span class="header-action__icon">
        <x-ui.icon name="cart" :size="19" />
        @if ($count > 0)
            <span class="header-action__count num" aria-hidden="true">{{ $count }}</span>
        @endif
    </span>
    <span class="header-action__label" aria-hidden="true">السلة</span>
</a>
