<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\Shipping\ZoneContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuildsMarketplace;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_the_important_content_is_in_the_server_rendered_html(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider('سريع بنها');
        $product = Product::factory()->create(['name' => 'غسالة اختبار']);

        $seller = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $this->makeOffer($product, $seller, 950);

        $response = $this->withSession([ZoneContext::SESSION_KEY => $zone->id])
            ->get($product->url());

        $response->assertOk()
            ->assertSee('غسالة اختبار')
            ->assertSee($seller->name)
            ->assertSee('950')          // product price
            ->assertSee('30')           // delivery
            ->assertSee('980')          // the real total
            ->assertSee('سريع بنها');   // who delivers it
    }

    public function test_a_product_with_no_offers_offers_a_useful_next_step(): void
    {
        $product = Product::factory()->create(['name' => 'منتج بدون عروض']);

        $this->get($product->url())
            ->assertOk()
            ->assertSee('لا توجد عروض متاحة لهذا المنتج حاليًا')
            ->assertSee(route('product-requests.create', ['q' => $product->name]), false);
    }

    public function test_the_page_carries_a_canonical_url_and_product_structured_data(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create();
        $seller = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $this->makeOffer($product, $seller, 500);

        $html = $this->withSession([ZoneContext::SESSION_KEY => $zone->id])
            ->get($product->url())
            ->assertOk()
            ->assertSee('rel="canonical" href="'.$product->url().'"', false)
            ->assertSee('name="robots" content="index, follow"', false)
            ->getContent();

        $this->assertStringContainsString('"@type":"Product"', $html);
        $this->assertStringContainsString('"@type":"AggregateOffer"', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);

        // Never invent social proof for a rich result.
        $this->assertStringNotContainsString('aggregateRating', $html);
        $this->assertStringNotContainsString('"@type":"Review"', $html);
    }

    public function test_a_sorted_view_is_crawlable_but_not_indexable_and_canonicalises_back(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create();
        $seller = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $this->makeOffer($product, $seller, 500);

        $this->withSession([ZoneContext::SESSION_KEY => $zone->id])
            ->get($product->url().'?sort=fastest')
            ->assertOk()
            ->assertSee('name="robots" content="noindex, follow"', false)
            ->assertSee('rel="canonical" href="'.$product->url().'"', false);
    }

    public function test_an_unpublished_product_is_not_reachable(): void
    {
        $product = Product::factory()->draft()->create();

        $this->get($product->url())->assertNotFound();
    }

    public function test_changing_the_delivery_zone_reprices_the_page(): void
    {
        $near = $this->makeZone('وسط بنها');
        $far = $this->makeZone('المناطق المحيطة');
        $courier = $this->makeProvider();
        $product = Product::factory()->create();

        $seller = $this->makeSellerServing($near, $courier, 20, sellerScopedRate: true);
        $this->makeSellerServing($far, $courier, 90, seller: $seller, sellerScopedRate: true);
        $this->makeOffer($product, $seller, 500);

        $this->withSession([ZoneContext::SESSION_KEY => $near->id])
            ->get($product->url())->assertOk()->assertSee('520');

        $this->withSession([ZoneContext::SESSION_KEY => $far->id])
            ->get($product->url())->assertOk()->assertSee('590');
    }
}
