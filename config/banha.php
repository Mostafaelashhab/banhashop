<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Marketplace identity
    |--------------------------------------------------------------------------
    |
    | Banha.shop launches in a single city. Everything that is city specific
    | lives here so a second city can be introduced without hunting through
    | Blade templates.
    |
    */

    'city' => env('BANHA_CITY', 'بنها'),

    'support_phone' => env('BANHA_SUPPORT_PHONE', '+201000000000'),

    'currency' => [
        'code' => 'EGP',
        'symbol' => 'ج.م',
        // Prices are stored as integer minor units (piastres) everywhere.
        'minor_units' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Inventory trust
    |--------------------------------------------------------------------------
    |
    | An offer whose stock has not been touched for this long is still shown,
    | but it is explicitly flagged as stale so the customer is never misled.
    |
    */

    'inventory' => [
        'stale_after_hours' => (int) env('BANHA_STALE_INVENTORY_HOURS', 48),
        // Offers untouched for this long are moved out of the active pool.
        'expire_after_days' => (int) env('BANHA_EXPIRE_OFFERS_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Offer ranking
    |--------------------------------------------------------------------------
    |
    | The default sort is deterministic and explainable: cheapest real total
    | (price + shipping) wins, ties break on delivery speed, then on the
    | freshest inventory, then on offer id so pagination never jitters.
    |
    */

    'ranking' => [
        'default' => 'total',
        'options' => ['total', 'fastest', 'price'],
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO
    |--------------------------------------------------------------------------
    */

    'seo' => [
        'default_title' => 'بنها شوب — قارن أسعار المتاجر المحلية في بنها',
        'default_description' => 'ابحث عن المنتج، قارن عروض متاجر بنها، واعرف السعر النهائي شامل التوصيل قبل ما تطلب.',
        'og_image' => '/assets/img/og-default.png',
        // Query strings that may appear on an indexable page. Anything else
        // forces a noindex response. See App\Support\Seo\IndexingPolicy.
        'indexable_query_keys' => ['page'],
        'sitemap_chunk' => 5000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Catalog
    |--------------------------------------------------------------------------
    */

    'catalog' => [
        'per_page' => 24,
        'search_per_page' => 24,
        'home_sections' => 8,
    ],
];
