<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'shipping_zone_id', 'label', 'recipient_name', 'phone', 'street',
    'building', 'floor', 'apartment', 'landmark', 'notes', 'is_default',
])]
class Address extends Model
{
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function summary(): string
    {
        $parts = array_filter([
            $this->street,
            $this->building ? 'عقار '.$this->building : null,
            $this->floor ? 'الدور '.$this->floor : null,
            $this->apartment ? 'شقة '.$this->apartment : null,
        ]);

        return implode('، ', $parts);
    }
}
