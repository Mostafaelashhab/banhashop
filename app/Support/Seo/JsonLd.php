<?php

namespace App\Support\Seo;

use App\Models\Product;
use App\Models\Seller;
use App\Services\Catalog\ComparedOffer;
use App\Services\Catalog\OfferBoard;
use App\Support\Money;

/**
 * Server-rendered structured data, built only from data that exists.
 *
 * There is deliberately no aggregateRating and no review markup: Banha.shop
 * has no reviews yet, and inventing them to win a rich result would be fraud
 * against both Google and the customer.
 */
final class JsonLd
{
    /** @param  array<int, array{label: string, url: ?string}>  $trail */
    public static function breadcrumbs(array $trail): array
    {
        $items = [];
        $position = 1;

        foreach ($trail as $crumb) {
            $item = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $crumb['label'],
            ];

            if (! empty($crumb['url'])) {
                $item['item'] = $crumb['url'];
            }

            $items[] = $item;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    public static function product(Product $product, OfferBoard $board, ?string $imageUrl = null): array
    {
        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->displayName(),
            'description' => $product->meta_description ?: $product->description,
            'sku' => $product->mpn,
            'gtin13' => $product->barcode,
            'mpn' => $product->mpn,
            'url' => $product->url(),
            'image' => $imageUrl,
            'brand' => $product->brand ? [
                '@type' => 'Brand',
                'name' => $product->brand->name,
            ] : null,
            'category' => $product->category?->name,
        ]);

        if ($board->isEmpty()) {
            return $schema;
        }

        $offers = $board->offers->map(fn (ComparedOffer $o) => array_filter([
            '@type' => 'Offer',
            'price' => Money::decimal($o->priceCents()),
            'priceCurrency' => config('banha.currency.code'),
            'availability' => 'https://schema.org/InStock',
            'itemCondition' => $o->offer->condition->schemaValue(),
            'url' => $product->url(),
            'seller' => $o->seller() ? [
                '@type' => 'Organization',
                'name' => $o->seller()->name,
                'url' => $o->seller()->url(),
            ] : null,
        ]))->values()->all();

        $prices = $board->offers->map(fn (ComparedOffer $o) => $o->priceCents());

        $schema['offers'] = [
            '@type' => 'AggregateOffer',
            'offerCount' => $board->count(),
            'lowPrice' => Money::decimal((int) $prices->min()),
            'highPrice' => Money::decimal((int) $prices->max()),
            'priceCurrency' => config('banha.currency.code'),
            'offers' => $offers,
        ];

        return $schema;
    }

    public static function store(Seller $seller, ?string $imageUrl = null): array
    {
        $location = $seller->relationLoaded('primaryLocation') ? $seller->primaryLocation : null;

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            'name' => $seller->name,
            'description' => $seller->meta_description ?: $seller->description,
            'url' => $seller->url(),
            'image' => $imageUrl,
            'telephone' => $seller->phone,
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $location?->address_line,
                'addressLocality' => config('banha.city'),
                'addressRegion' => 'القليوبية',
                'addressCountry' => 'EG',
            ]),
        ]);
    }

    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name'),
            'url' => url('/'),
            'inLanguage' => 'ar-EG',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('search').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public static function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('app.name'),
            'url' => url('/'),
            'areaServed' => [
                '@type' => 'City',
                'name' => config('banha.city'),
            ],
        ];
    }

    public static function itemList(iterable $products, string $listName): array
    {
        $items = [];
        $position = 1;

        foreach ($products as $product) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'url' => $product->url(),
                'name' => $product->displayName(),
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $listName,
            'itemListElement' => $items,
        ];
    }
}
