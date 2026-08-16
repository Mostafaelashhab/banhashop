<?php

namespace Tests\Unit;

use App\Models\ShippingProvider;
use App\Models\ShippingRate;
use App\Services\Shipping\ShippingQuote;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * A delivery promise is the second half of the price. It has to read like a
 * human wrote it, and it must never promise "today" when it cannot deliver.
 */
class DeliveryPromiseTest extends TestCase
{
    public function test_the_label_reads_naturally_in_arabic(): void
    {
        $now = Carbon::parse('2026-03-01 09:00:00');

        $this->assertSame('اليوم', $this->quote(6)->deliveryLabel($now));
        $this->assertSame('غدًا', $this->quote(24)->deliveryLabel($now));
        $this->assertSame('خلال يومين', $this->quote(48)->deliveryLabel($now));
        $this->assertSame('خلال 4 أيام', $this->quote(96)->deliveryLabel($now));
    }

    public function test_past_the_cutoff_the_clock_restarts_the_next_morning(): void
    {
        $beforeCutoff = Carbon::parse('2026-03-01 10:00:00');
        $afterCutoff = Carbon::parse('2026-03-01 22:00:00');
        $quote = $this->quote(6, cutoff: '16:00:00');

        $this->assertSame('اليوم', $quote->deliveryLabel($beforeCutoff));
        $this->assertSame(
            'غدًا',
            $quote->deliveryLabel($afterCutoff),
            'Promising same-day delivery at 10pm would be a lie.'
        );
    }

    public function test_a_basket_over_the_threshold_ships_free(): void
    {
        $rate = new ShippingRate(['price_cents' => 5000, 'free_over_cents' => 100000, 'eta_min_hours' => 2, 'eta_max_hours' => 6]);
        $provider = new ShippingProvider(['name' => 'شركة']);

        $this->assertSame(5000, ShippingQuote::fromRate($rate, $provider, 90000)->priceCents);

        $free = ShippingQuote::fromRate($rate, $provider, 120000);
        $this->assertSame(0, $free->priceCents);
        $this->assertTrue($free->isFreeByThreshold());
    }

    private function quote(int $etaMaxHours, ?string $cutoff = null): ShippingQuote
    {
        $rate = new ShippingRate([
            'price_cents' => 3000,
            'eta_min_hours' => max(1, $etaMaxHours - 2),
            'eta_max_hours' => $etaMaxHours,
            'same_day_cutoff' => $cutoff,
        ]);

        return ShippingQuote::fromRate($rate, new ShippingProvider(['name' => 'شركة']), 0);
    }
}
