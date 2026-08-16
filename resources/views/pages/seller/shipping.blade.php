<x-layouts.dashboard heading="التوصيل والمناطق">
    <form method="POST" action="{{ route('seller.shipping.update') }}">
        @csrf @method('PUT')

        <div class="stack">
            {{-- 1. Where the store is willing to go at all. --}}
            <section class="panel">
                <div class="panel__head"><h2>مناطق التوصيل</h2></div>
                <div class="panel__body">
                    <p class="small muted" style="margin-block-end:12px">
                        عروضك بتظهر بسعر نهائي للعملاء في المناطق دي فقط. لو منطقة مش مختارة، هيشوفوا
                        "المتجر لا يوصّل لمنطقتك".
                    </p>

                    <div class="field-grid field-grid--2">
                        @foreach ($zones as $zone)
                            <label class="check">
                                <input type="checkbox" name="zones[]" value="{{ $zone->id }}"
                                       @checked(in_array($zone->id, $selectedZoneIds, true))>
                                <span>
                                    {{ $zone->name }}
                                    @if ($zone->description)
                                        <span class="xsmall dim" style="display:block">{{ $zone->description }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- 2. Which couriers the store works with. --}}
            <section class="panel">
                <div class="panel__head"><h2>شركات التوصيل</h2></div>
                <div class="panel__body">
                    <div class="stack-8">
                        @foreach ($providers as $provider)
                            <label class="check">
                                <input type="checkbox" name="providers[]" value="{{ $provider->id }}"
                                       @checked(in_array($provider->id, $enabledProviderIds, true))>
                                <span>
                                    <span class="strong">{{ $provider->name }}</span>
                                    <span class="xsmall dim" style="display:block">
                                        {{ $provider->description }}
                                        @if ($provider->isSellerOwned())
                                            — بتحدد سعرك بنفسك تحت
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- 3. Self-delivery pricing, per zone. --}}
            @foreach ($selfProviders as $provider)
                <section class="panel">
                    <div class="panel__head"><h2>أسعار {{ $provider->name }}</h2></div>
                    <div class="panel__body">
                        <p class="small muted" style="margin-block-end:12px">
                            سيبها فاضية لو مش بتوصّل للمنطقة دي بنفسك. السعر ده بيغلب سعر أي شركة تانية لنفس المنطقة.
                        </p>

                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>المنطقة</th>
                                        <th>سعر التوصيل (ج.م)</th>
                                        <th>مجاني فوق (ج.م)</th>
                                        <th>أقل مدة (ساعة)</th>
                                        <th>أقصى مدة (ساعة)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($zones as $zone)
                                        @php $rate = $ownRates->get($provider->id.':'.$zone->id); @endphp
                                        <tr>
                                            <td>{{ $zone->name }}</td>
                                            <td>
                                                <label class="sr-only" for="p-{{ $provider->id }}-{{ $zone->id }}">
                                                    سعر التوصيل إلى {{ $zone->name }}
                                                </label>
                                                <input type="number" step="0.01" min="0" class="input" style="max-width:120px"
                                                       id="p-{{ $provider->id }}-{{ $zone->id }}"
                                                       name="rates[{{ $provider->id }}][{{ $zone->id }}][price]"
                                                       value="{{ $rate ? \App\Support\Money::decimal($rate->price_cents) : '' }}">
                                            </td>
                                            <td>
                                                <label class="sr-only" for="f-{{ $provider->id }}-{{ $zone->id }}">
                                                    توصيل مجاني فوق مبلغ في {{ $zone->name }}
                                                </label>
                                                <input type="number" step="0.01" min="0" class="input" style="max-width:120px"
                                                       id="f-{{ $provider->id }}-{{ $zone->id }}"
                                                       name="rates[{{ $provider->id }}][{{ $zone->id }}][free_over]"
                                                       value="{{ $rate?->free_over_cents ? \App\Support\Money::decimal($rate->free_over_cents) : '' }}">
                                            </td>
                                            <td>
                                                <label class="sr-only" for="mn-{{ $provider->id }}-{{ $zone->id }}">أقل مدة</label>
                                                <input type="number" min="1" class="input" style="max-width:90px"
                                                       id="mn-{{ $provider->id }}-{{ $zone->id }}"
                                                       name="rates[{{ $provider->id }}][{{ $zone->id }}][eta_min]"
                                                       value="{{ $rate?->eta_min_hours }}">
                                            </td>
                                            <td>
                                                <label class="sr-only" for="mx-{{ $provider->id }}-{{ $zone->id }}">أقصى مدة</label>
                                                <input type="number" min="1" class="input" style="max-width:90px"
                                                       id="mx-{{ $provider->id }}-{{ $zone->id }}"
                                                       name="rates[{{ $provider->id }}][{{ $zone->id }}][eta_max]"
                                                       value="{{ $rate?->eta_max_hours }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            @endforeach

            <div>
                <button type="submit" class="btn btn--primary btn--lg">حفظ إعدادات التوصيل</button>
            </div>
        </div>
    </form>
</x-layouts.dashboard>
