<?php

namespace Database\Seeders;

use App\Enums\ShippingProviderType;
use App\Models\ShippingProvider;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            ['name' => 'وسط بنها', 'slug' => 'banha-center', 'position' => 1,
                'description' => 'شارع فريد ندا وما حوله وحتى ميدان المحطة.'],
            ['name' => 'بنها الجديدة', 'slug' => 'new-banha', 'position' => 2,
                'description' => 'الامتداد العمراني الجديد شرق المدينة.'],
            ['name' => 'كفر الجزار', 'slug' => 'kafr-elgazzar', 'position' => 3],
            ['name' => 'ميت راضي', 'slug' => 'mit-rady', 'position' => 4],
            ['name' => 'المناطق المحيطة', 'slug' => 'greater-banha', 'position' => 5,
                'description' => 'طوخ، قها، كفر شكر والقرى القريبة.'],
        ];

        foreach ($zones as $zone) {
            ShippingZone::updateOrCreate(['slug' => $zone['slug']], $zone);
        }

        $providers = [
            [
                'name' => 'توصيل المتجر',
                'slug' => 'store-delivery',
                'type' => ShippingProviderType::Seller->value,
                'description' => 'مندوب المتجر نفسه — السعر يحدده كل متجر.',
                'position' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'سريع بنها',
                'slug' => 'sarie-banha',
                'type' => ShippingProviderType::ThirdParty->value,
                'description' => 'توصيل في نفس اليوم داخل المدينة.',
                'position' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'الوسيط إكسبريس',
                'slug' => 'waseet-express',
                'type' => ShippingProviderType::ThirdParty->value,
                'description' => 'توصيل اقتصادي خلال يوم عمل.',
                'position' => 3,
                'is_active' => true,
            ],
            [
                // Not launched yet. The row exists so the architecture is proven
                // to support an in-house fleet without an order rewrite.
                'name' => 'توصيل بنها شوب',
                'slug' => 'banha-shop-delivery',
                'type' => ShippingProviderType::Platform->value,
                'description' => 'أسطول المنصة — قيد التجهيز.',
                'position' => 4,
                'is_active' => false,
            ],
        ];

        foreach ($providers as $provider) {
            ShippingProvider::updateOrCreate(['slug' => $provider['slug']], $provider);
        }

        $this->seedPlatformRates();
    }

    /**
     * Platform-wide rates for the third-party couriers. Seller-owned rates for
     * "توصيل المتجر" are created per store in SellerSeeder.
     */
    private function seedPlatformRates(): void
    {
        $fast = ShippingProvider::where('slug', 'sarie-banha')->first();
        $cheap = ShippingProvider::where('slug', 'waseet-express')->first();

        // zone slug => [same-day price, next-day price] in EGP
        $matrix = [
            'banha-center' => [35, 22],
            'new-banha' => [40, 25],
            'kafr-elgazzar' => [45, 28],
            'mit-rady' => [45, 28],
            'greater-banha' => [60, 40],
        ];

        foreach ($matrix as $slug => [$fastPrice, $cheapPrice]) {
            $zone = ShippingZone::where('slug', $slug)->first();

            ShippingRate::updateOrCreate(
                [
                    'shipping_provider_id' => $fast->id,
                    'shipping_zone_id' => $zone->id,
                    'seller_id' => null,
                ],
                [
                    'price_cents' => $fastPrice * 100,
                    'eta_min_hours' => 3,
                    'eta_max_hours' => 8,
                    'same_day_cutoff' => '16:00:00',
                    'is_active' => true,
                ]
            );

            ShippingRate::updateOrCreate(
                [
                    'shipping_provider_id' => $cheap->id,
                    'shipping_zone_id' => $zone->id,
                    'seller_id' => null,
                ],
                [
                    'price_cents' => $cheapPrice * 100,
                    'eta_min_hours' => 24,
                    'eta_max_hours' => 36,
                    'is_active' => true,
                ]
            );
        }
    }
}
