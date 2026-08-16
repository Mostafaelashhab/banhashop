<?php

namespace App\Models;

use App\Enums\ShippingProviderType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'slug', 'logo_path', 'type', 'description', 'is_active', 'position',
])]
#[RouteKey('slug')]
class ShippingProvider extends Model
{
    protected function casts(): array
    {
        return [
            'type' => ShippingProviderType::class,
            'is_active' => 'boolean',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function sellers(): BelongsToMany
    {
        return $this->belongsToMany(Seller::class, 'seller_shipping_provider')
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position');
    }

    public function isSellerOwned(): bool
    {
        return $this->type === ShippingProviderType::Seller;
    }
}
