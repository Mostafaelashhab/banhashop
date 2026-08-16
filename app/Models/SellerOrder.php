<?php

namespace App\Models;

use App\Enums\SellerOrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * The unit of fulfilment. One customer order fans out into one of these per
 * store, which is what makes a multi-seller cart a UI change rather than a
 * schema migration.
 */
#[Fillable([
    'order_id', 'seller_id', 'reference', 'items_total_cents', 'shipping_cents',
    'total_cents', 'shipping_provider_id', 'shipping_rate_id', 'shipping_provider_name',
    'eta_min_hours', 'eta_max_hours', 'promised_at', 'status', 'accepted_at',
    'rejected_at', 'rejection_reason', 'shipped_at', 'delivered_at', 'cancelled_at',
])]
class SellerOrder extends Model
{
    protected function casts(): array
    {
        return [
            'status' => SellerOrderStatus::class,
            'items_total_cents' => 'integer',
            'shipping_cents' => 'integer',
            'total_cents' => 'integer',
            'promised_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    public function shippingProvider(): BelongsTo
    {
        return $this->belongsTo(ShippingProvider::class, 'shipping_provider_id');
    }

    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereIn('status', [
            SellerOrderStatus::Pending->value,
            SellerOrderStatus::Accepted->value,
            SellerOrderStatus::Preparing->value,
            SellerOrderStatus::Shipped->value,
        ]);
    }

    public function canTransitionTo(SellerOrderStatus $target): bool
    {
        return in_array($target, $this->status->nextStates(), true);
    }
}
