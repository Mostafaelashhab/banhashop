<?php

namespace App\Services\Catalog;

use App\Enums\OfferStatus;
use App\Enums\SellerStatus;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the denormalised offer counters on products and sellers truthful.
 *
 * Listing pages sort and filter on these columns, so they must never be
 * computed in Blade or guessed — they are recomputed from seller_offers
 * whenever an offer changes.
 */
class ProductAggregateUpdater
{
    public function refreshProducts(array $productIds): void
    {
        $productIds = array_values(array_unique(array_filter($productIds)));

        if ($productIds === []) {
            return;
        }

        $aggregates = DB::table('seller_offers')
            ->join('sellers', 'sellers.id', '=', 'seller_offers.seller_id')
            ->whereIn('seller_offers.product_id', $productIds)
            ->where('seller_offers.status', OfferStatus::Active->value)
            ->where('seller_offers.stock', '>', 0)
            ->where('sellers.status', SellerStatus::Active->value)
            ->groupBy('seller_offers.product_id')
            ->get([
                'seller_offers.product_id',
                DB::raw('COUNT(*) as offers_count'),
                DB::raw('COUNT(DISTINCT seller_offers.seller_id) as sellers_count'),
                DB::raw('MIN(seller_offers.price_cents) as min_price_cents'),
                DB::raw('MAX(seller_offers.price_cents) as max_price_cents'),
            ])
            ->keyBy('product_id');

        foreach ($productIds as $productId) {
            $row = $aggregates->get($productId);

            DB::table('products')->where('id', $productId)->update([
                'offers_count' => $row->offers_count ?? 0,
                'sellers_count' => $row->sellers_count ?? 0,
                'min_price_cents' => $row->min_price_cents ?? null,
                'max_price_cents' => $row->max_price_cents ?? null,
            ]);
        }
    }

    public function refreshProduct(int $productId): void
    {
        $this->refreshProducts([$productId]);
    }

    public function refreshSellers(array $sellerIds): void
    {
        $sellerIds = array_values(array_unique(array_filter($sellerIds)));

        if ($sellerIds === []) {
            return;
        }

        $counts = DB::table('seller_offers')
            ->whereIn('seller_id', $sellerIds)
            ->where('status', OfferStatus::Active->value)
            ->where('stock', '>', 0)
            ->groupBy('seller_id')
            ->select('seller_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'seller_id');

        foreach ($sellerIds as $sellerId) {
            DB::table('sellers')->where('id', $sellerId)->update([
                'active_offers_count' => $counts[$sellerId] ?? 0,
            ]);
        }
    }

    public function refreshCategoryCounts(): void
    {
        DB::statement('
            UPDATE categories c
            LEFT JOIN (
                SELECT category_id, COUNT(*) AS total
                FROM products
                WHERE status = ? AND offers_count > 0
                GROUP BY category_id
            ) p ON p.category_id = c.id
            SET c.products_count = COALESCE(p.total, 0)
        ', ['published']);
    }
}
