<?php

namespace App\Services\Catalog;

use App\Models\Seller;
use App\Models\SellerOffer;
use App\Services\Shipping\ShippingQuote;
use Illuminate\Support\Collection;

/**
 * One row of the comparison table: a seller's offer plus what it actually
 * costs to get it to this customer's zone.
 */
final class ComparedOffer
{
    /**
     * @param  Collection<int, ShippingQuote>  $quotes
     */
    public function __construct(
        public readonly SellerOffer $offer,
        public readonly Collection $quotes,
        public readonly ?ShippingQuote $selectedQuote,
        public readonly bool $isBestTotal = false,
        public readonly bool $isLowestPrice = false,
    ) {}

    public function withFlags(bool $isBestTotal, bool $isLowestPrice): self
    {
        return new self($this->offer, $this->quotes, $this->selectedQuote, $isBestTotal, $isLowestPrice);
    }

    public function withSelectedQuote(?ShippingQuote $quote): self
    {
        return new self($this->offer, $this->quotes, $quote, $this->isBestTotal, $this->isLowestPrice);
    }

    public function seller(): ?Seller
    {
        return $this->offer->seller;
    }

    public function priceCents(): int
    {
        return $this->offer->price_cents;
    }

    public function shippingCents(): ?int
    {
        return $this->selectedQuote?->priceCents;
    }

    /** Null when this store cannot deliver to the selected zone at all. */
    public function totalCents(): ?int
    {
        if ($this->selectedQuote === null) {
            return null;
        }

        return $this->priceCents() + $this->selectedQuote->priceCents;
    }

    public function deliversToZone(): bool
    {
        return $this->quotes->isNotEmpty();
    }

    /** The cheapest way this seller can deliver, regardless of current sort. */
    public function cheapestQuote(): ?ShippingQuote
    {
        return $this->quotes->first();
    }

    public function fastestQuote(): ?ShippingQuote
    {
        return $this->quotes->sortBy(fn (ShippingQuote $quote) => $quote->speedScore())->first();
    }

    public function deliveryLabel(): ?string
    {
        return $this->selectedQuote?->deliveryLabel();
    }
}
