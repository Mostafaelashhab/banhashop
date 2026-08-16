<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Support\ArabicText;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Catalog listing and search in one place, so a category page and a search
 * result page apply identical filters, sorting and eager loading.
 *
 * Search runs on MySQL FULLTEXT over the Arabic-folded `search_text` column.
 * That is genuinely enough for a single-city catalog; a dedicated search engine
 * would be infrastructure without a problem to solve.
 */
class ProductQueryService
{
    /** InnoDB ignores tokens shorter than this, so we fall back to LIKE. */
    private const MIN_FULLTEXT_TOKEN = 3;

    public const SORTS = ['relevance', 'price_asc', 'price_desc', 'offers', 'newest'];

    /**
     * @param  array{category_id?: int, brand_id?: int, seller_id?: int, min_price_cents?: int, max_price_cents?: int, available_only?: bool}  $filters
     */
    public function paginate(
        ?string $term = null,
        array $filters = [],
        string $sort = 'relevance',
        ?int $perPage = null
    ): LengthAwarePaginator {
        $perPage ??= (int) config('banha.catalog.per_page', 24);
        $query = $this->baseQuery($filters);

        $tokens = $term ? ArabicText::tokens($term) : [];

        if ($tokens !== []) {
            $this->applySearch($query, $term, $tokens);
        }

        $this->applySort($query, $sort, hasTerm: $tokens !== []);

        return $query->paginate($perPage)->withQueryString();
    }

    /** A direct barcode/SKU hit should jump straight to the product. */
    public function findByCode(string $code): ?Product
    {
        $code = trim($code);

        if ($code === '' || ! preg_match('/^[0-9A-Za-z\-]{6,32}$/', $code)) {
            return null;
        }

        return Product::query()->published()->where('barcode', $code)->first();
    }

    public function baseQuery(array $filters = []): Builder
    {
        $query = Product::query()->published()->forCard();

        if (! empty($filters['category_id'])) {
            $ids = (array) $filters['category_id'];
            $query->whereIn('category_id', $ids);
        }

        if (! empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        if (! empty($filters['seller_id'])) {
            $sellerId = $filters['seller_id'];
            $query->whereHas('activeOffers', fn (Builder $q) => $q->where('seller_id', $sellerId));
        }

        if (! empty($filters['min_price_cents'])) {
            $query->where('min_price_cents', '>=', $filters['min_price_cents']);
        }

        if (! empty($filters['max_price_cents'])) {
            $query->where('min_price_cents', '<=', $filters['max_price_cents']);
        }

        if (! empty($filters['available_only'])) {
            $query->where('offers_count', '>', 0);
        }

        return $query;
    }

    /**
     * Require every token first ("+kettle* +braun*"). If that is too strict we
     * relax to any-token and let the relevance score do the ranking, so a long
     * query degrades instead of returning nothing.
     */
    private function applySearch(Builder $query, string $term, array $tokens): void
    {
        $usable = array_filter($tokens, fn (string $t) => mb_strlen($t) >= self::MIN_FULLTEXT_TOKEN);

        if ($usable === []) {
            $needle = '%'.ArabicText::normalize($term).'%';
            $query->where('search_text', 'like', $needle);

            return;
        }

        $strict = $this->booleanExpression($usable, requireAll: true);

        if ($this->matchCount($query, $strict) > 0) {
            $this->applyMatch($query, $strict);

            return;
        }

        $this->applyMatch($query, $this->booleanExpression($usable, requireAll: false));
    }

    private function booleanExpression(array $tokens, bool $requireAll): string
    {
        return collect($tokens)
            ->map(fn (string $token) => ($requireAll ? '+' : '').$this->escapeToken($token).'*')
            ->implode(' ');
    }

    private function escapeToken(string $token): string
    {
        // Strip the boolean-mode operators so user input can never build a query.
        return preg_replace('/[+\-><\(\)~*\"@]+/u', '', $token) ?? $token;
    }

    private function applyMatch(Builder $query, string $expression): void
    {
        $query
            ->selectRaw('MATCH(search_text) AGAINST(? IN BOOLEAN MODE) as relevance', [$expression])
            ->whereRaw('MATCH(search_text) AGAINST(? IN BOOLEAN MODE)', [$expression]);
    }

    private function matchCount(Builder $query, string $expression): int
    {
        return (clone $query)
            ->whereRaw('MATCH(search_text) AGAINST(? IN BOOLEAN MODE)', [$expression])
            ->toBase()
            ->count();
    }

    private function applySort(Builder $query, string $sort, bool $hasTerm): void
    {
        // Products nobody sells yet stay in the catalog but never outrank a
        // product a customer can actually buy today.
        $query->orderByRaw('offers_count > 0 DESC');

        match ($sort) {
            'price_asc' => $query->orderByRaw('min_price_cents IS NULL')->orderBy('min_price_cents'),
            'price_desc' => $query->orderByDesc('min_price_cents'),
            'offers' => $query->orderByDesc('offers_count'),
            'newest' => $query->orderByDesc('published_at'),
            default => $hasTerm
                ? $query->orderByDesc('relevance')
                : $query->orderByDesc('offers_count'),
        };

        $query->orderBy('products.id');
    }
}
