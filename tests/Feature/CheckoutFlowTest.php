<?php

namespace Tests\Feature;

use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SellerOrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Services\Shipping\ZoneContext;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuildsMarketplace;
use Tests\TestCase;

/**
 * The vertical slice the whole product is built around:
 * offer -> cart -> checkout -> order -> seller fulfilment.
 */
class CheckoutFlowTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_a_customer_can_go_from_offer_to_a_cash_on_delivery_order(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create();
        $seller = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $offer = $this->makeOffer($product, $seller, 500, stock: 4);

        [$user, $address] = $this->makeCustomerWithAddress($zone);

        $this->actingAs($user)
            ->withSession([ZoneContext::SESSION_KEY => $zone->id])
            ->post(route('cart.store'), ['offer_id' => $offer->id, 'quantity' => 2])
            ->assertRedirect(route('cart.index'));

        $this->actingAs($user)->get(route('cart.index'))->assertOk();

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'cod',
        ]);

        $order = Order::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('orders.show', $order->number));

        // Totals: 2 x 500 items + 30 delivery.
        $this->assertSame(Money::toCents(1000), $order->items_total_cents);
        $this->assertSame(Money::toCents(30), $order->shipping_total_cents);
        $this->assertSame(Money::toCents(1030), $order->grand_total_cents);

        $this->assertSame(OrderStatus::Placed, $order->status);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);

        // Fulfilment is split per seller from day one.
        $sellerOrder = $order->sellerOrders()->firstOrFail();
        $this->assertSame($seller->id, $sellerOrder->seller_id);
        $this->assertSame(SellerOrderStatus::Pending, $sellerOrder->status);
        $this->assertSame(1, $order->items()->count());

        // Stock was consumed, and the cart is gone.
        $this->assertSame(2, $offer->fresh()->stock);
        $this->assertDatabaseCount('carts', 0);

        // The address is snapshotted, not referenced.
        $this->assertSame('شارع فريد ندا', $order->shipping_street);
        $this->assertSame($zone->name, $order->shipping_zone_name);
    }

    public function test_an_order_splits_into_one_fulfilment_per_seller(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();

        $sellerA = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $sellerB = $this->makeSellerServing($zone, $courier, 45, sellerScopedRate: true);

        $offerA = $this->makeOffer($productA, $sellerA, 200);
        $offerB = $this->makeOffer($productB, $sellerB, 300);

        [$user, $address] = $this->makeCustomerWithAddress($zone);

        $this->actingAs($user)->post(route('cart.store'), ['offer_id' => $offerA->id]);
        $this->actingAs($user)->post(route('cart.store'), ['offer_id' => $offerB->id]);

        $this->actingAs($user)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'cod',
        ]);

        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertSame(2, $order->sellerOrders()->count());
        // Each store's delivery is charged separately — 30 + 45.
        $this->assertSame(Money::toCents(75), $order->shipping_total_cents);
        $this->assertSame(Money::toCents(575), $order->grand_total_cents);
    }

    public function test_checkout_is_refused_when_the_last_unit_disappeared_first(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create();
        $seller = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $offer = $this->makeOffer($product, $seller, 500, stock: 2);

        [$user, $address] = $this->makeCustomerWithAddress($zone);

        $this->actingAs($user)->post(route('cart.store'), ['offer_id' => $offer->id, 'quantity' => 2]);

        // Someone else bought the stock between add-to-cart and confirm.
        $offer->update(['stock' => 0, 'status' => OfferStatus::OutOfStock]);

        $this->actingAs($user)
            ->post(route('checkout.store'), ['address_id' => $address->id, 'payment_method' => 'cod'])
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_guest_cart_survives_logging_in(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create();
        $seller = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $offer = $this->makeOffer($product, $seller, 500);

        [$user] = $this->makeCustomerWithAddress($zone);

        $this->post(route('cart.store'), ['offer_id' => $offer->id, 'quantity' => 3]);

        $this->post(route('login'), [
            'identifier' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'seller_offer_id' => $offer->id,
            'quantity' => 3,
        ]);
        $this->assertSame($user->id, Cart::firstOrFail()->user_id);
    }

    public function test_the_cart_never_promises_more_units_than_the_seller_has(): void
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create();
        $seller = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $offer = $this->makeOffer($product, $seller, 500, stock: 3);

        [$user] = $this->makeCustomerWithAddress($zone);

        $this->actingAs($user)->post(route('cart.store'), ['offer_id' => $offer->id, 'quantity' => 10]);

        $this->assertDatabaseHas('cart_items', ['seller_offer_id' => $offer->id, 'quantity' => 3]);
    }
}
