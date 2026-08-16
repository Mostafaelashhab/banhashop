<?php

namespace App\Services\Cart;

use App\Models\ShippingZone;
use Illuminate\Support\Collection;

final class CartSummary
{
    /**
     * @param  Collection<int, CartGroup>  $groups
     */
    public function __construct(
        public readonly Collection $groups,
        public readonly ?ShippingZone $zone,
    ) {}

    public function isEmpty(): bool
    {
        return $this->groups->isEmpty();
    }

    public function itemsTotalCents(): int
    {
        return (int) $this->groups->sum(fn (CartGroup $g) => $g->subtotalCents());
    }

    public function shippingTotalCents(): int
    {
        return (int) $this->groups->sum(fn (CartGroup $g) => $g->shippingCents() ?? 0);
    }

    public function grandTotalCents(): int
    {
        return $this->itemsTotalCents() + $this->shippingTotalCents();
    }

    public function quantity(): int
    {
        return (int) $this->groups->sum(fn (CartGroup $g) => $g->quantity());
    }

    /** @return array<int, string> */
    public function issues(): array
    {
        return $this->groups->flatMap(fn (CartGroup $g) => $g->issues)->unique()->values()->all();
    }

    public function canCheckout(): bool
    {
        return ! $this->isEmpty()
            && $this->groups->every(fn (CartGroup $g) => $g->canCheckout());
    }

    /** Multi-seller carts split into several deliveries — say so up front. */
    public function isMultiSeller(): bool
    {
        return $this->groups->count() > 1;
    }
}
