<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only trail of price/stock changes. Reliability metrics may only ever
 * be computed from rows like these, never estimated.
 */
#[Fillable([
    'seller_offer_id', 'user_id', 'reason',
    'price_cents_before', 'price_cents_after',
    'stock_before', 'stock_after',
])]
class OfferInventoryLog extends Model
{
    public const UPDATED_AT = null;

    public const REASON_CREATED = 'created';

    public const REASON_SELLER_UPDATE = 'seller_update';

    public const REASON_ORDER = 'order';

    public const REASON_ADMIN = 'admin';

    public const REASON_EXPIRED = 'expired';

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(SellerOffer::class, 'seller_offer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
