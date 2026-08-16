<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cart_id', 'seller_offer_id', 'product_id', 'seller_id', 'quantity', 'unit_price_cents',
])]
class CartItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(SellerOffer::class, 'seller_offer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /** Live price, not the snapshot — the snapshot exists to detect drift. */
    public function currentUnitPrice(): int
    {
        return $this->offer?->price_cents ?? $this->unit_price_cents;
    }

    public function lineTotal(): int
    {
        return $this->currentUnitPrice() * $this->quantity;
    }

    public function priceChanged(): bool
    {
        return $this->offer !== null && $this->offer->price_cents !== $this->unit_price_cents;
    }
}
