<?php

namespace App\Services\Cart;

use App\Models\CartItem;
use App\Models\Seller;
use App\Services\Shipping\ShippingQuote;
use Illuminate\Support\Collection;

/**
 * Everything from one store in the cart: its lines, its delivery options and
 * its own total. This is also the shape a SellerOrder is built from.
 */
final class CartGroup
{
    /**
     * @param  Collection<int, CartItem>  $items
     * @param  Collection<int, ShippingQuote>  $quotes
     * @param  array<int, string>  $issues
     */
    public function __construct(
        public readonly Seller $seller,
        public readonly Collection $items,
        public readonly Collection $quotes,
        public readonly ?ShippingQuote $selectedQuote,
        public readonly array $issues = [],
    ) {}

    public function subtotalCents(): int
    {
        return $this->items->sum(fn ($item) => $item->lineTotal());
    }

    public function shippingCents(): ?int
    {
        return $this->selectedQuote?->priceCents;
    }

    public function totalCents(): ?int
    {
        $shipping = $this->shippingCents();

        return $shipping === null ? null : $this->subtotalCents() + $shipping;
    }

    public function quantity(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function canCheckout(): bool
    {
        return $this->issues === [] && $this->selectedQuote !== null;
    }

    public function deliversToZone(): bool
    {
        return $this->quotes->isNotEmpty();
    }
}
