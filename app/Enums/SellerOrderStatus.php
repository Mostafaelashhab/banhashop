<?php

namespace App\Enums;

enum SellerOrderStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Preparing = 'preparing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار قبول المتجر',
            self::Accepted => 'مقبول',
            self::Preparing => 'قيد التجهيز',
            self::Shipped => 'خرج للتوصيل',
            self::Delivered => 'تم التسليم',
            self::Rejected => 'مرفوض من المتجر',
            self::Cancelled => 'ملغي',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Delivered, self::Accepted => 'success',
            self::Pending, self::Preparing, self::Shipped => 'info',
            self::Rejected, self::Cancelled => 'danger',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Accepted, self::Preparing, self::Shipped], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Rejected, self::Cancelled], true);
    }

    /** Allowed forward transitions from the seller dashboard. */
    public function nextStates(): array
    {
        return match ($this) {
            self::Pending => [self::Accepted, self::Rejected],
            self::Accepted => [self::Preparing, self::Cancelled],
            self::Preparing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            default => [],
        };
    }
}
