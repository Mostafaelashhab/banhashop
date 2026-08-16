<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Only pages worth ranking are listed: published products, active categories,
 * active stores and a handful of static pages. Filter/sort/search URLs are
 * deliberately absent — they are noindex by policy and would only dilute the
 * crawl budget.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.index', now()->addHour(), function () {
            $sections = ['pages', 'categories', 'products', 'stores'];
            $body = '';

            foreach ($sections as $section) {
                $body .= '<sitemap><loc>'.e(route('sitemap.section', $section)).'</loc>'
                    .'<lastmod>'.now()->toAtomString().'</lastmod></sitemap>';
            }

            return '<?xml version="1.0" encoding="UTF-8"?>'
                .'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$body.'</sitemapindex>';
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function section(string $section): Response
    {
        abort_unless(in_array($section, ['pages', 'categories', 'products', 'stores'], true), 404);

        $xml = Cache::remember("sitemap.{$section}", now()->addHour(), function () use ($section) {
            $urls = match ($section) {
                'pages' => $this->staticPages(),
                'categories' => $this->categories(),
                'products' => $this->products(),
                'stores' => $this->stores(),
            };

            $body = '';

            foreach ($urls as $url) {
                $body .= '<url><loc>'.e($url['loc']).'</loc>';
                if (! empty($url['lastmod'])) {
                    $body .= '<lastmod>'.$url['lastmod'].'</lastmod>';
                }
                $body .= '<changefreq>'.$url['changefreq'].'</changefreq>'
                    .'<priority>'.$url['priority'].'</priority></url>';
            }

            return '<?xml version="1.0" encoding="UTF-8"?>'
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$body.'</urlset>';
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function staticPages(): array
    {
        return [
            ['loc' => route('home'), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => route('products.index'), 'changefreq' => 'daily', 'priority' => '0.8'],
            ['loc' => route('stores.index'), 'changefreq' => 'weekly', 'priority' => '0.7'],
            ['loc' => route('pages.how-it-works'), 'changefreq' => 'monthly', 'priority' => '0.4'],
            ['loc' => route('sell'), 'changefreq' => 'monthly', 'priority' => '0.5'],
        ];
    }

    private function categories(): array
    {
        return Category::query()->where('is_active', true)->get(['slug', 'updated_at'])
            ->map(fn (Category $c) => [
                'loc' => route('categories.show', $c->slug),
                'lastmod' => $c->updated_at?->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ])->all();
    }

    private function products(): array
    {
        return Product::query()->published()
            ->orderByDesc('offers_count')
            ->limit((int) config('banha.seo.sitemap_chunk', 5000))
            ->get(['slug', 'updated_at', 'offers_count'])
            ->map(fn (Product $p) => [
                'loc' => route('products.show', $p->slug),
                'lastmod' => $p->updated_at?->toAtomString(),
                'changefreq' => 'daily',
                // A product several local stores compete on is worth more
                // crawl attention than one nobody sells yet.
                'priority' => $p->offers_count > 1 ? '0.9' : ($p->offers_count > 0 ? '0.7' : '0.4'),
            ])->all();
    }

    private function stores(): array
    {
        return Seller::query()->active()->get(['slug', 'updated_at'])
            ->map(fn (Seller $s) => [
                'loc' => route('stores.show', $s->slug),
                'lastmod' => $s->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ])->all();
    }
}
