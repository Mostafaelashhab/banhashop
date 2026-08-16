<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Services\Shipping\ZoneContext;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request, ZoneContext $zones, SeoData $seo): View
    {
        $seo->title('عناويني')->noindex(follow: false);

        return view('pages.account.addresses', [
            'addresses' => $request->user()->addresses()->with('zone:id,name')->orderByDesc('is_default')->get(),
            'zones' => $zones->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        DB::transaction(function () use ($request, $validated) {
            $isFirst = $request->user()->addresses()->count() === 0;

            if (! empty($validated['is_default']) || $isFirst) {
                $request->user()->addresses()->update(['is_default' => false]);
                $validated['is_default'] = true;
            }

            $request->user()->addresses()->create($validated);
        });

        return redirect()->to($request->input('redirect_to', route('account.addresses')))
            ->with('status', 'تمت إضافة العنوان.');
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);

        $validated = $this->validated($request);

        DB::transaction(function () use ($request, $address, $validated) {
            if (! empty($validated['is_default'])) {
                $request->user()->addresses()->update(['is_default' => false]);
            }

            $address->update($validated);
        });

        return redirect()->route('account.addresses')->with('status', 'تم تحديث العنوان.');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);

        $address->delete();

        return redirect()->route('account.addresses')->with('status', 'تم حذف العنوان.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['nullable', 'string', 'max:40'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^01[0-9]{9}$/'],
            'shipping_zone_id' => ['required', 'integer', 'exists:shipping_zones,id'],
            'street' => ['required', 'string', 'max:180'],
            'building' => ['nullable', 'string', 'max:60'],
            'floor' => ['nullable', 'string', 'max:20'],
            'apartment' => ['nullable', 'string', 'max:20'],
            'landmark' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ], [
            'phone.regex' => 'رقم الموبايل يجب أن يبدأ بـ 01 ويتكون من 11 رقمًا.',
        ]);
    }
}
