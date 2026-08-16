<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Seller\OfferInventory;
use App\Models\OfferInventoryLog;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerOffer;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\BuildsMarketplace;
use Tests\TestCase;

/**
 * Inline editing changes the interaction, not the rules: every write still
 * goes through OfferInventoryService, so the freshness timestamp and the audit
 * trail behave exactly as they do from the full form.
 */
class SellerOfferInventoryTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_a_seller_edits_price_and_stock_inline(): void
    {
        [$seller, $offer] = $this->sellerWithOffer();

        Livewire::actingAs($seller->user)
            ->test(OfferInventory::class)
            ->call('edit', $offer->id)
            ->assertSet('editingId', $offer->id)
            ->assertSet('price', '500.00')
            ->set('price', '450')
            ->set('stock', '7')
            ->call('save')
            ->assertSet('editingId', null)
            ->assertSee('تم تحديث');

        $offer->refresh();

        $this->assertSame(Money::toCents(450), $offer->price_cents);
        $this->assertSame(7, $offer->stock);
        $this->assertDatabaseHas('offer_inventory_logs', [
            'seller_offer_id' => $offer->id,
            'price_cents_before' => Money::toCents(500),
            'price_cents_after' => Money::toCents(450),
        ]);
    }

    public function test_invalid_input_is_rejected_without_touching_the_offer(): void
    {
        [$seller, $offer] = $this->sellerWithOffer();

        Livewire::actingAs($seller->user)
            ->test(OfferInventory::class)
            ->call('edit', $offer->id)
            ->set('price', '-5')
            ->call('save')
            ->assertHasErrors('price');

        $this->assertSame(Money::toCents(500), $offer->fresh()->price_cents);
    }

    public function test_confirming_stock_refreshes_freshness_without_changing_numbers(): void
    {
        [$seller, $offer] = $this->sellerWithOffer(stale: true);

        $this->assertTrue($offer->hasStaleInventory());

        Livewire::actingAs($seller->user)
            ->test(OfferInventory::class)
            ->call('confirm', $offer->id)
            ->assertSee('تم تأكيد مخزون');

        $offer->refresh();

        $this->assertFalse($offer->hasStaleInventory());
        $this->assertSame(5, $offer->stock);
        $this->assertSame(Money::toCents(500), $offer->price_cents);
        $this->assertDatabaseHas('offer_inventory_logs', [
            'seller_offer_id' => $offer->id,
            'reason' => OfferInventoryLog::REASON_SELLER_UPDATE,
        ]);
    }

    /** Offer ids come from the client, so ownership is re-checked every time. */
    public function test_a_seller_cannot_edit_another_stores_offer(): void
    {
        [, $offer] = $this->sellerWithOffer();
        $intruder = Seller::factory()->create();

        Livewire::actingAs($intruder->user)
            ->test(OfferInventory::class)
            ->call('edit', $offer->id)
            ->assertSet('editingId', null)
            ->call('confirm', $offer->id);

        $this->assertSame(Money::toCents(500), $offer->fresh()->price_cents);
        $this->assertDatabaseMissing('offer_inventory_logs', [
            'seller_offer_id' => $offer->id,
            'reason' => OfferInventoryLog::REASON_SELLER_UPDATE,
        ]);
    }

    public function test_the_table_filters_as_the_seller_types(): void
    {
        $seller = Seller::factory()->create();
        $wanted = Product::factory()->create(['name' => 'غسالة تجريبية']);
        $other = Product::factory()->create(['name' => 'موبايل تجريبي']);

        $this->makeOffer($wanted, $seller, 500);
        $this->makeOffer($other, $seller, 900);

        Livewire::actingAs($seller->user)
            ->test(OfferInventory::class)
            ->assertSee('غسالة تجريبية')
            ->assertSee('موبايل تجريبي')
            ->set('search', 'غسالة')
            ->assertSee('غسالة تجريبية')
            ->assertDontSee('موبايل تجريبي');
    }

    /** @return array{0: Seller, 1: SellerOffer} */
    private function sellerWithOffer(bool $stale = false): array
    {
        $seller = Seller::factory()->create();
        $product = Product::factory()->create();

        $offer = SellerOffer::factory()
            ->when($stale, fn ($f) => $f->stale())
            ->create([
                'product_id' => $product->id,
                'seller_id' => $seller->id,
                'price_cents' => Money::toCents(500),
                'stock' => 5,
            ]);

        return [$seller, $offer];
    }
}
