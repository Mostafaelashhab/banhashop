<?php

namespace App\Livewire;

use App\Models\CartItem;
use App\Services\Cart\CartManager;
use App\Services\Cart\CartSummaryBuilder;
use App\Services\Shipping\ZoneContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The cart page. Quantity changes and removals re-price every seller group in
 * place — including delivery, which can cross a free-shipping threshold as the
 * basket grows, so a full re-quote per change is the correct behaviour rather
 * than an optimisation to skip.
 */
class ShoppingCart extends Component
{
    public function updateQuantity(int $itemId, int $quantity): void
    {
        $item = $this->ownedItem($itemId);

        if ($item === null) {
            return;
        }

        app(CartManager::class)->updateQuantity($item, $quantity);
        $this->dispatch('cart-updated');
    }

    public function remove(int $itemId): void
    {
        $item = $this->ownedItem($itemId);

        if ($item === null) {
            return;
        }

        app(CartManager::class)->remove($item);
        $this->dispatch('cart-updated');
    }

    /** Keeps the totals honest if stock or prices moved in another tab. */
    #[On('cart-updated')]
    public function refreshCart(): void
    {
        // Re-rendering is enough; the summary is rebuilt from the database.
    }

    /** A cart line may only be touched from the cart that owns it. */
    private function ownedItem(int $itemId): ?CartItem
    {
        $cart = app(CartManager::class)->current();

        if ($cart === null) {
            return null;
        }

        return $cart->items()->whereKey($itemId)->first();
    }

    public function render(): View
    {
        $carts = app(CartManager::class);
        $zone = app(ZoneContext::class)->current();

        $summary = app(CartSummaryBuilder::class)->build($carts->current(), $carts->items(), $zone);

        return view('livewire.shopping-cart', [
            'summary' => $summary,
            'zone' => $zone,
        ]);
    }
}
