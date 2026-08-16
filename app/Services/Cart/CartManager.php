<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\SellerOffer;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Carts survive login: a guest builds one against a session token, and it is
 * merged into the account cart the moment they sign in.
 */
class CartManager
{
    public const SESSION_KEY = 'banha.cart_token';

    private ?Cart $cart = null;

    /**
     * Which identity the memoised cart belongs to. Without this, a container
     * that outlives a single request — a test process, or Octane with scoped
     * instances misconfigured — could hand one customer another's cart.
     */
    private int|string|null $memoisedFor = null;

    private bool $memoised = false;

    public function current(bool $create = false): ?Cart
    {
        $identity = auth()->id() ?? 'guest:'.Session::get(self::SESSION_KEY, '');

        if ($this->memoised && $this->memoisedFor === $identity && $this->cart !== null) {
            return $this->cart;
        }

        $this->memoisedFor = $identity;
        $this->memoised = true;
        $this->cart = null;

        $user = auth()->user();

        if ($user !== null) {
            $cart = Cart::query()->where('user_id', $user->id)->first();

            if ($cart === null && $create) {
                $cart = Cart::create(['user_id' => $user->id, 'last_activity_at' => Carbon::now()]);
            }

            return $this->cart = $cart;
        }

        $token = Session::get(self::SESSION_KEY);

        if ($token === null) {
            if (! $create) {
                return null;
            }

            $token = (string) Str::uuid();
            Session::put(self::SESSION_KEY, $token);
        }

        $cart = Cart::query()->where('session_token', $token)->first();

        if ($cart === null && $create) {
            $cart = Cart::create(['session_token' => $token, 'last_activity_at' => Carbon::now()]);
        }

        return $this->cart = $cart;
    }

    /** @return Collection<int, CartItem> */
    public function items()
    {
        $cart = $this->current();

        if ($cart === null) {
            return collect();
        }

        return $cart->items()
            ->with([
                'offer:id,product_id,seller_id,price_cents,stock,status,condition,inventory_updated_at',
                'product:id,name,slug,variant_label,image_path',
                'seller:id,name,slug,status',
            ])
            ->get();
    }

    public function itemCount(): int
    {
        $cart = $this->current();

        return $cart === null ? 0 : (int) $cart->items()->sum('quantity');
    }

    public function add(SellerOffer $offer, int $quantity = 1): CartItem
    {
        if (! $offer->isPurchasable()) {
            throw new RuntimeException('هذا العرض غير متاح للشراء حاليًا.');
        }

        $cart = $this->current(create: true);

        return DB::transaction(function () use ($cart, $offer, $quantity) {
            $item = $cart->items()->where('seller_offer_id', $offer->id)->first();
            $requested = ($item?->quantity ?? 0) + max(1, $quantity);

            // Never let the cart promise more units than the seller has.
            $capped = min($requested, $offer->stock);

            if ($item === null) {
                $item = $cart->items()->create([
                    'seller_offer_id' => $offer->id,
                    'product_id' => $offer->product_id,
                    'seller_id' => $offer->seller_id,
                    'quantity' => $capped,
                    'unit_price_cents' => $offer->price_cents,
                ]);
            } else {
                $item->update(['quantity' => $capped, 'unit_price_cents' => $offer->price_cents]);
            }

            $cart->update(['last_activity_at' => Carbon::now()]);

            return $item;
        });
    }

    public function updateQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity < 1) {
            $this->remove($item);

            return;
        }

        $stock = $item->offer?->stock ?? 0;
        $item->update(['quantity' => max(1, min($quantity, $stock))]);
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }

    /**
     * Called right after login. Guest lines win on quantity so nothing the
     * customer just added silently disappears.
     */
    public function mergeGuestCartInto(User $user): void
    {
        $token = Session::get(self::SESSION_KEY);

        if ($token === null) {
            return;
        }

        $guestCart = Cart::query()->where('session_token', $token)->with('items')->first();
        Session::forget(self::SESSION_KEY);

        if ($guestCart === null) {
            return;
        }

        $userCart = Cart::query()->where('user_id', $user->id)->first();

        if ($userCart === null) {
            $guestCart->update(['user_id' => $user->id, 'session_token' => null]);
            $this->cart = $guestCart;

            return;
        }

        DB::transaction(function () use ($guestCart, $userCart) {
            foreach ($guestCart->items as $item) {
                $existing = $userCart->items()->where('seller_offer_id', $item->seller_offer_id)->first();

                if ($existing === null) {
                    $item->update(['cart_id' => $userCart->id]);

                    continue;
                }

                $stock = $item->offer?->stock ?? $existing->quantity;
                $existing->update([
                    'quantity' => min($existing->quantity + $item->quantity, max(1, $stock)),
                ]);
                $item->delete();
            }

            $guestCart->delete();
        });

        $this->cart = $userCart;
    }
}
