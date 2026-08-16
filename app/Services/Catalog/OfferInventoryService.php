<?php

namespace App\Services\Catalog;

use App\Enums\OfferStatus;
use App\Models\OfferInventoryLog;
use App\Models\SellerOffer;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The only place price and stock are allowed to change.
 *
 * Every write stamps `inventory_updated_at` and appends a log row, which is
 * what lets the product page say "stock updated 14 minutes ago" from real data
 * instead of a guess.
 */
class OfferInventoryService
{
    public function create(array $attributes, ?User $actor = null): SellerOffer
    {
        return DB::transaction(function () use ($attributes, $actor) {
            $now = Carbon::now();

            $offer = SellerOffer::create($attributes + [
                'inventory_updated_at' => $now,
                'price_updated_at' => $now,
            ]);

            $this->log($offer, OfferInventoryLog::REASON_CREATED, $actor, null, $offer->price_cents, null, $offer->stock);

            return $offer;
        });
    }

    /**
     * @param  array{price_cents?: int, stock?: int, status?: OfferStatus|string, sku?: ?string, condition?: string, note?: ?string}  $changes
     */
    public function update(
        SellerOffer $offer,
        array $changes,
        ?User $actor = null,
        string $reason = OfferInventoryLog::REASON_SELLER_UPDATE
    ): SellerOffer {
        return DB::transaction(function () use ($offer, $changes, $actor, $reason) {
            $priceBefore = $offer->price_cents;
            $stockBefore = $offer->stock;
            $now = Carbon::now();

            $offer->fill($changes);

            if ($offer->isDirty('price_cents')) {
                $offer->price_updated_at = $now;
            }

            // A seller confirming "still 5 in stock" is itself fresh
            // information, so the timestamp moves even when the number does not.
            $offer->inventory_updated_at = $now;

            $offer->status = $this->resolveStatus($offer);
            $offer->save();

            $this->log(
                $offer,
                $reason,
                $actor,
                $priceBefore,
                $offer->price_cents,
                $stockBefore,
                $offer->stock,
            );

            return $offer;
        });
    }

    /** Called when an order consumes stock. */
    public function consume(SellerOffer $offer, int $quantity, ?User $actor = null): void
    {
        DB::transaction(function () use ($offer, $quantity, $actor) {
            $stockBefore = $offer->stock;
            $offer->stock = max(0, $offer->stock - $quantity);
            $offer->inventory_updated_at = Carbon::now();
            $offer->status = $this->resolveStatus($offer);
            $offer->save();

            $this->log(
                $offer,
                OfferInventoryLog::REASON_ORDER,
                $actor,
                $offer->price_cents,
                $offer->price_cents,
                $stockBefore,
                $offer->stock,
            );
        });
    }

    /** Restores stock when a seller rejects or an order is cancelled. */
    public function restore(SellerOffer $offer, int $quantity, ?User $actor = null): void
    {
        DB::transaction(function () use ($offer, $quantity, $actor) {
            $stockBefore = $offer->stock;
            $offer->stock += $quantity;

            if ($offer->status === OfferStatus::OutOfStock && $offer->stock > 0) {
                $offer->status = OfferStatus::Active;
            }

            $offer->save();

            $this->log(
                $offer,
                OfferInventoryLog::REASON_ORDER,
                $actor,
                $offer->price_cents,
                $offer->price_cents,
                $stockBefore,
                $offer->stock,
            );
        });
    }

    /**
     * Offers nobody has touched in a long time stop being presented as live
     * availability. They are not deleted — the seller reactivates with one tap.
     */
    public function expireStale(): int
    {
        $cutoff = Carbon::now()->subDays((int) config('banha.inventory.expire_after_days', 30));

        $offers = SellerOffer::query()
            ->where('status', OfferStatus::Active)
            ->where(function ($query) use ($cutoff) {
                $query->whereNull('inventory_updated_at')
                    ->orWhere('inventory_updated_at', '<', $cutoff);
            })
            ->get();

        foreach ($offers as $offer) {
            $offer->status = OfferStatus::Expired;
            $offer->save();

            $this->log(
                $offer,
                OfferInventoryLog::REASON_EXPIRED,
                null,
                $offer->price_cents,
                $offer->price_cents,
                $offer->stock,
                $offer->stock,
            );
        }

        return $offers->count();
    }

    /**
     * Stock and status must agree: zero stock can never read as "متاح", and a
     * restocked offer leaves the out-of-stock state on its own.
     */
    private function resolveStatus(SellerOffer $offer): OfferStatus
    {
        $status = $offer->status;

        if (in_array($status, [OfferStatus::Paused, OfferStatus::Rejected], true)) {
            return $status;
        }

        return $offer->stock > 0 ? OfferStatus::Active : OfferStatus::OutOfStock;
    }

    private function log(
        SellerOffer $offer,
        string $reason,
        ?User $actor,
        ?int $priceBefore,
        ?int $priceAfter,
        ?int $stockBefore,
        ?int $stockAfter,
    ): void {
        OfferInventoryLog::create([
            'seller_offer_id' => $offer->id,
            'user_id' => $actor?->id,
            'reason' => $reason,
            'price_cents_before' => $priceBefore,
            'price_cents_after' => $priceAfter,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
        ]);
    }
}
