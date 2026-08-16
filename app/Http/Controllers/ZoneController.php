<?php

namespace App\Http\Controllers;

use App\Services\Shipping\ZoneContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    /**
     * Changing the delivery zone re-prices every offer on the page, so it is a
     * POST + redirect: the customer lands back where they were, on a clean URL.
     */
    public function update(Request $request, ZoneContext $zones): RedirectResponse
    {
        $validated = $request->validate([
            'zone_id' => ['required', 'integer', 'exists:shipping_zones,id'],
            'redirect' => ['nullable', 'string'],
        ]);

        $zone = $zones->set((int) $validated['zone_id']);

        $target = $validated['redirect'] ?? null;
        $isInternal = $target && str_starts_with($target, config('app.url'));

        return redirect()->to($isInternal ? $target : route('home'))
            ->with('status', $zone ? 'تم تغيير منطقة التوصيل إلى '.$zone->name.'.' : null);
    }
}
