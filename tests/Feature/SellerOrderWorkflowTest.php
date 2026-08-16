<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SellerOrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerOffer;
use App\Models\SellerOrder;
use App\Services\Orders\SellerOrderWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuildsMarketplace;
use Tests\TestCase;

class SellerOrderWorkflowTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_the_customer_order_status_is_derived_from_its_seller_orders(): void
    {
        [$order, $sellerOrder, $seller] = $this->placeOrder();
        $workflow = app(SellerOrderWorkflow::class);
        $actor = $seller->user;

        $this->assertSame(OrderStatus::Placed, $order->status);

        $workflow->transition($sellerOrder, SellerOrderStatus::Accepted, $actor);
        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);

        $workflow->transition($sellerOrder->fresh(), SellerOrderStatus::Preparing, $actor);
        $workflow->transition($sellerOrder->fresh(), SellerOrderStatus::Shipped, $actor);
        $this->assertSame(OrderStatus::InDelivery, $order->fresh()->status);

        $workflow->transition($sellerOrder->fresh(), SellerOrderStatus::Delivered, $actor);
        $order->refresh();

        $this->assertSame(OrderStatus::Completed, $order->status);
        // Cash on delivery settles when the goods land, not before.
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertNotNull($order->completed_at);
    }

    public function test_rejecting_an_order_puts_the_stock_back(): void
    {
        [$order, $sellerOrder, $seller, $offer] = $this->placeOrder(quantity: 2, stock: 5);

        $this->assertSame(3, $offer->fresh()->stock);

        app(SellerOrderWorkflow::class)->transition(
            $sellerOrder,
            SellerOrderStatus::Rejected,
            $seller->user,
            'الكمية خلصت من الفرع'
        );

        $this->assertSame(5, $offer->fresh()->stock, 'Rejected units must return to the shelf.');
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame('الكمية خلصت من الفرع', $sellerOrder->fresh()->rejection_reason);
    }

    public function test_illegal_transitions_are_refused(): void
    {
        [, $sellerOrder, $seller] = $this->placeOrder();

        $this->expectException(\RuntimeException::class);

        // Pending -> Delivered skips acceptance and fulfilment.
        app(SellerOrderWorkflow::class)->transition($sellerOrder, SellerOrderStatus::Delivered, $seller->user);
    }

    public function test_a_seller_cannot_touch_another_stores_order(): void
    {
        [, $sellerOrder] = $this->placeOrder();
        $intruder = Seller::factory()->create();

        $this->actingAs($intruder->user)
            ->post(route('seller.orders.transition', $sellerOrder), ['status' => 'accepted'])
            ->assertForbidden();
    }

    public function test_a_seller_accepts_an_order_from_the_dashboard(): void
    {
        [, $sellerOrder, $seller] = $this->placeOrder();

        $this->actingAs($seller->user)
            ->post(route('seller.orders.transition', $sellerOrder), ['status' => 'accepted'])
            ->assertRedirect();

        $this->assertSame(SellerOrderStatus::Accepted, $sellerOrder->fresh()->status);
        $this->assertSame(1, $seller->fresh()->accepted_orders_count);
    }

    public function test_rejecting_without_a_reason_is_refused(): void
    {
        [, $sellerOrder, $seller] = $this->placeOrder();

        $this->actingAs($seller->user)
            ->post(route('seller.orders.transition', $sellerOrder), ['status' => 'rejected'])
            ->assertSessionHasErrors('reason');

        $this->assertSame(SellerOrderStatus::Pending, $sellerOrder->fresh()->status);
    }

    /** @return array{0: Order, 1: SellerOrder, 2: Seller, 3: SellerOffer} */
    private function placeOrder(int $quantity = 1, int $stock = 5): array
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create();
        $seller = $this->makeSellerServing($zone, $courier, 30, sellerScopedRate: true);
        $offer = $this->makeOffer($product, $seller, 500, stock: $stock);

        [$user, $address] = $this->makeCustomerWithAddress($zone);

        $this->actingAs($user)->post(route('cart.store'), ['offer_id' => $offer->id, 'quantity' => $quantity]);
        $this->actingAs($user)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'cod',
        ]);

        $order = Order::query()->latest('id')->firstOrFail();

        return [$order, $order->sellerOrders()->firstOrFail(), $seller->fresh(), $offer];
    }
}
