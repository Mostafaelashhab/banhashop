<?php

namespace Tests\Feature;

use App\Enums\OfferStatus;
use App\Models\OfferInventoryLog;
use App\Models\Product;
use App\Models\SellerOffer;
use App\Services\Catalog\OfferInventoryService;
use App\Services\Shipping\ZoneContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuildsMarketplace;
use Tests\TestCase;

/**
 * Availability is a claim a store makes. These tests hold the platform to
 * never presenting a stale claim as a current fact.
 */
class InventoryTrustTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_a_stale_offer_is_shown_but_labelled_as_needing_confirmation(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create();
        $seller = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);

        SellerOffer::factory()->stale()->create([
            'product_id' => $product->id,
            'seller_id' => $seller->id,
            'price_cents' => 50000,
        ]);

        $this->withSession([ZoneContext::SESSION_KEY => $zone->id])
            ->get($product->url())
            ->assertOk()
            ->assertSee('قد يحتاج تأكيد');
    }

    public function test_a_seller_confirming_stock_refreshes_the_timestamp_without_changing_numbers(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create();
        $seller = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);

        $offer = SellerOffer::factory()->stale()->create([
            'product_id' => $product->id,
            'seller_id' => $seller->id,
            'price_cents' => 50000,
            'stock' => 4,
        ]);

        $this->assertTrue($offer->hasStaleInventory());

        $this->actingAs($seller->user)
            ->post(route('seller.offers.confirm', $offer))
            ->assertRedirect();

        $offer->refresh();

        $this->assertFalse($offer->hasStaleInventory());
        $this->assertSame(4, $offer->stock, 'Confirming must not change the number.');
        $this->assertSame(50000, $offer->price_cents);
        $this->assertDatabaseHas('offer_inventory_logs', [
            'seller_offer_id' => $offer->id,
            'reason' => OfferInventoryLog::REASON_SELLER_UPDATE,
        ]);
    }

    public function test_setting_stock_to_zero_moves_the_offer_out_of_the_available_pool(): void
    {
        $product = Product::factory()->create();
        $offer = SellerOffer::factory()->create(['product_id' => $product->id, 'stock' => 3]);

        app(OfferInventoryService::class)->update($offer, ['stock' => 0]);

        $this->assertSame(OfferStatus::OutOfStock, $offer->fresh()->status);
        $this->assertSame(0, $product->fresh()->offers_count);
    }

    public function test_restocking_brings_the_offer_back_automatically(): void
    {
        $product = Product::factory()->create();
        $offer = SellerOffer::factory()->outOfStock()->create(['product_id' => $product->id]);

        app(OfferInventoryService::class)->update($offer, ['stock' => 2]);

        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
        $this->assertSame(1, $product->fresh()->offers_count);
    }

    public function test_a_paused_offer_stays_paused_even_when_restocked(): void
    {
        $product = Product::factory()->create();
        $offer = SellerOffer::factory()->create([
            'product_id' => $product->id,
            'status' => OfferStatus::Paused,
            'stock' => 0,
        ]);

        app(OfferInventoryService::class)->update($offer, ['stock' => 9]);

        $this->assertSame(OfferStatus::Paused, $offer->fresh()->status);
    }

    public function test_offers_nobody_has_touched_for_a_month_are_expired_not_deleted(): void
    {
        $product = Product::factory()->create();
        $fresh = SellerOffer::factory()->create(['product_id' => $product->id]);
        $forgotten = SellerOffer::factory()->create([
            'product_id' => $product->id,
            'inventory_updated_at' => now()->subDays(45),
        ]);

        $expired = app(OfferInventoryService::class)->expireStale();

        $this->assertSame(1, $expired);
        $this->assertSame(OfferStatus::Expired, $forgotten->fresh()->status);
        $this->assertSame(OfferStatus::Active, $fresh->fresh()->status);
        $this->assertDatabaseHas('seller_offers', ['id' => $forgotten->id]);
    }

    public function test_price_and_stock_changes_are_recorded(): void
    {
        $offer = SellerOffer::factory()->create(['price_cents' => 50000, 'stock' => 5]);

        app(OfferInventoryService::class)->update($offer, ['price_cents' => 45000, 'stock' => 3]);

        $this->assertDatabaseHas('offer_inventory_logs', [
            'seller_offer_id' => $offer->id,
            'price_cents_before' => 50000,
            'price_cents_after' => 45000,
            'stock_before' => 5,
            'stock_after' => 3,
        ]);
    }
}
