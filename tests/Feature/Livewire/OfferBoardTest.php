<?php

namespace Tests\Feature\Livewire;

use App\Enums\OfferStatus;
use App\Livewire\OfferBoard;
use App\Models\Product;
use App\Services\Shipping\ZoneContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\BuildsMarketplace;
use Tests\TestCase;

/**
 * The comparison is now reactive, but the rules must not have moved: ranking
 * and pricing still come from the domain services, and the first paint is
 * still server-rendered HTML.
 */
class OfferBoardTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_sorting_reranks_without_leaving_the_page(): void
    {
        $zone = $this->makeZone();
        $slow = $this->makeProvider('اقتصادي');
        $fast = $this->makeProvider('سريع');
        $product = Product::factory()->create();

        $cheap = $this->makeSellerServing($zone, $slow, 20, etaMax: 48, sellerScopedRate: true);
        $this->makeOffer($product, $cheap, 500);

        $quick = $this->makeSellerServing($zone, $fast, 60, etaMax: 4, sellerScopedRate: true);
        $this->makeOffer($product, $quick, 500);

        Livewire::test(OfferBoard::class, ['product' => $product])
            ->assertSet('sort', 'total')
            ->assertSeeHtmlInOrder([$cheap->name, $quick->name])
            ->call('sortBy', 'fastest')
            ->assertSet('sort', 'fastest')
            ->assertSeeHtmlInOrder([$quick->name, $cheap->name]);
    }

    public function test_an_unknown_sort_falls_back_to_the_default(): void
    {
        $product = Product::factory()->create();

        Livewire::test(OfferBoard::class, ['product' => $product])
            ->call('sortBy', 'cheapest-by-vibes')
            ->assertSet('sort', 'total');
    }

    public function test_changing_the_zone_in_the_header_reprices_the_board(): void
    {
        $near = $this->makeZone('وسط بنها');
        $far = $this->makeZone('المناطق المحيطة');
        $courier = $this->makeProvider();
        $product = Product::factory()->create();

        $seller = $this->makeSellerServing($near, $courier, 20, sellerScopedRate: true);
        $this->makeSellerServing($far, $courier, 90, seller: $seller, sellerScopedRate: true);
        $this->makeOffer($product, $seller, 500);

        session([ZoneContext::SESSION_KEY => $near->id]);

        Livewire::test(OfferBoard::class, ['product' => $product])
            ->assertSee('520')
            ->dispatch('zone-changed', zoneId: $far->id)
            ->assertSee('590')
            ->assertDontSee('520');
    }

    public function test_adding_to_cart_announces_the_change_for_the_header(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create(['name' => 'غسالة اختبار']);
        $seller = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $offer = $this->makeOffer($product, $seller, 500);

        [$user] = $this->makeCustomerWithAddress($zone);

        session([ZoneContext::SESSION_KEY => $zone->id]);

        Livewire::actingAs($user)
            ->test(OfferBoard::class, ['product' => $product])
            ->call('addToCart', $offer->id)
            ->assertDispatched('cart-updated')
            ->assertSee('تمت إضافة');

        $this->assertDatabaseHas('cart_items', ['seller_offer_id' => $offer->id, 'quantity' => 1]);
    }

    /** The offer id comes from the client, so it is never trusted. */
    public function test_an_offer_belonging_to_another_product_cannot_be_added(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create();
        $otherProduct = Product::factory()->create();
        $seller = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);

        $this->makeOffer($product, $seller, 500);
        $foreignOffer = $this->makeOffer($otherProduct, $seller, 100);

        [$user] = $this->makeCustomerWithAddress($zone);

        Livewire::actingAs($user)
            ->test(OfferBoard::class, ['product' => $product])
            ->call('addToCart', $foreignOffer->id)
            ->assertNotDispatched('cart-updated')
            ->assertSee('لم يعد متاحًا');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_adding_a_sold_out_offer_reports_it_instead_of_failing(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create();
        $seller = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $offer = $this->makeOffer($product, $seller, 500);
        $offer->update(['stock' => 0, 'status' => OfferStatus::OutOfStock]);

        [$user] = $this->makeCustomerWithAddress($zone);

        Livewire::actingAs($user)
            ->test(OfferBoard::class, ['product' => $product])
            ->call('addToCart', $offer->id)
            ->assertNotDispatched('cart-updated');

        $this->assertDatabaseCount('cart_items', 0);
    }

    /** wire:poll calls this; it must pick up a price a seller just changed. */
    public function test_polling_picks_up_a_price_change(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create();
        $seller = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $offer = $this->makeOffer($product, $seller, 500);

        session([ZoneContext::SESSION_KEY => $zone->id]);

        $component = Livewire::test(OfferBoard::class, ['product' => $product])
            ->assertSee('530');

        $offer->update(['price_cents' => 40000]);

        $component->call('refreshBoard')->assertSee('430')->assertDontSee('530');
    }

    public function test_the_product_cannot_be_swapped_from_the_client(): void
    {
        $product = Product::factory()->create();
        $other = Product::factory()->create();

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(OfferBoard::class, ['product' => $product])
            ->set('productId', $other->id);
    }
}
