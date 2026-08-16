<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'number', 'user_id', 'customer_name', 'customer_phone', 'customer_email',
    'shipping_zone_id', 'shipping_zone_name', 'shipping_street', 'shipping_building',
    'shipping_floor', 'shipping_apartment', 'shipping_landmark', 'shipping_notes',
    'items_total_cents', 'shipping_total_cents', 'grand_total_cents',
    'status', 'payment_method', 'payment_status', 'placed_at', 'completed_at',
    'cancelled_at', 'cancel_reason',
])]
#[RouteKey('number')]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'items_total_cents' => 'integer',
            'shipping_total_cents' => 'integer',
            'grand_total_cents' => 'integer',
            'placed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sellerOrders(): HasMany
    {
        return $this->hasMany(SellerOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('created_at');
    }

    public function shippingAddressLine(): string
    {
        $parts = array_filter([
            $this->shipping_street,
            $this->shipping_building ? 'عقار '.$this->shipping_building : null,
            $this->shipping_floor ? 'الدور '.$this->shipping_floor : null,
            $this->shipping_apartment ? 'شقة '.$this->shipping_apartment : null,
            $this->shipping_zone_name,
        ]);

        return implode('، ', $parts);
    }

    public function url(): string
    {
        return route('orders.show', $this->number);
    }
}
