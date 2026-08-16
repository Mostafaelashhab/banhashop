<?php

namespace App\Models;

use App\Enums\OfferStatus;
use App\Enums\SellerStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id', 'name', 'slug', 'description', 'logo_path', 'phone', 'whatsapp',
    'status', 'is_verified', 'onboarded_by_admin', 'meta_title', 'meta_description',
])]
#[RouteKey('slug')]
class Seller extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => SellerStatus::class,
            'is_verified' => 'boolean',
            'onboarded_by_admin' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(SellerLocation::class);
    }

    public function primaryLocation(): HasOne
    {
        return $this->hasOne(SellerLocation::class)->where('is_primary', true);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(SellerOffer::class);
    }

    public function activeOffers(): HasMany
    {
        return $this->hasMany(SellerOffer::class)
            ->where('status', OfferStatus::Active)
            ->where('stock', '>', 0);
    }

    /** Zones this store is willing to deliver to. */
    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(ShippingZone::class, 'seller_zone')->withTimestamps();
    }

    public function shippingProviders(): BelongsToMany
    {
        return $this->belongsToMany(ShippingProvider::class, 'seller_shipping_provider')
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    /** Seller-scoped rates: self-delivery pricing, or a negotiated override. */
    public function shippingRates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function sellerOrders(): HasMany
    {
        return $this->hasMany(SellerOrder::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', SellerStatus::Active);
    }

    public function isActive(): bool
    {
        return $this->status === SellerStatus::Active;
    }

    public function servesZone(int $zoneId): bool
    {
        return $this->zones()->whereKey($zoneId)->exists();
    }

    /**
     * Share of orders this store accepted. Returns null until there is enough
     * real data — the platform never displays an invented trust number.
     */
    public function acceptanceRate(): ?float
    {
        if ($this->orders_count < 5) {
            return null;
        }

        return round($this->accepted_orders_count / $this->orders_count * 100, 1);
    }

    public function url(): string
    {
        return route('stores.show', $this->slug);
    }
}
