<?php

namespace App\Livewire;

use App\Services\Cart\CartManager;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The cart badge in the header. It listens for `cart-updated` so adding an
 * offer from a product page updates the count without a page load.
 */
class CartCounter extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->refreshCount();
    }

    #[On('cart-updated')]
    public function refreshCount(): void
    {
        $this->count = app(CartManager::class)->itemCount();
    }

    public function render(): View
    {
        return view('livewire.cart-counter');
    }
}
