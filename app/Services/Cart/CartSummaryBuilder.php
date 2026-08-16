<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ShippingZone;
use App\Services\Shipping\ShippingQuote;
use App\Services\Shipping\ShippingQuoteService;
use Illuminate\Support\Collection;

/**
 * Prices a cart against a destination zone: groups lines per seller, quotes
 * delivery per group, and surfaces anything that would make checkout fail
 * before the customer reaches the last step.
 */
class CartSummaryBuilder
{
    public function __construct(private readonly ShippingQuoteService $shipping) {}

    /**
     * @param  Collection<int, CartItem>  $items
     * @param  array<int, int>  $selections  seller id => chosen shipping_rate id
     */
    public function build(?Cart $cart, Collection $items, ?ShippingZone $zone, array $selections = []): CartSummary
    {
        if ($items->isEmpty()) {
            return new CartSummary(collect(), $zone);
        }

        $bySeller = $items->groupBy('seller_id');
        $sellers = $items->pluck('seller')->filter()->unique('id')->values();

        $subtotals = $bySeller
            ->map(fn (Collection $group) => (int) $group->sum(fn (CartItem $i) => $i->lineTotal()))
            ->all();

        $quotesBySeller = $zone
            ? $this->shipping->forMany($sellers, $zone, $subtotals)
            : collect();

        $groups = $sellers->map(function ($seller) use ($bySeller, $quotesBySeller, $selections, $zone) {
            /** @var Collection<int, CartItem> $sellerItems */
            $sellerItems = $bySeller->get($seller->id, collect());
            /** @var Collection<int, ShippingQuote> $quotes */
            $quotes = $quotesBySeller->get($seller->id, collect());

            $selected = $this->resolveSelection($quotes, $selections[$seller->id] ?? null);

            return new CartGroup(
                seller: $seller,
                items: $sellerItems,
                quotes: $quotes,
                selectedQuote: $selected,
                issues: $this->issuesFor($seller, $sellerItems, $quotes, $zone),
            );
        });

        return new CartSummary($groups->values(), $zone);
    }

    /**
     * @param  Collection<int, ShippingQuote>  $quotes
     */
    private function resolveSelection(Collection $quotes, ?int $rateId): ?ShippingQuote
    {
        if ($quotes->isEmpty()) {
            return null;
        }

        if ($rateId !== null) {
            $match = $quotes->first(fn (ShippingQuote $q) => $q->rate->id === $rateId);

            if ($match !== null) {
                return $match;
            }
        }

        // Default to the cheapest — the same option the product page priced.
        return $quotes->first();
    }

    /**
     * @param  Collection<int, CartItem>  $items
     * @param  Collection<int, ShippingQuote>  $quotes
     * @return array<int, string>
     */
    private function issuesFor($seller, Collection $items, Collection $quotes, ?ShippingZone $zone): array
    {
        $issues = [];

        if (! $seller->isActive()) {
            $issues[] = 'متجر '.$seller->name.' غير متاح حاليًا.';
        }

        foreach ($items as $item) {
            $offer = $item->offer;

            if ($offer === null || ! $offer->isPurchasable()) {
                $issues[] = $item->product?->name.' لم يعد متاحًا لدى '.$seller->name.'.';

                continue;
            }

            if ($offer->stock < $item->quantity) {
                $issues[] = 'الكمية المتاحة من '.$item->product?->name.' لدى '.$seller->name.' هي '.$offer->stock.' فقط.';
            }
        }

        if ($zone !== null && $quotes->isEmpty()) {
            $issues[] = 'متجر '.$seller->name.' لا يوصّل إلى '.$zone->name.' حاليًا.';
        }

        return array_values(array_unique($issues));
    }
}
