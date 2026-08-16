<?php

namespace Tests\Unit;

use App\Support\Seo\IndexingPolicy;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Faceted URLs multiply without limit. The policy's job is to keep exactly one
 * indexable URL per real page and point every variant back at it.
 */
class IndexingPolicyTest extends TestCase
{
    public function test_a_clean_url_is_indexable_and_self_canonical(): void
    {
        $verdict = $this->evaluate('http://banha.shop/categories/mobile-phones');

        $this->assertSame('index, follow', $verdict['robots']);
        $this->assertSame('http://banha.shop/categories/mobile-phones', $verdict['canonical']);
    }

    public function test_pagination_stays_indexable_and_keeps_its_page_number(): void
    {
        $verdict = $this->evaluate('http://banha.shop/products?page=3');

        $this->assertSame('index, follow', $verdict['robots']);
        $this->assertSame('http://banha.shop/products?page=3', $verdict['canonical']);
    }

    public function test_page_one_canonicalises_to_the_bare_url(): void
    {
        $verdict = $this->evaluate('http://banha.shop/products?page=1');

        $this->assertSame('http://banha.shop/products', $verdict['canonical']);
    }

    public function test_sort_and_filter_urls_are_crawlable_but_not_indexable(): void
    {
        foreach (['?sort=price_asc', '?brand=4', '?brand=4&sort=price_asc', '?min=100&max=900'] as $query) {
            $verdict = $this->evaluate('http://banha.shop/products'.$query);

            $this->assertSame('noindex, follow', $verdict['robots'], "Failed on: {$query}");
            $this->assertSame(
                'http://banha.shop/products',
                $verdict['canonical'],
                "A faceted URL must canonicalise back to the clean page: {$query}"
            );
        }
    }

    public function test_a_facet_combined_with_pagination_keeps_only_the_page(): void
    {
        $verdict = $this->evaluate('http://banha.shop/products?sort=price_asc&page=2');

        $this->assertSame('noindex, follow', $verdict['robots']);
        $this->assertSame('http://banha.shop/products?page=2', $verdict['canonical']);
    }

    /** @return array{robots: string, canonical: string} */
    private function evaluate(string $url): array
    {
        return (new IndexingPolicy)->evaluate(Request::create($url));
    }
}
