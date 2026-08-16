<x-layouts.app>
    <div class="row row--between" style="margin-block-end:18px">
        <h1>طلباتي</h1>
        <a href="{{ route('account.addresses') }}" class="btn btn--sm">عناويني</a>
    </div>

    @if ($orders->isEmpty())
        <x-ui.empty title="لسه مفيش طلبات" text="أول ما تطلب من متاجر {{ config('banha.city') }} هتلاقي طلباتك هنا.">
            <x-slot:action>
                <a href="{{ route('products.index') }}" class="btn btn--primary">تصفح المنتجات</a>
            </x-slot:action>
        </x-ui.empty>
    @else
        <div class="stack-12">
            @foreach ($orders as $order)
                <article class="panel">
                    <div class="panel__head">
                        <div>
                            <a href="{{ $order->url() }}" class="strong num">{{ $order->number }}</a>
                            <div class="xsmall muted">{{ $order->placed_at?->translatedFormat('j F Y — g:i a') }}</div>
                        </div>
                        <x-ui.badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-ui.badge>
                    </div>
                    <div class="panel__body">
                        <div class="row row--between row--wrap">
                            <p class="small muted">
                                {{ $order->sellerOrders->count() }}
                                {{ $order->sellerOrders->count() === 1 ? 'متجر' : 'متاجر' }}:
                                {{ $order->sellerOrders->map(fn ($so) => $so->seller->name)->join('، ') }}
                            </p>
                            <span class="strong num">{{ money($order->grand_total_cents) }}</span>
                        </div>
                    </div>
                    <div class="panel__foot">
                        <a href="{{ $order->url() }}" class="btn btn--sm">تفاصيل الطلب</a>
                    </div>
                </article>
            @endforeach
        </div>

        {{ $orders->links() }}
    @endif
</x-layouts.app>
