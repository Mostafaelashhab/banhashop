<?php

namespace App\Services\Shipping;

use App\Models\ShippingZone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * "Where am I?" — answered once per request and reused everywhere.
 *
 * Delivery price is meaningless without a destination, so every listing and
 * product page is rendered against a zone. It is remembered in the session and
 * defaults to the first configured zone rather than asking up front.
 */
class ZoneContext
{
    public const SESSION_KEY = 'banha.zone_id';

    private ?ShippingZone $resolved = null;

    /** @var Collection<int, ShippingZone>|null */
    private ?Collection $zones = null;

    /**
     * Zones are a handful of rows. The service is request-scoped, so this is
     * one query per request — cheaper and fresher than a cache round trip.
     *
     * @return Collection<int, ShippingZone>
     */
    public function all(): Collection
    {
        return $this->zones ??= ShippingZone::query()->active()->get();
    }

    public function current(): ?ShippingZone
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $zones = $this->all();

        if ($zones->isEmpty()) {
            return null;
        }

        $selectedId = Session::get(self::SESSION_KEY);

        return $this->resolved = $zones->firstWhere('id', $selectedId) ?? $zones->first();
    }

    public function set(int $zoneId): ?ShippingZone
    {
        $zone = $this->all()->firstWhere('id', $zoneId);

        if ($zone === null) {
            return null;
        }

        Session::put(self::SESSION_KEY, $zone->id);

        return $this->resolved = $zone;
    }

    /** True once the customer has actually chosen, not just defaulted. */
    public function isExplicit(): bool
    {
        return Session::has(self::SESSION_KEY);
    }
}
