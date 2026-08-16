<?php

namespace App\Models;

use App\Enums\OfferCondition;
use App\Enums\OfferStatus;
use App\Observers\SellerOfferObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A store's claim about one catalog product: this price, this many in stock,
 * as of this moment. The "as of" part is not decoration — a stale offer is
 * marked stale rather than quietly presented as available.
 */
#[Fillable([
    'product_id', 'seller_id', 'price_cents', 'compare_at_price_cents', 'stock',
    'sku', 'condition', 'status', 'note', 'inventory_updated_at', 'price_updated_at',
])]
#[ObservedBy(SellerOfferObserver::class)]
class SellerOffer extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'condition' => OfferCondition::class,
            'status' => OfferStatus::class,
            'price_cents' => 'integer',
            'compare_at_price_cents' => 'integer',
            'stock' => 'integer',
            'inventory_updated_at' => 'datetime',
            'price_updated_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(OfferInventoryLog::class);
    }

    #[Scope]
    protected function purchasable(Builder $query): void
    {
        $query->where('status', OfferStatus::Active)->where('stock', '>', 0);
    }

    #[Scope]
    protected function fromActiveSellers(Builder $query): void
    {
        $query->whereHas('seller', fn (Builder $q) => $q->active());
    }

    public function isPurchasable(): bool
    {
        return $this->status === OfferStatus::Active && $this->stock > 0;
    }

    public function staleAfterHours(): int
    {
        return (int) config('banha.inventory.stale_after_hours', 48);
    }

    /** True when we can no longer vouch for this stock number. */
    public function hasStaleInventory(): bool
    {
        if ($this->inventory_updated_at === null) {
            return true;
        }

        return $this->inventory_updated_at->lt(Carbon::now()->subHours($this->staleAfterHours()));
    }

    /** "منذ ١٤ دقيقة" — always derived from a real timestamp. */
    public function inventoryAge(): ?string
    {
        return $this->inventory_updated_at?->diffForHumans();
    }

    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock <= 3;
    }

    public function hasDiscount(): bool
    {
        return $this->compare_at_price_cents !== null
            && $this->compare_at_price_cents > $this->price_cents;
    }

    public function discountPercent(): ?int
    {
        if (! $this->hasDiscount()) {
            return null;
        }

        $off = ($this->compare_at_price_cents - $this->price_cents) / $this->compare_at_price_cents;

        return (int) round($off * 100);
    }
}
