<?php

namespace App\Observers;

use App\Models\SellerOffer;
use App\Services\Catalog\ProductAggregateUpdater;

/**
 * Any write to an offer — seller dashboard, admin, checkout stock decrement —
 * must leave the product's counters correct. One place, no exceptions.
 */
class SellerOfferObserver
{
    public function __construct(private readonly ProductAggregateUpdater $aggregates) {}

    public function saved(SellerOffer $offer): void
    {
        $this->sync($offer);
    }

    public function deleted(SellerOffer $offer): void
    {
        $this->sync($offer);
    }

    private function sync(SellerOffer $offer): void
    {
        $this->aggregates->refreshProducts(array_filter([
            $offer->product_id,
            $offer->getOriginal('product_id'),
        ]));

        $this->aggregates->refreshSellers(array_filter([
            $offer->seller_id,
            $offer->getOriginal('seller_id'),
        ]));
    }
}
