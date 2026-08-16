<?php

namespace App\Services\Shipping;

use App\Models\Seller;
use App\Models\ShippingProvider;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns (seller, zone, basket subtotal) into the delivery options a customer
 * may pick.
 *
 * A rate applies when all of these hold:
 *   1. the provider is active and the seller has enabled it
 *   2. the seller delivers to that zone at all
 *   3. a rate row exists for provider+zone, seller-specific beating platform-wide
 *
 * The subtotal matters: free-delivery thresholds mean the same store can be the
 * most expensive option on a cheap item and the cheapest on an expensive one.
 *
 * Database work is memoised per (zone, sellers) and pricing is pure computation
 * on top, so quoting one product page — many sellers, a different subtotal per
 * offer — still costs a fixed handful of queries.
 */
class ShippingQuoteService
{
    /**
     * One cached context per zone, covering every seller asked about so far.
     *
     * @var array<int, array{covers: array<int, int>, providers: Collection, enabled: array, served: array, rates: Collection}>
     */
    private array $context = [];

    /** @return Collection<int, ShippingQuote> */
    public function for(Seller $seller, ShippingZone $zone, int $subtotalCents = 0): Collection
    {
        return $this->forMany(collect([$seller]), $zone, $subtotalCents)
            ->get($seller->id, collect());
    }

    /**
     * @param  Collection<int, Seller>  $sellers
     * @param  int|array<int, int>  $subtotalCents  one subtotal, or per-seller subtotals
     * @return Collection<int, Collection<int, ShippingQuote>> keyed by seller id
     */
    public function forMany(Collection $sellers, ShippingZone $zone, int|array $subtotalCents = 0): Collection
    {
        $sellerIds = $sellers->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($sellerIds === []) {
            return collect();
        }

        $context = $this->context($sellerIds, $zone->id);

        return collect($sellerIds)->mapWithKeys(function (int $sellerId) use ($context, $subtotalCents) {
            $subtotal = is_array($subtotalCents) ? ($subtotalCents[$sellerId] ?? 0) : $subtotalCents;

            return [$sellerId => $this->price($context, $sellerId, $subtotal)];
        });
    }

    /**
     * Quotes for one seller at one basket value. Cheap to call repeatedly — the
     * product page calls it once per offer so each row is priced against its
     * own price, which is what makes free-over-X thresholds honest.
     */
    public function quote(int $sellerId, ShippingZone $zone, int $subtotalCents): Collection
    {
        return $this->price($this->context([$sellerId], $zone->id), $sellerId, $subtotalCents);
    }

    /** Warms the query cache for a set of sellers before per-offer quoting. */
    public function preload(array $sellerIds, ShippingZone $zone): void
    {
        $this->context(array_map('intval', $sellerIds), $zone->id);
    }

    public function cheapest(Seller $seller, ShippingZone $zone, int $subtotalCents = 0): ?ShippingQuote
    {
        return $this->for($seller, $zone, $subtotalCents)->first();
    }

    /** @return Collection<int, ShippingQuote> */
    private function price(array $context, int $sellerId, int $subtotalCents): Collection
    {
        if (! in_array($sellerId, $context['served'], true)) {
            return collect();
        }

        $enabled = $context['enabled'][$sellerId] ?? [];

        return $context['providers']
            ->filter(fn (ShippingProvider $p) => in_array($p->id, $enabled, true))
            ->map(function (ShippingProvider $provider) use ($context, $sellerId, $subtotalCents) {
                $rate = $this->resolveRate($context['rates'], $provider->id, $sellerId);

                return $rate ? ShippingQuote::fromRate($rate, $provider, $subtotalCents) : null;
            })
            ->filter()
            // Cheapest first, ties broken by the faster promise.
            ->sortBy([
                fn (ShippingQuote $a, ShippingQuote $b) => $a->priceCents <=> $b->priceCents,
                fn (ShippingQuote $a, ShippingQuote $b) => $a->etaMaxHours <=> $b->etaMaxHours,
            ])
            ->values();
    }

    /**
     * A seller-specific rate always wins over the provider's platform rate:
     * self-delivery and negotiated pricing are both expressed that way.
     *
     * @param  Collection<int, ShippingRate>  $rates
     */
    private function resolveRate(Collection $rates, int $providerId, int $sellerId): ?ShippingRate
    {
        $candidates = $rates->where('shipping_provider_id', $providerId);

        return $candidates->firstWhere('seller_id', $sellerId)
            ?? $candidates->firstWhere('seller_id', null);
    }

    /**
     * Everything the pricing needs, loaded in three queries and reused for the
     * rest of the request.
     *
     * @return array{providers: Collection, enabled: array, served: array, rates: Collection}
     */
    private function context(array $sellerIds, int $zoneId): array
    {
        $cached = $this->context[$zoneId] ?? null;

        // A cached context is reusable whenever it already covers every seller
        // being asked about. The product page preloads all its sellers once,
        // then quotes them one at a time for free.
        if ($cached !== null && array_diff($sellerIds, $cached['covers']) === []) {
            return $cached;
        }

        $covered = array_values(array_unique(array_merge($cached['covers'] ?? [], $sellerIds)));

        return $this->context[$zoneId] = [
            'covers' => $covered,
            'providers' => ShippingProvider::query()->active()->get(),
            'enabled' => $this->enabledProviderIdsBySeller($covered),
            'served' => $this->sellersServingZone($covered, $zoneId),
            'rates' => $this->ratesForZone($zoneId, $covered),
        ];
    }

    /** @return array<int, array<int, int>> */
    private function enabledProviderIdsBySeller(array $sellerIds): array
    {
        return DB::table('seller_shipping_provider')
            ->whereIn('seller_id', $sellerIds)
            ->where('is_enabled', true)
            ->get(['seller_id', 'shipping_provider_id'])
            ->groupBy('seller_id')
            ->map(fn ($rows) => $rows->pluck('shipping_provider_id')->map(fn ($id) => (int) $id)->all())
            ->all();
    }

    /** @return array<int, int> */
    private function sellersServingZone(array $sellerIds, int $zoneId): array
    {
        return DB::table('seller_zone')
            ->whereIn('seller_id', $sellerIds)
            ->where('shipping_zone_id', $zoneId)
            ->pluck('seller_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return Collection<int, ShippingRate> */
    private function ratesForZone(int $zoneId, array $sellerIds): Collection
    {
        return ShippingRate::query()
            ->active()
            ->where('shipping_zone_id', $zoneId)
            ->where(function ($query) use ($sellerIds) {
                $query->whereNull('seller_id')->orWhereIn('seller_id', $sellerIds);
            })
            ->get();
    }
}
