<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    /** Submitted by a seller, waiting for the catalog team. */
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Pending => 'بانتظار المراجعة',
            self::Published => 'منشور',
            self::Rejected => 'مرفوض',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Published => 'success',
            self::Pending => 'warning',
            self::Rejected => 'danger',
            self::Draft => 'neutral',
        };
    }
}
