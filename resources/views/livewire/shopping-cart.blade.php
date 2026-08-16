<div>
    @if ($summary->isEmpty())
        <x-ui.empty
            illustration="cart"
            title="سلتك فاضية"
            text="ابدأ من الأقسام أو ابحث عن المنتج اللي محتاجه، وقارن عروض متاجر {{ config('banha.city') }}."
        >
            <x-slot:action>
                <a href="{{ route('products.index') }}" class="btn btn--primary">تصفح المنتجات</a>
            </x-slot:action>
        </x-ui.empty>
    @else
        @if ($summary->issues())
            <x-ui.alert tone="warn" title="محتاج تعديل قبل إتمام الطلب" style="margin-block-end:16px">
                <ul>
                    @foreach ($summary->issues() as $issue)
                        <li>{{ $issue }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        @if ($summary->isMultiSeller())
            <x-ui.alert tone="info" style="margin-block-end:16px">
                طلبك من {{ $summary->groups->count() }} متاجر مختلفة، يعني هيوصلك في
                {{ $summary->groups->count() }} شحنات منفصلة، وكل متجر بيحسب توصيله لوحده.
            </x-ui.alert>
        @endif

        <div class="split" wire:loading.class="is-busy">
            <div>
                @foreach ($summary->groups as $group)
                    <section class="panel cart-group" wire:key="group-{{ $group->seller->id }}">
                        <div class="panel__head">
                            <div class="row">
                                <x-ui.icon name="store" :size="17" />
                                <a href="{{ $group->seller->url() }}" class="strong">{{ $group->seller->name }}</a>
                            </div>
                            <span class="small muted num">{{ $group->quantity() }} قطعة</span>
                        </div>

                        @foreach ($group->items as $item)
                            <div class="cart-line" wire:key="item-{{ $item->id }}">
                                <a href="{{ $item->product->url() }}" tabindex="-1" aria-hidden="true">
                                    <x-product.thumb :product="$item->product" :size="72"
                                                     class="cart-line__media" sizes="72px" />
                                </a>

                                <div style="min-width:0">
                                    <a href="{{ $item->product->url() }}" class="cart-line__title">
                                        {{ $item->product->displayName() }}
                                    </a>

                                    <div class="small muted" style="margin-block-start:2px">
                                        {{ money($item->currentUnitPrice()) }} للقطعة
                                        @if ($item->priceChanged())
                                            <span class="badge badge--warn" style="margin-inline-start:6px">تغيّر السعر</span>
                                        @endif
                                    </div>

                                    <div class="cart-line__foot">
                                        {{-- Plain POST forms, intercepted by Livewire when it is
                                             loaded, so the cart still works without JavaScript. --}}
                                        <div class="qty">
                                            <form method="POST" action="{{ route('cart.update', $item) }}"
                                                  wire:submit="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="quantity" value="{{ $item->quantity - 1 }}">
                                                <button type="submit" aria-label="تقليل الكمية"
                                                        wire:loading.attr="disabled">
                                                    <x-ui.icon name="minus" :size="15" />
                                                </button>
                                            </form>

                                            <span class="qty__value" aria-live="polite">{{ $item->quantity }}</span>

                                            <form method="POST" action="{{ route('cart.update', $item) }}"
                                                  wire:submit="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }})">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="quantity" value="{{ $item->quantity + 1 }}">
                                                <button type="submit" aria-label="زيادة الكمية"
                                                        wire:loading.attr="disabled"
                                                        @disabled($item->offer && $item->quantity >= $item->offer->stock)>
                                                    <x-ui.icon name="plus" :size="15" />
                                                </button>
                                            </form>
                                        </div>

                                        <div class="row" style="gap:12px">
                                            <span class="strong num">{{ money($item->lineTotal()) }}</span>
                                            <form method="POST" action="{{ route('cart.destroy', $item) }}"
                                                  wire:submit="remove({{ $item->id }})">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="icon-btn" aria-label="حذف المنتج من السلة"
                                                        wire:loading.attr="disabled">
                                                    <x-ui.icon name="trash" :size="17" />
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="panel__foot">
                            <div class="totals">
                                <div class="totals__line">
                                    <span class="muted">إجمالي المنتجات</span>
                                    <span class="totals__value">{{ money($group->subtotalCents()) }}</span>
                                </div>
                                <div class="totals__line">
                                    <span class="muted">
                                        التوصيل
                                        @if ($group->selectedQuote)
                                            <span class="dim">— {{ $group->selectedQuote->provider->name }}،
                                                {{ $group->selectedQuote->deliveryLabel() }}</span>
                                        @endif
                                    </span>
                                    <span class="totals__value">
                                        @if ($group->selectedQuote === null)
                                            <span style="color:var(--bad)">غير متاح لمنطقتك</span>
                                        @elseif ($group->selectedQuote->isFree())
                                            مجاني
                                        @else
                                            {{ money($group->shippingCents()) }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>

            <aside class="panel summary-aside">
                <div class="panel__head"><h2>ملخص الطلب</h2></div>
                <div class="panel__body">
                    <div class="totals">
                        <div class="totals__line">
                            <span class="muted">إجمالي المنتجات</span>
                            <span class="totals__value">{{ money($summary->itemsTotalCents()) }}</span>
                        </div>
                        <div class="totals__line">
                            <span class="muted">إجمالي التوصيل</span>
                            <span class="totals__value">{{ money($summary->shippingTotalCents()) }}</span>
                        </div>
                        <div class="totals__line totals__line--grand">
                            <span>الإجمالي النهائي</span>
                            <span class="totals__value">{{ money($summary->grandTotalCents()) }}</span>
                        </div>
                    </div>

                    @if ($zone)
                        <p class="xsmall muted" style="margin-block-start:10px">
                            محسوب على أساس التوصيل إلى {{ $zone->name }}. تقدر تغيّر المنطقة من أعلى الصفحة.
                        </p>
                    @endif
                </div>
                <div class="panel__foot">
                    <a href="{{ route('checkout.show') }}"
                       class="btn btn--primary btn--lg btn--block summary-aside__cta"
                       @if (! $summary->canCheckout()) aria-disabled="true" @endif>
                        إتمام الطلب
                    </a>
                    <p class="xsmall muted" style="margin-block-start:10px;text-align:center">
                        الدفع عند الاستلام متاح لكل متاجر {{ config('banha.city') }}.
                    </p>
                </div>
            </aside>
        </div>

        {{-- On a phone the summary is just the last block on a long page, so
             the total and the action ride along at the bottom of the viewport
             instead of waiting at the end of it. --}}
        <div class="sticky-bar">
            <div class="sticky-bar__inner">
                <div class="sticky-bar__total">
                    <div class="sticky-bar__label">الإجمالي النهائي</div>
                    <div class="sticky-bar__amount num">{{ money($summary->grandTotalCents()) }}</div>
                </div>
                <a href="{{ route('checkout.show') }}"
                   class="btn btn--primary btn--lg"
                   @if (! $summary->canCheckout()) aria-disabled="true" @endif>
                    إتمام الطلب
                </a>
            </div>
        </div>
    @endif
</div>
