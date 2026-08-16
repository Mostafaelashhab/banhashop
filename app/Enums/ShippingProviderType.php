<?php

namespace App\Enums;

enum ShippingProviderType: string
{
    /** A future in-house fleet — Banha.shop Delivery. */
    case Platform = 'platform';
    case ThirdParty = 'third_party';
    /** The store delivers with its own driver; the rate belongs to the seller. */
    case Seller = 'seller';

    public function label(): string
    {
        return match ($this) {
            self::Platform => 'توصيل بنها شوب',
            self::ThirdParty => 'شركة توصيل',
            self::Seller => 'توصيل المتجر',
        };
    }

    /** Seller-owned providers price per seller, not platform-wide. */
    public function requiresSellerRate(): bool
    {
        return $this === self::Seller;
    }
}
