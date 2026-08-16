<?php

namespace App\Models;

use App\Support\ArabicText;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'shipping_zone_id', 'query_text', 'normalized_key', 'note',
    'contact_phone', 'status', 'product_id',
])]
class ProductRequest extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_SOURCING = 'sourcing';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUS_DECLINED = 'declined';

    protected static function booted(): void
    {
        static::saving(function (self $request): void {
            $request->normalized_key = ArabicText::normalize($request->query_text);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', self::STATUS_OPEN);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_OPEN => 'مفتوح',
            self::STATUS_SOURCING => 'جاري التوفير',
            self::STATUS_FULFILLED => 'تم التوفير',
            self::STATUS_DECLINED => 'مرفوض',
            default => $status,
        };
    }
}
