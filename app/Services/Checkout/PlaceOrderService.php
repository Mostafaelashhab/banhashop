<?php

namespace App\Services\Checkout;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SellerOrderStatus;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Seller;
use App\Models\SellerOffer;
use App\Models\SellerOrder;
use App\Models\User;
use App\Services\Cart\CartGroup;
use App\Services\Cart\CartSummary;
use App\Services\Catalog\OfferInventoryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns a priced cart into an order.
 *
 * Everything happens inside one transaction with the offer rows locked, so two
 * customers cannot both buy the last unit. Prices, product names and delivery
 * terms are snapshotted onto the order — later catalog edits never rewrite what
 * a customer agreed to pay.
 */
class PlaceOrderService
{
    public function __construct(private readonly OfferInventoryService $inventory) {}

    public function place(
        Cart $cart,
        CartSummary $summary,
        Address $address,
        ?User $user,
        PaymentMethod $method = PaymentMethod::Cod,
    ): Order {
        if (! $summary->canCheckout()) {
            throw new CheckoutException('لا يمكن إتمام الطلب: '.implode(' ', $summary->issues()));
        }

        return DB::transaction(function () use ($cart, $summary, $address, $user, $method) {
            $this->assertStockStillAvailable($summary);

            $order = Order::create([
                'number' => $this->nextOrderNumber(),
                'user_id' => $user?->id,
                'customer_name' => $address->recipient_name,
                'customer_phone' => $address->phone,
                'customer_email' => $user?->email,
                'shipping_zone_id' => $address->shipping_zone_id,
                'shipping_zone_name' => $address->zone?->name ?? '',
                'shipping_street' => $address->street,
                'shipping_building' => $address->building,
                'shipping_floor' => $address->floor,
                'shipping_apartment' => $address->apartment,
                'shipping_landmark' => $address->landmark,
                'shipping_notes' => $address->notes,
                'items_total_cents' => $summary->itemsTotalCents(),
                'shipping_total_cents' => $summary->shippingTotalCents(),
                'grand_total_cents' => $summary->grandTotalCents(),
                'status' => OrderStatus::Placed,
                'payment_method' => $method,
                'payment_status' => PaymentStatus::Pending,
                'placed_at' => Carbon::now(),
            ]);

            foreach ($summary->groups as $group) {
                $this->createSellerOrder($order, $group);
            }

            $order->payments()->create([
                'method' => $method,
                'status' => PaymentStatus::Pending,
                'amount_cents' => $order->grand_total_cents,
            ]);

            OrderEvent::create([
                'order_id' => $order->id,
                'user_id' => $user?->id,
                'status' => OrderStatus::Placed->value,
                'note' => 'تم استلام الطلب وجاري إرساله للمتاجر.',
            ]);

            $cart->items()->delete();
            $cart->delete();

            return $order->load('sellerOrders.items', 'sellerOrders.seller');
        });
    }

    private function createSellerOrder(Order $order, CartGroup $group): SellerOrder
    {
        $quote = $group->selectedQuote;

        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_id' => $group->seller->id,
            'reference' => $order->number.'-'.Str::upper(Str::random(4)),
            'items_total_cents' => $group->subtotalCents(),
            'shipping_cents' => $group->shippingCents() ?? 0,
            'total_cents' => $group->totalCents() ?? $group->subtotalCents(),
            'shipping_provider_id' => $quote?->provider->id,
            'shipping_rate_id' => $quote?->rate->id,
            'shipping_provider_name' => $quote?->provider->name,
            'eta_min_hours' => $quote?->etaMinHours,
            'eta_max_hours' => $quote?->etaMaxHours,
            'promised_at' => $quote?->promisedAt(),
            'status' => SellerOrderStatus::Pending,
        ]);

        foreach ($group->items as $item) {
            $this->createOrderItem($order, $sellerOrder, $item);
        }

        $sellerOrder->shipment()->create([
            'shipping_provider_id' => $quote?->provider->id,
            'cost_cents' => $group->shippingCents() ?? 0,
        ]);

        // Incremented in the database rather than on the in-memory model: the
        // counter must not depend on a value this request happened to load.
        Seller::whereKey($group->seller->id)->increment('orders_count');

        return $sellerOrder;
    }

    private function createOrderItem(Order $order, SellerOrder $sellerOrder, CartItem $item): void
    {
        $offer = $item->offer;
        $product = $item->product;
        $unitPrice = $offer?->price_cents ?? $item->unit_price_cents;

        $order->items()->create([
            'seller_order_id' => $sellerOrder->id,
            'product_id' => $item->product_id,
            'seller_offer_id' => $item->seller_offer_id,
            'product_name' => $product?->name ?? '',
            'product_slug' => $product?->slug ?? '',
            'variant_label' => $product?->variant_label,
            'image_path' => $product?->image_path,
            'condition' => $offer?->condition->value ?? 'new',
            'unit_price_cents' => $unitPrice,
            'quantity' => $item->quantity,
            'line_total_cents' => $unitPrice * $item->quantity,
        ]);

        if ($offer !== null) {
            $this->inventory->consume($offer, $item->quantity);
        }
    }

    /**
     * Re-checks stock with the offer rows locked. The cart summary was built
     * before this transaction opened, so it is advisory, not authoritative.
     */
    private function assertStockStillAvailable(CartSummary $summary): void
    {
        $offerIds = $summary->groups
            ->flatMap(fn (CartGroup $g) => $g->items->pluck('seller_offer_id'))
            ->filter()
            ->unique()
            ->all();

        $locked = SellerOffer::query()->whereIn('id', $offerIds)->lockForUpdate()->get()->keyBy('id');

        foreach ($summary->groups as $group) {
            foreach ($group->items as $item) {
                $offer = $locked->get($item->seller_offer_id);

                if ($offer === null || ! $offer->isPurchasable() || $offer->stock < $item->quantity) {
                    throw new CheckoutException(
                        'نفدت الكمية المطلوبة من "'.($item->product?->name ?? 'أحد المنتجات').'" أثناء إتمام الطلب.'
                    );
                }
            }
        }
    }

    /** Human-readable and safe under concurrency: BN-YYMM-###### */
    private function nextOrderNumber(): string
    {
        do {
            $number = 'BN-'.Carbon::now()->format('ym').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }
}
