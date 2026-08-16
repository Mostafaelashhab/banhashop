<?php

namespace Tests\Feature\Livewire;

use App\Livewire\CartCounter;
use App\Livewire\ShoppingCart;
use App\Livewire\ZonePicker;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\SellerOffer;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Shipping\ZoneContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\BuildsMarketplace;
use Tests\TestCase;

class ReactiveCartTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_the_zone_picker_persists_the_choice_and_announces_it(): void
    {
        $near = $this->makeZone('وسط بنها');
        $far = $this->makeZone('بنها الجديدة');

        session([ZoneContext::SESSION_KEY => $near->id]);

        Livewire::test(ZonePicker::class)
            ->assertSet('zoneId', $near->id)
            ->set('zoneId', $far->id)
            ->assertDispatched('zone-changed', zoneId: $far->id);

        // Written to the session too, so a full page load anywhere else agrees.
        $this->assertSame($far->id, session(ZoneContext::SESSION_KEY));
    }

    public function test_an_unknown_zone_is_ignored(): void
    {
        $zone = $this->makeZone();
        session([ZoneContext::SESSION_KEY => $zone->id]);

        Livewire::test(ZonePicker::class)
            ->set('zoneId', 99999)
            ->assertNotDispatched('zone-changed');

        $this->assertSame($zone->id, session(ZoneContext::SESSION_KEY));
    }

    public function test_the_header_counter_updates_when_the_cart_changes(): void
    {
        [$user, $offer] = $this->customerWithOffer();

        $counter = Livewire::actingAs($user)->test(CartCounter::class)->assertSet('count', 0);

        app(CartManager::class)->add($offer, 2);

        $counter->dispatch('cart-updated')->assertSet('count', 2);
    }

    public function test_quantity_changes_reprice_the_whole_group(): void
    {
        [$user, $offer] = $this->customerWithOffer(price: 500, shipping: 30);

        app(CartManager::class)->add($offer);
        $item = CartItem::firstOrFail();

        Livewire::actingAs($user)
            ->test(ShoppingCart::class)
            ->assertSee('530')
            ->call('updateQuantity', $item->id, 3)
            ->assertDispatched('cart-updated')
            ->assertSee('1,530');

        $this->assertSame(3, $item->fresh()->quantity);
    }

    public function test_crossing_a_free_delivery_threshold_updates_the_total_live(): void
    {
        [$user, $offer] = $this->customerWithOffer(price: 500, shipping: 40, freeOver: 900);

        app(CartManager::class)->add($offer);
        $item = CartItem::firstOrFail();

        $cart = Livewire::actingAs($user)->test(ShoppingCart::class)->assertSee('540');

        // Two units clears the threshold, so delivery drops to zero.
        $cart->call('updateQuantity', $item->id, 2)
            ->assertSee('مجاني')
            ->assertSee('1,000');
    }

    public function test_removing_the_last_line_empties_the_cart(): void
    {
        [$user, $offer] = $this->customerWithOffer();

        app(CartManager::class)->add($offer);
        $item = CartItem::firstOrFail();

        Livewire::actingAs($user)
            ->test(ShoppingCart::class)
            ->call('remove', $item->id)
            ->assertDispatched('cart-updated')
            ->assertSee('سلتك فاضية');

        $this->assertDatabaseCount('cart_items', 0);
    }

    /** Line ids come from the client, so ownership is re-checked server-side. */
    public function test_a_customer_cannot_touch_another_customers_cart_line(): void
    {
        [$victim, $offer] = $this->customerWithOffer();

        app(CartManager::class)->add($offer);
        $item = CartItem::firstOrFail();

        $attacker = User::factory()->create();

        Livewire::actingAs($attacker)
            ->test(ShoppingCart::class)
            ->call('remove', $item->id);

        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
    }

    /** @return array{0: User, 1: SellerOffer} */
    private function customerWithOffer(float $price = 500, float $shipping = 30, ?float $freeOver = null): array
    {
        $zone = $this->makeZone();
        $courier = $this->makeProvider();
        $product = Product::factory()->create();
        $seller = $this->makeSellerServing($zone, $courier, $shipping, freeOverEgp: $freeOver, sellerScopedRate: true);
        $offer = $this->makeOffer($product, $seller, $price, stock: 10);

        [$user] = $this->makeCustomerWithAddress($zone);
        session([ZoneContext::SESSION_KEY => $zone->id]);
        $this->actingAs($user);

        return [$user, $offer];
    }
}
