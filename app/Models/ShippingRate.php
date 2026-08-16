<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One price for one provider delivering to one zone.
 *
 *   seller_id = null -> the provider's platform rate for that zone
 *   seller_id set    -> this store's own rate (self-delivery or negotiated),
 *                       which wins over the platform rate.
 */
#[Fillable([
    'shipping_provider_id', 'shipping_zone_id', 'seller_id', 'price_cents',
    'free_over_cents', 'eta_min_hours', 'eta_max_hours', 'same_day_cutoff', 'is_active',
])]
class ShippingRate extends Model
{
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'free_over_cents' => 'integer',
            'eta_min_hours' => 'integer',
            'eta_max_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ShippingProvider::class, 'shipping_provider_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** Price after the seller's free-delivery threshold is applied. */
    public function priceFor(int $subtotalCents): int
    {
        if ($this->free_over_cents !== null && $subtotalCents >= $this->free_over_cents) {
            return 0;
        }

        return $this->price_cents;
    }
}
