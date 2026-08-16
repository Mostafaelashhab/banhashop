<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\Address;
use App\Services\Cart\CartManager;
use App\Services\Cart\CartSummaryBuilder;
use App\Services\Checkout\CheckoutException;
use App\Services\Checkout\PlaceOrderService;
use App\Services\Shipping\ZoneContext;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(
        Request $request,
        CartManager $carts,
        CartSummaryBuilder $builder,
        ZoneContext $zones,
        SeoData $seo,
    ): View|RedirectResponse {
        $cart = $carts->current();
        $items = $carts->items();

        if ($cart === null || $items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $addresses = $request->user()->addresses()->with('zone:id,name')->orderByDesc('is_default')->get();
        $selectedAddress = $this->selectedAddress($request, $addresses);

        // Delivery is quoted against the address, not the header zone: the
        // number on this page must be the number that gets charged.
        $zone = $selectedAddress?->zone ?? $zones->current();
        $summary = $builder->build($cart, $items, $zone, $this->shippingSelections($request));

        $seo->title('إتمام الطلب')->noindex(follow: false);

        return view('pages.checkout', [
            'summary' => $summary,
            'addresses' => $addresses,
            'selectedAddress' => $selectedAddress,
            'zones' => $zones->all(),
            'zone' => $zone,
            'paymentMethods' => PaymentMethod::available(),
        ]);
    }

    public function store(
        Request $request,
        CartManager $carts,
        CartSummaryBuilder $builder,
        PlaceOrderService $placer,
    ): RedirectResponse {
        $validated = $request->validate([
            'address_id' => ['required', 'integer'],
            'payment_method' => ['required', 'string', 'in:cod'],
            'shipping' => ['nullable', 'array'],
            'shipping.*' => ['nullable', 'integer'],
        ]);

        $cart = $carts->current();
        $items = $carts->items();

        if ($cart === null || $items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        /** @var Address $address */
        $address = $request->user()->addresses()->with('zone:id,name')->findOrFail($validated['address_id']);

        $summary = $builder->build(
            $cart,
            $items,
            $address->zone,
            collect($validated['shipping'] ?? [])->map(fn ($v) => (int) $v)->all()
        );

        try {
            $order = $placer->place(
                cart: $cart,
                summary: $summary,
                address: $address,
                user: $request->user(),
                method: PaymentMethod::from($validated['payment_method']),
            );
        } catch (CheckoutException $e) {
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }

        return redirect()->route('orders.show', $order->number)
            ->with('status', 'تم استلام طلبك بنجاح. رقم الطلب '.$order->number.'.');
    }

    /** @param  Collection<int, Address>  $addresses */
    private function selectedAddress(Request $request, $addresses): ?Address
    {
        $requested = $request->integer('address');

        return $addresses->firstWhere('id', $requested)
            ?? $addresses->firstWhere('is_default', true)
            ?? $addresses->first();
    }

    /** @return array<int, int> seller id => shipping rate id */
    private function shippingSelections(Request $request): array
    {
        return collect($request->input('shipping', []))
            ->filter()
            ->mapWithKeys(fn ($rateId, $sellerId) => [(int) $sellerId => (int) $rateId])
            ->all();
    }
}
