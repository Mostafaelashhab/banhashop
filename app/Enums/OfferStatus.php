<?php

namespace App\Enums;

enum OfferStatus: string
{
    case Active = 'active';
    case OutOfStock = 'out_of_stock';
    case Paused = 'paused';
    /** Inventory untouched for too long — hidden until the seller confirms. */
    case Expired = 'expired';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'متاح',
            self::OutOfStock => 'غير متوفر',
            self::Paused => 'موقوف مؤقتًا',
            self::Expired => 'يحتاج تحديث',
            self::Rejected => 'مرفوض',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::OutOfStock, self::Expired => 'warning',
            self::Paused => 'neutral',
            self::Rejected => 'danger',
        };
    }

    /** Statuses a seller may set directly from the dashboard. */
    public static function sellerSelectable(): array
    {
        return [self::Active, self::Paused];
    }
}
