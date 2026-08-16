<?php

namespace App\Enums;

/**
 * Only cash on delivery ships in the MVP. The enum exists so adding Instapay
 * or a card gateway is a new case plus a driver, not an order rewrite.
 */
enum PaymentMethod: string
{
    case Cod = 'cod';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'الدفع عند الاستلام',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Cod => 'ادفع نقدًا لمندوب التوصيل عند استلام طلبك.',
        };
    }

    /** Methods currently offered at checkout. */
    public static function available(): array
    {
        return [self::Cod];
    }
}
