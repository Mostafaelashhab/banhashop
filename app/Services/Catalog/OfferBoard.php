<?php

namespace App\Services\Catalog;

use Illuminate\Support\Collection;

/**
 * The full comparison for one product, already sorted. Blade only reads from
 * this — it never sorts, prices or queries.
 */
final class OfferBoard
{
    /**
     * @param  Collection<int, ComparedOffer>  $offers
     */
    public function __construct(
        public readonly Collection $offers,
        public readonly string $sort,
        public readonly bool $zoneKnown,
    ) {}

    public function isEmpty(): bool
    {
        return $this->offers->isEmpty();
    }

    public function count(): int
    {
        return $this->offers->count();
    }

    public function sellersCount(): int
    {
        return $this->offers->pluck('offer.seller_id')->unique()->count();
    }

    public function best(): ?ComparedOffer
    {
        return $this->offers->first(fn (ComparedOffer $o) => $o->isBestTotal);
    }

    public function cheapestTotal(): ?int
    {
        return $this->offers
            ->map(fn (ComparedOffer $o) => $o->totalCents())
            ->filter(fn (?int $t) => $t !== null)
            ->min();
    }

    public function lowestPrice(): ?int
    {
        return $this->offers->min(fn (ComparedOffer $o) => $o->priceCents());
    }

    /**
     * True when the cheapest product price is NOT the cheapest real total —
     * the exact situation Banha.shop exists to make visible.
     */
    public function cheapestPriceIsNotBestDeal(): bool
    {
        $best = $this->best();

        if ($best === null) {
            return false;
        }

        return ! $best->isLowestPrice;
    }

    public function deliverableCount(): int
    {
        return $this->offers->filter(fn (ComparedOffer $o) => $o->deliversToZone())->count();
    }
}
