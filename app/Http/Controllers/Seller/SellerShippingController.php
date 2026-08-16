<?php

namespace App\Http\Controllers\Seller;

use App\Models\ShippingProvider;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Support\Money;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * A store controls three things about delivery: which zones it serves, which
 * couriers it works with, and — if it delivers itself — its own price per zone.
 */
class SellerShippingController extends SellerController
{
    public function edit(Request $request, SeoData $seo): View
    {
        $seller = $this->seller($request);
        $seo->title('التوصيل والمناطق')->noindex(follow: false);

        $seller->load(['zones:id', 'shippingProviders:id']);

        $selfProviders = ShippingProvider::query()->active()
            ->where('type', 'seller')
            ->get();

        return view('pages.seller.shipping', [
            'seller' => $seller,
            'zones' => ShippingZone::query()->active()->get(),
            'providers' => ShippingProvider::query()->active()->get(),
            'selfProviders' => $selfProviders,
            'selectedZoneIds' => $seller->zones->pluck('id')->all(),
            'enabledProviderIds' => $seller->shippingProviders->pluck('id')->all(),
            'ownRates' => ShippingRate::where('seller_id', $seller->id)
                ->get()
                ->keyBy(fn (ShippingRate $rate) => $rate->shipping_provider_id.':'.$rate->shipping_zone_id),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $seller = $this->seller($request);

        $validated = $request->validate([
            'zones' => ['array'],
            'zones.*' => ['integer', 'exists:shipping_zones,id'],
            'providers' => ['array'],
            'providers.*' => ['integer', 'exists:shipping_providers,id'],
            'rates' => ['array'],
            'rates.*.*.price' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'rates.*.*.free_over' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'rates.*.*.eta_min' => ['nullable', 'integer', 'min:1', 'max:720'],
            'rates.*.*.eta_max' => ['nullable', 'integer', 'min:1', 'max:720'],
        ]);

        $zoneIds = $validated['zones'] ?? [];

        DB::transaction(function () use ($seller, $validated, $zoneIds) {
            $seller->zones()->sync($zoneIds);

            $seller->shippingProviders()->sync(
                collect($validated['providers'] ?? [])->mapWithKeys(fn ($id) => [$id => ['is_enabled' => true]])->all()
            );

            foreach ($validated['rates'] ?? [] as $providerId => $zoneRates) {
                foreach ($zoneRates as $zoneId => $values) {
                    $price = $values['price'] ?? null;

                    // A rate only exists where the store actually delivers.
                    if ($price === null || $price === '' || ! in_array((int) $zoneId, array_map('intval', $zoneIds), true)) {
                        ShippingRate::where('seller_id', $seller->id)
                            ->where('shipping_provider_id', $providerId)
                            ->where('shipping_zone_id', $zoneId)
                            ->delete();

                        continue;
                    }

                    $etaMin = (int) ($values['eta_min'] ?? 24);
                    $etaMax = max($etaMin, (int) ($values['eta_max'] ?? 48));

                    ShippingRate::updateOrCreate(
                        [
                            'seller_id' => $seller->id,
                            'shipping_provider_id' => $providerId,
                            'shipping_zone_id' => $zoneId,
                        ],
                        [
                            'price_cents' => Money::toCents($price),
                            'free_over_cents' => filled($values['free_over'] ?? null)
                                ? Money::toCents($values['free_over'])
                                : null,
                            'eta_min_hours' => $etaMin,
                            'eta_max_hours' => $etaMax,
                            'is_active' => true,
                        ]
                    );
                }
            }
        });

        return redirect()->route('seller.shipping.edit')->with('status', 'تم حفظ إعدادات التوصيل.');
    }
}
