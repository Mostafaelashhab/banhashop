<?php

namespace Database\Seeders;

use App\Enums\OfferCondition;
use App\Enums\OfferStatus;
use App\Models\OfferInventoryLog;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerOffer;
use App\Services\Catalog\ProductAggregateUpdater;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Competing offers on shared catalog products.
 *
 * The numbers are chosen so the platform's core claim is visible in the data:
 * on several products the cheapest sticker price is NOT the cheapest real
 * total once each store's delivery is added.
 */
class OfferSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::pluck('id', 'slug');
        $sellers = Seller::pluck('id', 'slug');

        foreach ($this->offers() as [$productSlug, $sellerSlug, $price, $stock, $freshHours, $status, $compareAt]) {
            if (! isset($products[$productSlug], $sellers[$sellerSlug])) {
                continue;
            }

            $updatedAt = Carbon::now()->subHours($freshHours);

            $offer = SellerOffer::updateOrCreate(
                [
                    'product_id' => $products[$productSlug],
                    'seller_id' => $sellers[$sellerSlug],
                    'condition' => OfferCondition::New,
                ],
                [
                    'price_cents' => $price * 100,
                    'compare_at_price_cents' => $compareAt ? $compareAt * 100 : null,
                    'stock' => $stock,
                    'status' => $status,
                    'inventory_updated_at' => $updatedAt,
                    'price_updated_at' => $updatedAt,
                ]
            );

            OfferInventoryLog::firstOrCreate(
                ['seller_offer_id' => $offer->id, 'reason' => OfferInventoryLog::REASON_CREATED],
                [
                    'price_cents_after' => $offer->price_cents,
                    'stock_after' => $offer->stock,
                    'created_at' => $updatedAt,
                ]
            );
        }

        app(ProductAggregateUpdater::class)->refreshProducts(Product::pluck('id')->all());
        app(ProductAggregateUpdater::class)->refreshSellers(Seller::pluck('id')->all());
        app(ProductAggregateUpdater::class)->refreshCategoryCounts();
    }

    /**
     * [product slug, seller slug, price EGP, stock, hours since stock check,
     *  status, compare-at price EGP|null]
     */
    private function offers(): array
    {
        $active = OfferStatus::Active;

        return [
            // Flagship phone: the cheapest price is at بنها موبايل, but
            // الشربيني clears its free-delivery threshold and wins the total.
            ['iphone-17-pro-256gb', 'banha-mobile-center', 61280, 3, 2, $active, null],
            ['iphone-17-pro-256gb', 'el-sherbiny-mobiles', 61290, 5, 1, $active, 63000],
            ['iphone-17-pro-256gb', 'super-store-banha', 62100, 2, 30, $active, null],

            ['iphone-17-pro-512gb', 'el-sherbiny-mobiles', 71500, 2, 4, $active, null],
            ['iphone-17-pro-512gb', 'banha-mobile-center', 71200, 1, 6, $active, null],

            ['samsung-galaxy-s25-256gb', 'banha-mobile-center', 43900, 4, 3, $active, null],
            ['samsung-galaxy-s25-256gb', 'el-sherbiny-mobiles', 44100, 2, 8, $active, 46000],
            ['samsung-galaxy-s25-256gb', 'super-store-banha', 44500, 3, 20, $active, null],

            ['redmi-note-14-pro-256gb', 'banha-mobile-center', 15150, 7, 1, $active, null],
            ['redmi-note-14-pro-256gb', 'el-sherbiny-mobiles', 15300, 6, 5, $active, null],
            // Stale on purpose: 5 days without a stock check is flagged, not hidden.
            ['redmi-note-14-pro-256gb', 'super-store-banha', 15400, 4, 120, $active, null],

            ['oppo-reno-13-256gb', 'el-sherbiny-mobiles', 17900, 3, 6, $active, null],
            ['oppo-reno-13-256gb', 'banha-mobile-center', 18200, 2, 12, $active, null],

            ['infinix-hot-50-128gb', 'banha-mobile-center', 6450, 8, 2, $active, null],
            ['infinix-hot-50-128gb', 'el-sherbiny-mobiles', 6600, 4, 9, $active, 7200],

            ['realme-note-60-128gb', 'el-sherbiny-mobiles', 4250, 5, 3, $active, null],

            ['galaxy-tab-a9-plus-128gb', 'banha-mobile-center', 12900, 2, 7, $active, null],
            ['galaxy-tab-a9-plus-128gb', 'super-store-banha', 13400, 1, 26, $active, null],

            ['anker-powercore-20000', 'techno-house', 1790, 9, 2, $active, null],
            ['anker-powercore-20000', 'el-sherbiny-mobiles', 1850, 12, 4, $active, null],
            ['anker-powercore-20000', 'banha-mobile-center', 1880, 6, 10, $active, null],

            ['anker-65w-gan-charger', 'el-sherbiny-mobiles', 1230, 15, 3, $active, null],
            ['anker-65w-gan-charger', 'techno-house', 1250, 8, 5, $active, null],

            // White goods: الأمين delivers with its own truck at 50 EGP, free
            // above 15,000 — which flips the ranking either way depending on
            // whether the basket clears the threshold.
            ['lg-washing-machine-8kg', 'el-amin-appliances', 18900, 4, 5, $active, 20500],
            ['lg-washing-machine-8kg', 'super-store-banha', 19100, 2, 18, $active, null],

            // Below the threshold: الأمين is cheapest on price and still loses
            // the total to a courier that costs 28 EGP less.
            ['toshiba-washing-machine-7kg', 'el-amin-appliances', 13900, 3, 6, $active, null],
            ['toshiba-washing-machine-7kg', 'super-store-banha', 13920, 5, 14, $active, null],

            ['kiriazi-fridge-16ft', 'el-amin-appliances', 24500, 2, 4, $active, null],
            ['kiriazi-fridge-16ft', 'super-store-banha', 24900, 1, 22, $active, null],

            ['fresh-fridge-14ft', 'super-store-banha', 19200, 3, 9, $active, null],
            ['fresh-fridge-14ft', 'el-amin-appliances', 19250, 2, 11, $active, null],

            ['braun-blender-800w', 'el-amin-appliances', 2150, 6, 7, $active, null],
            ['braun-blender-800w', 'super-store-banha', 2160, 10, 3, $active, null],

            ['tornado-stand-fan-16', 'el-amin-appliances', 899, 12, 8, $active, null],
            ['tornado-stand-fan-16', 'super-store-banha', 915, 20, 2, $active, 1050],

            ['samsung-crystal-uhd-55', 'super-store-banha', 21850, 2, 12, $active, null],
            ['samsung-crystal-uhd-55', 'electronics-world', 21900, 3, 5, $active, 23500],

            ['sharp-4k-50', 'electronics-world', 15400, 4, 6, $active, null],
            ['sharp-4k-50', 'super-store-banha', 15410, 2, 16, $active, null],

            ['galaxy-buds3-white', 'banha-mobile-center', 4290, 5, 3, $active, null],
            ['galaxy-buds3-white', 'electronics-world', 4300, 7, 4, $active, null],
            ['galaxy-buds3-white', 'el-sherbiny-mobiles', 4350, 3, 15, $active, null],

            ['hp-15-i5-16gb', 'techno-house', 32900, 2, 4, $active, 34500],
            ['hp-15-i5-16gb', 'super-store-banha', 33200, 1, 28, $active, null],

            // Real-world states the UI must handle honestly.
            ['oppo-reno-13-256gb', 'super-store-banha', 18100, 0, 20, OfferStatus::OutOfStock, null],
            ['sharp-4k-50', 'techno-house', 15350, 2, 9, OfferStatus::Paused, null],
        ];
    }
}
