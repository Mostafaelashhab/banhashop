<?php

namespace App\Http\Controllers\Seller;

use App\Models\ShippingZone;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerProfileController extends SellerController
{
    public function edit(Request $request, SeoData $seo): View
    {
        $seller = $this->seller($request);
        $seo->title('بيانات المتجر')->noindex(follow: false);

        $seller->load('primaryLocation');

        return view('pages.seller.profile', [
            'seller' => $seller,
            'zones' => ShippingZone::query()->active()->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $seller = $this->seller($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'regex:/^01[0-9]{9}$/'],
            'whatsapp' => ['nullable', 'string', 'regex:/^01[0-9]{9}$/'],
            'address_line' => ['required', 'string', 'max:220'],
            'landmark' => ['nullable', 'string', 'max:160'],
            'shipping_zone_id' => ['required', 'integer', 'exists:shipping_zones,id'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ], [
            'phone.regex' => 'رقم الموبايل يجب أن يبدأ بـ 01 ويتكون من 11 رقمًا.',
            'whatsapp.regex' => 'رقم الواتساب يجب أن يبدأ بـ 01 ويتكون من 11 رقمًا.',
        ]);

        $seller->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ]);

        $seller->locations()->updateOrCreate(
            ['is_primary' => true],
            [
                'address_line' => $validated['address_line'],
                'landmark' => $validated['landmark'] ?? null,
                'shipping_zone_id' => $validated['shipping_zone_id'],
                'is_primary' => true,
            ]
        );

        return redirect()->route('seller.profile.edit')->with('status', 'تم حفظ بيانات المتجر.');
    }
}
