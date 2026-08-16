<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\SellerOffer;
use App\Models\ShippingZone;
use App\Services\Shipping\ShippingQuote;
use App\Services\Shipping\ShippingQuoteService;
use Illuminate\Support\Collection;

/**
 * Builds the offer comparison for a product page.
 *
 * Ranking is deterministic and explainable — no hidden weighting, no
 * "recommended" that the customer cannot reconstruct:
 *
 *   total   : cheapest price + delivery, then faster promise, then fresher stock
 *   price    : cheapest product price, then cheapest total
 *   fastest  : earliest promised delivery, then cheapest total
 *
 * Offers that cannot reach the selected zone always sort last, whatever the
 * mode — an undeliverable offer is not a deal.
 */
class OfferComparisonService
{
    public function __construct(private readonly ShippingQuoteService $shipping) {}

    public function build(Product $product, ?ShippingZone $zone, string $sort = 'total'): OfferBoard
    {
        $sort = in_array($sort, config('banha.ranking.options', []), true)
            ? $sort
            : config('banha.ranking.default', 'total');

        $offers = $this->purchasableOffers($product);

        if ($offers->isEmpty()) {
            return new OfferBoard(collect(), $sort, $zone !== null);
        }

        if ($zone !== null) {
            // One round of queries for every seller on this page…
            $this->shipping->preload($offers->pluck('seller_id')->unique()->all(), $zone);
        }

        $compared = $offers->map(function (SellerOffer $offer) use ($zone, $sort) {
            // …then each offer is priced against its own price, so a store's
            // "free delivery over X" is applied exactly when it really applies.
            /** @var Collection<int, ShippingQuote> $quotes */
            $quotes = $zone === null
                ? collect()
                : $this->shipping->quote($offer->seller_id, $zone, $offer->price_cents);

            return new ComparedOffer(
                offer: $offer,
                quotes: $quotes,
                selectedQuote: $this->selectQuote($quotes, $sort),
            );
        });

        $compared = $this->flagWinners($compared);

        return new OfferBoard($this->sort($compared, $sort), $sort, $zone !== null);
    }

    /**
     * The quote whose total is being displayed. "fastest" shows the fastest
     * option's price so the number under the heading matches the heading.
     *
     * @param  Collection<int, ShippingQuote>  $quotes
     */
    private function selectQuote(Collection $quotes, string $sort): ?ShippingQuote
    {
        if ($quotes->isEmpty()) {
            return null;
        }

        return $sort === 'fastest'
            ? $quotes->sortBy(fn (ShippingQuote $quote) => $quote->speedScore())->first()
            : $quotes->first();
    }

    /**
     * @param  Collection<int, ComparedOffer>  $offers
     * @return Collection<int, ComparedOffer>
     */
    private function flagWinners(Collection $offers): Collection
    {
        $totals = $offers->map(fn (ComparedOffer $o) => $o->totalCents())->filter(fn ($t) => $t !== null)->values();
        $bestTotal = $totals->min();
        // A badge is only honest when the win is unambiguous. On a tie, nobody
        // is crowned.
        $bestIsUnique = $bestTotal !== null && $totals->filter(fn ($t) => $t === $bestTotal)->count() === 1;

        $lowestPrice = $offers->min(fn (ComparedOffer $o) => $o->priceCents());

        return $offers->map(fn (ComparedOffer $o) => $o->withFlags(
            isBestTotal: $bestIsUnique && $o->totalCents() === $bestTotal,
            isLowestPrice: $o->priceCents() === $lowestPrice,
        ));
    }

    /**
     * @param  Collection<int, ComparedOffer>  $offers
     * @return Collection<int, ComparedOffer>
     */
    private function sort(Collection $offers, string $sort): Collection
    {
        // PHP_INT_MAX pushes undeliverable offers to the bottom of every mode.
        $total = fn (ComparedOffer $o) => $o->totalCents() ?? PHP_INT_MAX;
        $speed = fn (ComparedOffer $o) => $o->selectedQuote?->speedScore() ?? PHP_INT_MAX;
        $fresh = fn (ComparedOffer $o) => -($o->offer->inventory_updated_at?->getTimestamp() ?? 0);

        $comparators = match ($sort) {
            'price' => [
                fn ($a, $b) => $a->priceCents() <=> $b->priceCents(),
                fn ($a, $b) => $total($a) <=> $total($b),
            ],
            'fastest' => [
                fn ($a, $b) => $speed($a) <=> $speed($b),
                fn ($a, $b) => $total($a) <=> $total($b),
            ],
            default => [
                fn ($a, $b) => $total($a) <=> $total($b),
                fn ($a, $b) => $speed($a) <=> $speed($b),
                fn ($a, $b) => $fresh($a) <=> $fresh($b),
            ],
        };

        // Final tiebreaker keeps the order stable across requests.
        $comparators[] = fn (ComparedOffer $a, ComparedOffer $b) => $a->offer->id <=> $b->offer->id;

        return $offers->sortBy($comparators)->values();
    }

    /** @return Collection<int, SellerOffer> */
    private function purchasableOffers(Product $product): Collection
    {
        return SellerOffer::query()
            ->where('product_id', $product->id)
            ->purchasable()
            ->fromActiveSellers()
            ->with(['seller:id,name,slug,logo_path,status,is_verified,orders_count,accepted_orders_count'])
            ->orderBy('price_cents')
            ->get();
    }
}
