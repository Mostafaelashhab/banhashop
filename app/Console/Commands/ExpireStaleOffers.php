<?php

namespace App\Console\Commands;

use App\Services\Catalog\OfferInventoryService;
use Illuminate\Console\Command;

/**
 * Availability data decays. This keeps the storefront honest by retiring
 * offers no store has confirmed in a long time — they are marked as needing an
 * update, never deleted, so a seller reactivates with one tap.
 */
class ExpireStaleOffers extends Command
{
    protected $signature = 'offers:expire-stale';

    protected $description = 'Retire offers whose inventory has not been confirmed for too long';

    public function handle(OfferInventoryService $inventory): int
    {
        $days = (int) config('banha.inventory.expire_after_days');
        $count = $inventory->expireStale();

        $this->info($count === 0
            ? "No offers have gone {$days} days without an inventory update."
            : "Retired {$count} offer(s) untouched for more than {$days} days.");

        return self::SUCCESS;
    }
}
