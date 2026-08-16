<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\SellerOffer;
use App\Services\Cart\CartManager;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CartController extends Controller
{
    /** The cart itself is a Livewire component; this only sets the page up. */
    public function index(SeoData $seo): View
    {
        $seo->title('سلة المشتريات')->noindex(follow: false);

        return view('pages.cart');
    }

    public function store(Request $request, CartManager $carts): RedirectResponse
    {
        $validated = $request->validate([
            'offer_id' => ['required', 'integer', 'exists:seller_offers,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $offer = SellerOffer::query()->with('product:id,name')->findOrFail($validated['offer_id']);

        try {
            $carts->add($offer, (int) ($validated['quantity'] ?? 1));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('cart.index')
            ->with('status', 'تمت إضافة "'.$offer->product?->name.'" إلى السلة.');
    }

    public function update(Request $request, CartItem $item, CartManager $carts): RedirectResponse
    {
        $this->authorizeItem($item, $carts);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        $carts->updateQuantity($item, (int) $validated['quantity']);

        return redirect()->route('cart.index');
    }

    public function destroy(CartItem $item, CartManager $carts): RedirectResponse
    {
        $this->authorizeItem($item, $carts);
        $carts->remove($item);

        return redirect()->route('cart.index')->with('status', 'تم حذف المنتج من السلة.');
    }

    /** A cart line may only be touched from the cart that owns it. */
    private function authorizeItem(CartItem $item, CartManager $carts): void
    {
        abort_unless($carts->current()?->id === $item->cart_id, 403);
    }
}
