<?php

namespace Tests;

use App\Enums\ShippingProviderType;
use App\Models\Address;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerOffer;
use App\Models\ShippingProvider;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Str;

/**
 * Scenario helpers. Marketplace behaviour only means something when a zone, a
 * courier, a rate, a store and an offer all line up, so tests build that whole
 * chain rather than asserting on isolated rows.
 */
trait BuildsMarketplace
{
    protected function makeZone(string $name = 'وسط بنها'): ShippingZone
    {
        return ShippingZone::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'is_active' => true,
        ]);
    }

    protected function makeProvider(string $name = 'شركة توصيل', ShippingProviderType $type = ShippingProviderType::ThirdParty): ShippingProvider
    {
        return ShippingProvider::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'type' => $type,
            'is_active' => true,
        ]);
    }

    /**
     * A store that delivers to $zone through $provider at $priceEgp.
     */
    protected function makeSellerServing(
        ShippingZone $zone,
        ShippingProvider $provider,
        float $priceEgp,
        ?float $freeOverEgp = null,
        int $etaMax = 24,
        ?Seller $seller = null,
        bool $sellerScopedRate = false,
    ): Seller {
        $seller ??= Seller::factory()->create();

        $seller->zones()->syncWithoutDetaching([$zone->id]);
        $seller->shippingProviders()->syncWithoutDetaching([$provider->id => ['is_enabled' => true]]);

        ShippingRate::updateOrCreate(
            [
                'shipping_provider_id' => $provider->id,
                'shipping_zone_id' => $zone->id,
                'seller_id' => $sellerScopedRate ? $seller->id : null,
            ],
            [
                'price_cents' => Money::toCents($priceEgp),
                'free_over_cents' => $freeOverEgp !== null ? Money::toCents($freeOverEgp) : null,
                'eta_min_hours' => max(1, $etaMax - 2),
                'eta_max_hours' => $etaMax,
                'is_active' => true,
            ]
        );

        return $seller;
    }

    protected function makeOffer(Product $product, Seller $seller, float $priceEgp, int $stock = 5): SellerOffer
    {
        return SellerOffer::factory()->create([
            'product_id' => $product->id,
            'seller_id' => $seller->id,
            'price_cents' => Money::toCents($priceEgp),
            'stock' => $stock,
        ]);
    }

    protected function makeCustomerWithAddress(ShippingZone $zone): array
    {
        $user = User::factory()->create();

        $address = Address::create([
            'user_id' => $user->id,
            'shipping_zone_id' => $zone->id,
            'recipient_name' => 'منى صلاح',
            'phone' => '01099887766',
            'street' => 'شارع فريد ندا',
            'is_default' => true,
        ]);

        return [$user, $address];
    }
}
