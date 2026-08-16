<?php

namespace Database\Factories;

use App\Enums\OfferCondition;
use App\Enums\OfferStatus;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SellerOffer> */
class SellerOfferFactory extends Factory
{
    protected $model = SellerOffer::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'seller_id' => Seller::factory(),
            'price_cents' => 100000,
            'stock' => 5,
            'condition' => OfferCondition::New,
            'status' => OfferStatus::Active,
            'inventory_updated_at' => now(),
            'price_updated_at' => now(),
        ];
    }

    /** Inventory nobody has confirmed for days — shown, but flagged as stale. */
    public function stale(): static
    {
        return $this->state(['inventory_updated_at' => now()->subDays(7)]);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock' => 0, 'status' => OfferStatus::OutOfStock]);
    }
}
