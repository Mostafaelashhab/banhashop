<?php

namespace App\Models;

use App\Enums\OfferCondition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id', 'seller_order_id', 'product_id', 'seller_offer_id', 'product_name',
    'product_slug', 'variant_label', 'image_path', 'condition', 'unit_price_cents',
    'quantity', 'line_total_cents',
])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'condition' => OfferCondition::class,
            'unit_price_cents' => 'integer',
            'quantity' => 'integer',
            'line_total_cents' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function sellerOrder(): BelongsTo
    {
        return $this->belongsTo(SellerOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Null once an offer is deleted; the snapshot columns still describe it. */
    public function sellerOffer(): BelongsTo
    {
        return $this->belongsTo(SellerOffer::class);
    }
}
