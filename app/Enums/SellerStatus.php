<?php

namespace App\Enums;

enum SellerStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد التفعيل',
            self::Active => 'نشط',
            self::Suspended => 'موقوف',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Pending => 'warning',
            self::Suspended => 'danger',
        };
    }
}
