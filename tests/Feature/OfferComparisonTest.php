<?php

namespace Tests\Feature;

use App\Enums\OfferStatus;
use App\Enums\SellerStatus;
use App\Models\Product;
use App\Services\Catalog\OfferComparisonService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuildsMarketplace;
use Tests\TestCase;

/**
 * The platform's central claim: the cheapest product price is not necessarily
 * the cheapest deal. If these tests pass, the comparison is doing its job.
 */
class OfferComparisonTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_the_cheapest_total_wins_over_the_cheapest_sticker_price(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider('سريع بنها');
        $product = Product::factory()->create();

        // Store A: 920 + 80 delivery = 1,000
        $cheapProduct = $this->makeSellerServing($zone, $courier, 80, sellerScopedRate: true);
        $this->makeOffer($product, $cheapProduct, 920);

        // Store B: 950 + 30 delivery = 980  <- the better deal
        $cheapDelivery = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $this->makeOffer($product, $cheapDelivery, 950);

        $board = app(OfferComparisonService::class)->build($product->fresh(), $zone);

        $this->assertSame(Money::toCents(920), $board->lowestPrice());
        $this->assertSame(Money::toCents(980), $board->cheapestTotal());
        $this->assertSame($cheapDelivery->id, $board->best()->offer->seller_id);
        $this->assertTrue(
            $board->cheapestPriceIsNotBestDeal(),
            'The board must be able to say that the cheapest price is not the best deal.'
        );
    }

    public function test_a_free_delivery_threshold_changes_who_wins(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider('توصيل المتجر');
        $product = Product::factory()->create();

        // Slightly cheaper product, but delivery is always charged.
        $flatRate = $this->makeSellerServing($zone, $courier, 25, sellerScopedRate: true);
        $this->makeOffer($product, $flatRate, 1000);

        // Pricier product, but this basket clears the free-delivery threshold.
        $freeOver = $this->makeSellerServing($zone, $courier, 25, freeOverEgp: 900, sellerScopedRate: true);
        $this->makeOffer($product, $freeOver, 1010);

        $board = app(OfferComparisonService::class)->build($product->fresh(), $zone);

        $this->assertSame($freeOver->id, $board->best()->offer->seller_id);
        $this->assertSame(Money::toCents(1010), $board->cheapestTotal());
    }

    public function test_offers_that_cannot_reach_the_zone_sort_last_and_have_no_total(): void
    {
        $zone = $this->makeZone();
        $otherZone = $this->makeZone('بنها الجديدة');
        $courier = $this->makeProvider();
        $product = Product::factory()->create();

        $reachable = $this->makeSellerServing($zone, $courier, 40, sellerScopedRate: true);
        $this->makeOffer($product, $reachable, 500);

        // Cheapest by far, but only delivers somewhere else.
        $unreachable = $this->makeSellerServing($otherZone, $courier, 10, sellerScopedRate: true);
        $this->makeOffer($product, $unreachable, 300);

        $board = app(OfferComparisonService::class)->build($product->fresh(), $zone);

        $this->assertSame($reachable->id, $board->offers->first()->offer->seller_id);
        $this->assertNull($board->offers->last()->totalCents());
        $this->assertSame(1, $board->deliverableCount());
    }

    public function test_no_best_badge_is_awarded_when_two_offers_tie_on_total(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create();

        $a = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $b = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $this->makeOffer($product, $a, 500);
        $this->makeOffer($product, $b, 500);

        $board = app(OfferComparisonService::class)->build($product->fresh(), $zone);

        $this->assertNull($board->best(), 'A tie must not crown a winner.');
    }

    public function test_sorting_by_fastest_puts_the_earliest_promise_first(): void
    {
        $zone = $this->makeZone();
        $slowCheap = $this->makeProvider('اقتصادي');
        $fastPricey = $this->makeProvider('سريع');
        $product = Product::factory()->create();

        $slowSeller = $this->makeSellerServing($zone, $slowCheap, 20, etaMax: 48, sellerScopedRate: true);
        $this->makeOffer($product, $slowSeller, 500);

        $fastSeller = $this->makeSellerServing($zone, $fastPricey, 60, etaMax: 4, sellerScopedRate: true);
        $this->makeOffer($product, $fastSeller, 500);

        $product = $product->fresh();
        $service = app(OfferComparisonService::class);

        $this->assertSame($slowSeller->id, $service->build($product, $zone, 'total')->offers->first()->offer->seller_id);
        $this->assertSame($fastSeller->id, $service->build($product, $zone, 'fastest')->offers->first()->offer->seller_id);
    }

    public function test_paused_and_out_of_stock_offers_never_appear(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create();

        $seller = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $offer = $this->makeOffer($product, $seller, 500);
        $offer->update(['stock' => 0, 'status' => OfferStatus::OutOfStock]);

        $board = app(OfferComparisonService::class)->build($product->fresh(), $zone);

        $this->assertTrue($board->isEmpty());
        $this->assertSame(0, $product->fresh()->offers_count);
    }

    public function test_offers_from_suspended_sellers_are_hidden(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create();

        $seller = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $this->makeOffer($product, $seller, 500);
        $seller->update(['status' => SellerStatus::Suspended]);

        $board = app(OfferComparisonService::class)->build($product->fresh(), $zone);

        $this->assertTrue($board->isEmpty());
    }
}
