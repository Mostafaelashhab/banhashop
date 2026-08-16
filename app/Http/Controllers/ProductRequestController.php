<?php

namespace App\Http\Controllers;

use App\Models\ProductRequest;
use App\Services\Shipping\ZoneContext;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "No results" is a demand signal, not a dead end. Every request recorded here
 * becomes a line on the admin's seller-acquisition list.
 */
class ProductRequestController extends Controller
{
    public function create(Request $request, ZoneContext $zones, SeoData $seo): View
    {
        $seo->title('اطلب منتجًا غير متوفر')
            ->description('لم تجد ما تبحث عنه في متاجر '.config('banha.city').'؟ اطلبه وسنحاول توفيره من المتاجر المحلية.')
            ->canonical(route('product-requests.create'));

        return view('pages.product-request', [
            'query' => $request->query('q'),
            'zones' => $zones->all(),
            'currentZone' => $zones->current(),
        ]);
    }

    public function store(Request $request, ZoneContext $zones): RedirectResponse
    {
        $validated = $request->validate([
            'query_text' => ['required', 'string', 'min:2', 'max:180'],
            'note' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'regex:/^01[0-9]{9}$/'],
            'shipping_zone_id' => ['nullable', 'integer', 'exists:shipping_zones,id'],
        ], [
            'contact_phone.regex' => 'رقم الموبايل يجب أن يبدأ بـ 01 ويتكون من 11 رقمًا.',
        ]);

        ProductRequest::create([
            'user_id' => $request->user()?->id,
            'shipping_zone_id' => $validated['shipping_zone_id'] ?? $zones->current()?->id,
            'query_text' => $validated['query_text'],
            'note' => $validated['note'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'status' => ProductRequest::STATUS_OPEN,
        ]);

        return redirect()->route('product-requests.create')
            ->with('status', 'وصلنا طلبك. لو وفّره أحد متاجر '.config('banha.city').' هنبلغك.');
    }
}
