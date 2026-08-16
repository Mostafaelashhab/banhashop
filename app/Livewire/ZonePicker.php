<?php

namespace App\Livewire;

use App\Services\Shipping\ZoneContext;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The destination every delivery price is quoted against.
 *
 * Changing it writes the choice to the session (so a full page load anywhere
 * else agrees) and announces `zone-changed`, which re-prices the offer board
 * in place instead of reloading the page.
 */
class ZonePicker extends Component
{
    public ?int $zoneId = null;

    public function mount(): void
    {
        $this->zoneId = app(ZoneContext::class)->current()?->id;
    }

    public function updatedZoneId(mixed $value): void
    {
        $zone = app(ZoneContext::class)->set((int) $value);

        if ($zone === null) {
            return;
        }

        $this->zoneId = $zone->id;
        $this->dispatch('zone-changed', zoneId: $zone->id);
    }

    public function render(): View
    {
        return view('livewire.zone-picker', [
            'zones' => app(ZoneContext::class)->all(),
        ]);
    }
}
