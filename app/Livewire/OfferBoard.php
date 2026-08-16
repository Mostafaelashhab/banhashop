<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ShippingZone;
use App\Services\Cart\CartManager;
use App\Services\Catalog\OfferComparisonService;
use App\Services\Shipping\ZoneContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use RuntimeException;

/**
 * The offer comparison, made reactive.
 *
 * Two properties are load-bearing for the rest of the product:
 *
 *  1. The first paint is still ordinary server-rendered HTML — Livewire only
 *     takes over on interaction — so the offers, prices and totals are in the
 *     initial response for crawlers and for LCP.
 *  2. Pricing still runs through OfferComparisonService. Nothing about
 *     shipping or ranking is reimplemented client-side, so there is exactly
 *     one place the rules live.
 *
 * Sorting and zone switching no longer reload the page; polling keeps stock
 * and prices current while the board is on screen.
 */
class OfferBoard extends Component
{
    /** Locked: the client may not repoint this component at another product. */
    #[Locked]
    public int $productId;

    #[Url(as: 'sort', except: 'total')]
    public string $sort = 'total';

    public ?int $zoneId = null;

    /** Name of the product just added, for the inline confirmation. */
    public ?string $addedLabel = null;

    public ?string $error = null;

    public function mount(Product $product): void
    {
        $this->productId = $product->id;
        $this->zoneId = app(ZoneContext::class)->current()?->id;

        if (! in_array($this->sort, (array) config('banha.ranking.options'), true)) {
            $this->sort = (string) config('banha.ranking.default');
        }
    }

    #[Computed]
    public function product(): Product
    {
        return Product::query()->findOrFail($this->productId);
    }

    #[Computed]
    public function zone(): ?ShippingZone
    {
        return $this->zoneId === null ? null : ShippingZone::find($this->zoneId);
    }

    /** The header zone picker re-prices this board without a reload. */
    #[On('zone-changed')]
    public function zoneChanged(int $zoneId): void
    {
        $this->zoneId = $zoneId;
        $this->addedLabel = null;
        unset($this->zone);
    }

    public function sortBy(string $sort): void
    {
        $this->sort = in_array($sort, (array) config('banha.ranking.options'), true)
            ? $sort
            : (string) config('banha.ranking.default');

        $this->addedLabel = null;
    }

    public function addToCart(int $offerId): void
    {
        $this->error = null;
        $this->addedLabel = null;

        // Re-read the offer rather than trusting anything from the client, and
        // confirm it still belongs to this product.
        $offer = $this->product->offers()->with('product:id,name')->find($offerId);

        if ($offer === null) {
            $this->error = 'هذا العرض لم يعد متاحًا.';

            return;
        }

        try {
            app(CartManager::class)->add($offer);
        } catch (RuntimeException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->addedLabel = $offer->product?->name;
        $this->dispatch('cart-updated');
    }

    /**
     * Called by wire:poll. Recomputing the board is the whole job — dropping
     * the cached zone/product forces fresh prices and stock on every tick.
     */
    public function refreshBoard(): void
    {
        unset($this->product, $this->zone);
    }

    public function render(): View
    {
        $board = app(OfferComparisonService::class)->build($this->product, $this->zone, $this->sort);

        return view('livewire.offer-board', [
            'board' => $board,
            'product' => $this->product,
            'zone' => $this->zone,
        ]);
    }
}
