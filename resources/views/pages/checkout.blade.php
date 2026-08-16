<x-layouts.app>
    <h1 style="margin-block-end:18px">إتمام الطلب</h1>

    @if ($summary->issues())
        <x-ui.alert tone="warn" title="لازم نحل ده الأول" style="margin-block-end:16px">
            <ul>
                @foreach ($summary->issues() as $issue)
                    <li>{{ $issue }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <div class="split split--wide-aside">
        <div class="stack">
            {{-- 1. Address first: it decides the delivery zone, which prices
                 everything below it. Switching address is a plain GET link, so
                 the page re-prices server-side with no JavaScript. --}}
            <section class="panel">
                <div class="panel__head"><h2>عنوان التوصيل</h2></div>
                <div class="panel__body stack-12">
                    @forelse ($addresses as $address)
                        <a href="{{ route('checkout.show', ['address' => $address->id]) }}"
                           class="choice"
                           style="{{ $selectedAddress?->id === $address->id ? 'border-color:var(--brand);background:var(--brand-tint)' : '' }}"
                           @if ($selectedAddress?->id === $address->id) aria-current="true" @endif>
                            <span class="choice__body">
                                <span class="choice__title">
                                    {{ $address->recipient_name }}
                                    @if ($address->label)
                                        <span class="badge badge--outline">{{ $address->label }}</span>
                                    @endif
                                    @if ($selectedAddress?->id === $address->id)
                                        <span class="badge badge--good">مختار</span>
                                    @endif
                                </span>
                                <span class="choice__meta">{{ $address->summary() }} — {{ $address->zone?->name }}</span>
                                <span class="choice__meta num">{{ $address->phone }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="muted small">لسه مضفتش عنوان. ضيف عنوان التوصيل عشان نحسب تكلفة الشحن بدقة.</p>
                    @endforelse

                    <details @if ($addresses->isEmpty()) open @endif>
                        <summary class="strong small" style="cursor:pointer;padding-block:6px">إضافة عنوان جديد</summary>
                        <div style="margin-block-start:12px">
                            @include('pages.account._address-form', [
                                'zones' => $zones,
                                'redirectTo' => route('checkout.show'),
                            ])
                        </div>
                    </details>
                </div>
            </section>

            <form method="POST" action="{{ route('checkout.store') }}" id="checkout-form">
                @csrf
                <input type="hidden" name="address_id" value="{{ $selectedAddress?->id }}">

                <div class="stack">
                    {{-- 2. Delivery: one choice per store, priced for this address. --}}
                    <section class="panel">
                        <div class="panel__head"><h2>طريقة التوصيل</h2></div>
                        <div class="panel__body stack">
                            @foreach ($summary->groups as $group)
                                <fieldset style="border:0;padding:0;margin:0">
                                    <legend class="field__label">{{ $group->seller->name }}</legend>

                                    @if ($group->quotes->isEmpty())
                                        <x-ui.alert tone="bad">
                                            هذا المتجر لا يوصّل إلى {{ $zone?->name }}. غيّر العنوان أو احذف منتجاته من السلة.
                                        </x-ui.alert>
                                    @else
                                        <div class="stack-8">
                                            @foreach ($group->quotes as $quote)
                                                <label class="choice">
                                                    <input type="radio"
                                                           name="shipping[{{ $group->seller->id }}]"
                                                           value="{{ $quote->rate->id }}"
                                                           @checked($group->selectedQuote?->rate->id === $quote->rate->id)>
                                                    <span class="choice__body">
                                                        <span class="choice__title">{{ $quote->provider->name }}</span>
                                                        <span class="choice__meta">
                                                            التسليم {{ $quote->deliveryLabel() }}
                                                            @if ($quote->isFreeByThreshold())
                                                                — توصيل مجاني لتجاوزك الحد الأدنى
                                                            @endif
                                                        </span>
                                                    </span>
                                                    <span class="choice__price">
                                                        {{ $quote->isFree() ? 'مجاني' : money($quote->priceCents) }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                </fieldset>
                            @endforeach
                        </div>
                    </section>

                    {{-- 3. Payment. --}}
                    <section class="panel">
                        <div class="panel__head"><h2>طريقة الدفع</h2></div>
                        <div class="panel__body stack-8">
                            @foreach ($paymentMethods as $method)
                                <label class="choice">
                                    <input type="radio" name="payment_method" value="{{ $method->value }}" @checked($loop->first)>
                                    <span class="choice__body">
                                        <span class="choice__title">{{ $method->label() }}</span>
                                        <span class="choice__meta">{{ $method->description() }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </section>
                </div>
            </form>
        </div>

        <aside class="panel" style="position:sticky;top:calc(var(--header-h) + 12px)">
            <div class="panel__head"><h2>ملخص الطلب</h2></div>
            <div class="panel__body">
                @foreach ($summary->groups as $group)
                    <div style="padding-block:8px;{{ $loop->last ? '' : 'border-block-end:1px solid var(--line-2)' }}">
                        <p class="small strong">{{ $group->seller->name }}</p>
                        @foreach ($group->items as $item)
                            <div class="row row--between small muted" style="padding-block:2px">
                                <span class="clamp-2">{{ $item->product->displayName() }} × {{ $item->quantity }}</span>
                                <span class="num nowrap">{{ money($item->lineTotal()) }}</span>
                            </div>
                        @endforeach
                        <div class="row row--between small" style="padding-block-start:4px">
                            <span class="muted">التوصيل</span>
                            <span class="num">
                                @if ($group->shippingCents() === null)
                                    —
                                @elseif ($group->shippingCents() === 0)
                                    مجاني
                                @else
                                    {{ money($group->shippingCents()) }}
                                @endif
                            </span>
                        </div>
                    </div>
                @endforeach

                <div class="totals" style="margin-block-start:10px">
                    <div class="totals__line">
                        <span class="muted">إجمالي المنتجات</span>
                        <span class="totals__value">{{ money($summary->itemsTotalCents()) }}</span>
                    </div>
                    <div class="totals__line">
                        <span class="muted">إجمالي التوصيل</span>
                        <span class="totals__value">{{ money($summary->shippingTotalCents()) }}</span>
                    </div>
                    <div class="totals__line totals__line--grand">
                        <span>الإجمالي</span>
                        <span class="totals__value">{{ money($summary->grandTotalCents()) }}</span>
                    </div>
                </div>
            </div>
            <div class="panel__foot">
                <button type="submit" form="checkout-form" class="btn btn--primary btn--lg btn--block"
                        @disabled(! $summary->canCheckout() || $selectedAddress === null)>
                    تأكيد الطلب
                </button>
                <p class="xsmall muted" style="margin-block-start:10px;text-align:center">
                    كل متجر هيستلم طلبه ويأكده، وهتقدر تتابع الحالة من صفحة الطلب.
                </p>
            </div>
        </aside>
    </div>
</x-layouts.app>
