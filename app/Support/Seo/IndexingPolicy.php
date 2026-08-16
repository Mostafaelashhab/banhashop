<?php

namespace App\Support\Seo;

use Illuminate\Http\Request;

/**
 * Decides which URLs are allowed into the index.
 *
 * Every filter/sort combination is a URL, and letting all of them be indexed
 * buries the pages that actually matter under near-duplicates. The rule here:
 * a page is indexable only when its query string contains nothing but
 * pagination. Everything else is noindex, follow — crawlable, not indexable —
 * and canonicalises to the clean URL.
 */
class IndexingPolicy
{
    /** @return array{robots: string, canonical: string} */
    public function evaluate(Request $request, ?string $baseUrl = null): array
    {
        $baseUrl ??= $request->url();
        $allowed = (array) config('banha.seo.indexable_query_keys', ['page']);

        $query = $request->query();
        $extra = array_diff(array_keys($query), $allowed);

        $canonicalQuery = array_intersect_key($query, array_flip($allowed));
        // Page 1 is the bare URL — /products and /products?page=1 are one page.
        if (($canonicalQuery['page'] ?? null) === '1') {
            unset($canonicalQuery['page']);
        }

        $canonical = $canonicalQuery === []
            ? $baseUrl
            : $baseUrl.'?'.http_build_query($canonicalQuery);

        return [
            'robots' => $extra === [] ? 'index, follow' : 'noindex, follow',
            'canonical' => $canonical,
        ];
    }

    /** Applies the verdict to the request's SEO object. */
    public function apply(SeoData $seo, Request $request, ?string $baseUrl = null): SeoData
    {
        $verdict = $this->evaluate($request, $baseUrl);

        return $seo->robots($verdict['robots'])->canonical($verdict['canonical']);
    }
}
