<?php

namespace App\Enums;

enum OfferCondition: string
{
    case New = 'new';
    case Refurbished = 'refurbished';
    case Used = 'used';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جديد',
            self::Refurbished => 'مجدّد',
            self::Used => 'مستعمل',
        };
    }

    /** schema.org OfferItemCondition. */
    public function schemaValue(): string
    {
        return match ($this) {
            self::New => 'https://schema.org/NewCondition',
            self::Refurbished => 'https://schema.org/RefurbishedCondition',
            self::Used => 'https://schema.org/UsedCondition',
        };
    }
}
