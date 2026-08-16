<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShippingProviderType;
use App\Http\Controllers\Controller;
use App\Models\ShippingProvider;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Support\Money;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminShippingController extends Controller
{
    public function index(SeoData $seo): View
    {
        $seo->title('الشحن والمناطق')->noindex(follow: false);

        return view('pages.admin.shipping', [
            'zones' => ShippingZone::orderBy('position')->get(),
            'providers' => ShippingProvider::orderBy('position')->get(),
            // Platform-wide rates only; seller-scoped rates belong to the store.
            'rates' => ShippingRate::whereNull('seller_id')
                ->with(['provider:id,name', 'zone:id,name'])
                ->get()
                ->groupBy('shipping_provider_id'),
            'types' => ShippingProviderType::cases(),
        ]);
    }

    public function storeZone(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ShippingZone::create($validated + [
            'slug' => Str::slug($validated['name']) ?: 'zone-'.uniqid(),
            'city' => config('banha.city'),
        ]);

        return back()->with('status', 'تمت إضافة المنطقة.');
    }

    public function storeProvider(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(ShippingProviderType::class)],
            'description' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ShippingProvider::create($validated + [
            'slug' => Str::slug($validated['name']) ?: 'provider-'.uniqid(),
        ]);

        return back()->with('status', 'تمت إضافة شركة التوصيل.');
    }

    public function storeRate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shipping_provider_id' => ['required', 'integer', 'exists:shipping_providers,id'],
            'shipping_zone_id' => ['required', 'integer', 'exists:shipping_zones,id'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000'],
            'free_over' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'eta_min_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'eta_max_hours' => ['required', 'integer', 'min:1', 'max:720', 'gte:eta_min_hours'],
            'same_day_cutoff' => ['nullable', 'date_format:H:i'],
        ], [
            'eta_max_hours.gte' => 'أقصى مدة توصيل لازم تكون أكبر من أو تساوي أقلها.',
        ]);

        ShippingRate::updateOrCreate(
            [
                'shipping_provider_id' => $validated['shipping_provider_id'],
                'shipping_zone_id' => $validated['shipping_zone_id'],
                'seller_id' => null,
            ],
            [
                'price_cents' => Money::toCents($validated['price']),
                'free_over_cents' => filled($validated['free_over'] ?? null)
                    ? Money::toCents($validated['free_over'])
                    : null,
                'eta_min_hours' => $validated['eta_min_hours'],
                'eta_max_hours' => $validated['eta_max_hours'],
                'same_day_cutoff' => $validated['same_day_cutoff'] ?? null,
                'is_active' => true,
            ]
        );

        return back()->with('status', 'تم حفظ سعر التوصيل.');
    }

    public function destroyRate(ShippingRate $rate): RedirectResponse
    {
        $rate->delete();

        return back()->with('status', 'تم حذف السعر.');
    }
}
