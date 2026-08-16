<?php

namespace App\Http\Controllers;

use App\Services\Catalog\ProductQueryService;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(
        Request $request,
        ProductQueryService $catalog,
        SeoData $seo,
    ): View|RedirectResponse {
        $term = trim((string) $request->query('q', ''));

        // Scanning a barcode should land on the product, not on a result list.
        if ($term !== '' && ($direct = $catalog->findByCode($term)) !== null) {
            return redirect()->to($direct->url());
        }

        $sort = $request->string('sort')->toString() ?: 'relevance';
        $products = $catalog->paginate($term, [], $sort, (int) config('banha.catalog.search_per_page'));

        // Search result pages are never indexable: they are infinite, thin and
        // duplicate the category pages that should rank instead.
        $seo->title($term !== '' ? 'نتائج البحث عن "'.$term.'"' : 'ابحث في منتجات '.config('banha.city'))
            ->description('نتائج البحث داخل متاجر '.config('banha.city').'.')
            ->noindex()
            ->canonical(route('search'));

        return view('pages.search', [
            'term' => $term,
            'products' => $products,
            'sort' => $sort,
        ]);
    }
}
