<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Placed = 'placed';
    case Confirmed = 'confirmed';
    case InDelivery = 'in_delivery';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Placed => 'تم استلام الطلب',
            self::Confirmed => 'مؤكد',
            self::InDelivery => 'في الطريق',
            self::Completed => 'تم التسليم',
            self::Cancelled => 'ملغي',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Completed, self::Confirmed => 'success',
            self::Placed, self::InDelivery => 'info',
            self::Cancelled => 'danger',
        };
    }
}
