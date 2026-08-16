<?php

namespace App\Services\Shipping;

use App\Models\ShippingProvider;
use App\Models\ShippingRate;
use Illuminate\Support\Carbon;

/**
 * One concrete delivery option for one seller into one zone, priced against a
 * specific basket subtotal. Immutable — quotes are recomputed, never mutated.
 */
final class ShippingQuote
{
    public function __construct(
        public readonly ShippingProvider $provider,
        public readonly ShippingRate $rate,
        public readonly int $priceCents,
        public readonly int $etaMinHours,
        public readonly int $etaMaxHours,
        public readonly ?string $sameDayCutoff = null,
    ) {}

    public static function fromRate(ShippingRate $rate, ShippingProvider $provider, int $subtotalCents): self
    {
        return new self(
            provider: $provider,
            rate: $rate,
            priceCents: $rate->priceFor($subtotalCents),
            etaMinHours: $rate->eta_min_hours,
            etaMaxHours: $rate->eta_max_hours,
            sameDayCutoff: $rate->same_day_cutoff,
        );
    }

    public function isFree(): bool
    {
        return $this->priceCents === 0;
    }

    /** Free only because the basket cleared the seller's threshold. */
    public function isFreeByThreshold(): bool
    {
        return $this->isFree() && $this->rate->price_cents > 0;
    }

    /**
     * Latest moment we are willing to promise. Past the same-day cutoff the
     * clock restarts the next morning — promising "today" at 11pm is a lie.
     */
    public function promisedAt(?Carbon $now = null): Carbon
    {
        $now ??= Carbon::now();
        $start = $now->copy();

        if ($this->sameDayCutoff !== null && $now->format('H:i:s') > $this->sameDayCutoff) {
            $start = $now->copy()->addDay()->setTimeFromTimeString('09:00:00');
        }

        return $start->addHours($this->etaMaxHours);
    }

    public function deliveryLabel(?Carbon $now = null): string
    {
        $now ??= Carbon::now();

        // Carbon 3 returns a float here; cast before comparing, otherwise the
        // exact-match arms never fire and everything reads "خلال 1 أيام".
        $days = (int) $now->copy()->startOfDay()->diffInDays($this->promisedAt($now)->startOfDay());

        return match (true) {
            $days <= 0 => 'اليوم',
            $days === 1 => 'غدًا',
            $days === 2 => 'خلال يومين',
            // Arabic number agreement: 3–10 take the plural, 11+ the singular.
            $days <= 10 => 'خلال '.$days.' أيام',
            default => 'خلال '.$days.' يومًا',
        };
    }

    /** Sort key for "fastest first": earlier promise wins. */
    public function speedScore(?Carbon $now = null): int
    {
        return $this->promisedAt($now)->getTimestamp();
    }
}
