<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Support\Seo\JsonLd;
use App\Support\Seo\SeoData;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(SeoData $seo): View
    {
        $seo->title(null)
            ->description(config('banha.seo.default_description'))
            ->canonical(route('home'))
            ->addSchema(JsonLd::website())
            ->addSchema(JsonLd::organization());

        // Four indexed, limited queries. Caching Eloquent objects would trade
        // that for stale data and a serialization round trip — not a win here.
        return view('pages.home', [
            'categories' => Category::query()->active()->roots()
                ->orderBy('position')->limit(12)->get(['id', 'name', 'slug', 'products_count']),

            // The marketplace metric that matters, surfaced to customers:
            // products where local stores actually compete.
            'competitive' => Product::query()->published()->forCard()
                ->where('sellers_count', '>', 1)
                ->orderByDesc('sellers_count')->orderBy('id')
                ->limit(8)->get(),

            'newest' => Product::query()->published()->forCard()->withOffers()
                ->orderByDesc('published_at')->orderBy('id')
                ->limit(8)->get(),

            'stores' => Seller::query()->active()
                ->where('active_offers_count', '>', 0)
                ->orderByDesc('active_offers_count')
                ->limit(6)->get(['id', 'name', 'slug', 'logo_path', 'is_verified', 'active_offers_count']),
        ]);
    }
}
