<?php

namespace App\Http\Controllers;

use App\Models\Seller;
use App\Services\Catalog\ProductQueryService;
use App\Services\Shipping\ShippingQuoteService;
use App\Services\Shipping\ZoneContext;
use App\Support\Seo\IndexingPolicy;
use App\Support\Seo\JsonLd;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function index(Request $request, SeoData $seo, IndexingPolicy $indexing): View
    {
        $sellers = Seller::query()
            ->active()
            ->with('primaryLocation.zone:id,name')
            ->orderByDesc('active_offers_count')
            ->orderBy('id')
            ->paginate(24);

        $seo->title('متاجر '.config('banha.city'))
            ->description('كل المتاجر المحلية المشاركة في بنها شوب وعدد العروض المتاحة لدى كل منها.')
            ->breadcrumbs([
                ['label' => 'الرئيسية', 'url' => route('home')],
                ['label' => 'المتاجر', 'url' => route('stores.index')],
            ]);

        $indexing->apply($seo, $request, route('stores.index'));

        return view('pages.stores', ['sellers' => $sellers]);
    }

    public function show(
        Request $request,
        Seller $seller,
        ProductQueryService $catalog,
        ZoneContext $zones,
        ShippingQuoteService $shipping,
        SeoData $seo,
        IndexingPolicy $indexing,
    ): View {
        abort_unless($seller->isActive(), 404);

        $seller->load(['primaryLocation.zone:id,name', 'zones:id,name']);

        $products = $catalog->paginate(null, ['seller_id' => $seller->id], 'price_asc');

        $zone = $zones->current();
        $quotes = $zone ? $shipping->for($seller, $zone) : collect();

        $trail = [
            ['label' => 'الرئيسية', 'url' => route('home')],
            ['label' => 'المتاجر', 'url' => route('stores.index')],
            ['label' => $seller->name, 'url' => $seller->url()],
        ];

        $seo->title($seller->meta_title ?: $seller->name.' — '.config('banha.city'))
            ->description($seller->meta_description ?: $seller->description
                ?: 'تصفح عروض '.$seller->name.' في '.config('banha.city').' وقارن السعر النهائي شامل التوصيل.')
            ->breadcrumbs($trail)
            ->addSchema(JsonLd::store($seller, $seller->logo_path ? asset('storage/'.$seller->logo_path) : null));

        $indexing->apply($seo, $request, $seller->url());

        return view('pages.store', [
            'seller' => $seller,
            'products' => $products,
            'quotes' => $quotes,
            'zone' => $zone,
        ]);
    }
}
