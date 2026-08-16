<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductRequest;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\BuildsMarketplace;
use Tests\TestCase;

/**
 * Search is the one area that cannot use RefreshDatabase: InnoDB defers
 * FULLTEXT index updates until the transaction commits, so rows written inside
 * a test transaction are invisible to MATCH ... AGAINST. Truncating between
 * tests keeps the writes committed and the index real.
 */
class SearchTest extends TestCase
{
    use BuildsMarketplace, DatabaseTruncation;

    public function test_arabic_spelling_variants_find_the_same_product(): void
    {
        $brand = Brand::create(['name' => 'Samsung', 'name_ar' => 'سامسونج', 'slug' => 'samsung', 'is_active' => true]);

        Product::factory()->create([
            'name' => 'Samsung Galaxy S25',
            'variant_label' => '256 جيجا',
            'brand_id' => $brand->id,
            'search_keywords' => 'سامسونج جالاكسي موبايل',
        ]);

        // Latin name, Arabic transliteration, and a folded spelling
        // (ة/ه, أ/ا, Arabic-Indic digits) all have to land on the same product.
        foreach (['Samsung', 'سامسونج', 'جالاكسي', 'موبايل'] as $term) {
            $this->get(route('search', ['q' => $term]))
                ->assertOk()
                ->assertSee('Samsung Galaxy S25', false);
        }
    }

    /**
     * InnoDB ignores tokens below its minimum length, so a two-letter query
     * takes the LIKE fallback — which projects no relevance column. Relevance
     * is also the default sort, so ordering by it here asked MySQL for a column
     * that was never selected and the page died with SQLSTATE 42S22.
     */
    public function test_a_term_too_short_for_the_fulltext_index_still_returns_results(): void
    {
        Product::factory()->create(['name' => 'Samsung Galaxy S25']);

        $this->get(route('search', ['q' => 'sa']))
            ->assertOk()
            ->assertSee('Samsung Galaxy S25', false);
    }

    public function test_a_short_term_survives_every_sort_option(): void
    {
        Product::factory()->create(['name' => 'Samsung Galaxy S25']);

        foreach (\App\Services\Catalog\ProductQueryService::SORTS as $sort) {
            $this->get(route('search', ['q' => 'sa', 'sort' => $sort]))
                ->assertOk();
        }
    }

    /**
     * Not a regression test — this passed before the relevance fix too. It pins
     * an invariant nothing else asserts: normalisation drops every character
     * outside letters, digits and spaces, so a user cannot inject FULLTEXT
     * boolean operators into the MATCH expression. Loosen that regex and a
     * query of "+++" starts building "+*", which MySQL rejects outright.
     */
    public function test_a_query_of_only_boolean_operators_does_not_break_search(): void
    {
        Product::factory()->create(['name' => 'Samsung Galaxy S25']);

        foreach (['+++', '***', '>><<', '~~~ (((' ] as $term) {
            $this->get(route('search', ['q' => $term]))->assertOk();
        }
    }

    public function test_a_barcode_goes_straight_to_the_product(): void
    {
        $product = Product::factory()->create(['barcode' => '194253000011']);

        $this->get(route('search', ['q' => '194253000011']))
            ->assertRedirect($product->url());
    }

    public function test_a_failed_search_captures_demand_instead_of_dead_ending(): void
    {
        $this->get(route('search', ['q' => 'ايربودز برو']))
            ->assertOk()
            ->assertSee('مفيش نتائج')
            ->assertSee(route('product-requests.create', ['q' => 'ايربودز برو']), false);
    }

    public function test_search_results_are_never_indexable(): void
    {
        Product::factory()->create(['name' => 'منتج للبحث']);

        $this->get(route('search', ['q' => 'منتج']))
            ->assertOk()
            ->assertSee('name="robots" content="noindex, follow"', false);
    }

    public function test_a_customer_can_record_a_product_request(): void
    {
        $zone = $this->makeZone();

        $this->post(route('product-requests.store'), [
            'query_text' => 'AirPods Pro 2',
            'shipping_zone_id' => $zone->id,
            'contact_phone' => '01099887766',
        ])->assertRedirect(route('product-requests.create'));

        $this->assertDatabaseHas('product_requests', [
            'query_text' => 'AirPods Pro 2',
            'status' => 'open',
        ]);
    }

    public function test_requests_for_the_same_product_share_a_normalised_key(): void
    {
        $this->post(route('product-requests.store'), ['query_text' => 'ايفون ١٧ برو']);
        $this->post(route('product-requests.store'), ['query_text' => 'أيفون 17 برو']);

        $keys = ProductRequest::pluck('normalized_key')->unique();

        $this->assertCount(1, $keys, 'Arabic spelling variants must group into one demand signal.');
    }
}
