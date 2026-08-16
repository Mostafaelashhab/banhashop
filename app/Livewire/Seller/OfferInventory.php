<?php

namespace App\Livewire\Seller;

use App\Enums\OfferStatus;
use App\Models\OfferInventoryLog;
use App\Models\SellerOffer;
use App\Services\Catalog\OfferInventoryService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The seller's daily job, made a one-screen task: confirm stock is still
 * accurate, or correct a price, without leaving the table.
 *
 * Every write still goes through OfferInventoryService, so the freshness
 * timestamp and the audit log are updated exactly as they are from the plain
 * form — reactivity changes the interaction, not the rules.
 */
class OfferInventory extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    /** Offer currently being edited inline, if any. */
    public ?int $editingId = null;

    public string $price = '';

    public string $stock = '';

    public ?string $flash = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function edit(int $offerId): void
    {
        $offer = $this->ownedOffer($offerId);

        if ($offer === null) {
            return;
        }

        $this->editingId = $offer->id;
        $this->price = Money::decimal($offer->price_cents);
        $this->stock = (string) $offer->stock;
        $this->flash = null;
    }

    public function cancel(): void
    {
        $this->reset(['editingId', 'price', 'stock']);
        $this->resetValidation();
    }

    public function save(): void
    {
        $offer = $this->ownedOffer($this->editingId);

        if ($offer === null) {
            return;
        }

        $data = $this->validate([
            'price' => ['required', 'numeric', 'min:1', 'max:10000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
        ], attributes: ['price' => 'السعر', 'stock' => 'الكمية']);

        app(OfferInventoryService::class)->update($offer, [
            'price_cents' => Money::toCents($data['price']),
            'stock' => (int) $data['stock'],
        ], auth()->user());

        $this->flash = 'تم تحديث "'.$offer->product?->name.'".';
        $this->cancel();
    }

    /**
     * "Still accurate" — refreshes what the customer sees without changing a
     * number. This is the action that keeps the freshness indicator meaningful.
     */
    public function confirm(int $offerId): void
    {
        $offer = $this->ownedOffer($offerId);

        if ($offer === null) {
            return;
        }

        app(OfferInventoryService::class)->update($offer, [], auth()->user(), OfferInventoryLog::REASON_SELLER_UPDATE);

        $this->flash = 'تم تأكيد مخزون "'.$offer->product?->name.'".';
    }

    /** Ownership is re-checked on every action; never trust the id from the client. */
    private function ownedOffer(?int $offerId): ?SellerOffer
    {
        $seller = auth()->user()?->seller;

        if ($seller === null || $offerId === null) {
            return null;
        }

        return SellerOffer::query()
            ->where('seller_id', $seller->id)
            ->with('product:id,name,slug,variant_label')
            ->find($offerId);
    }

    public function render(): View
    {
        $seller = auth()->user()?->seller;

        abort_if($seller === null, 403);

        $offers = SellerOffer::query()
            ->where('seller_id', $seller->id)
            ->with('product:id,name,slug,variant_label,image_path')
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', $term));
            })
            ->orderByRaw('inventory_updated_at IS NULL DESC')
            ->orderBy('inventory_updated_at')
            ->paginate(20);

        return view('livewire.seller.offer-inventory', [
            'offers' => $offers,
            'statuses' => OfferStatus::cases(),
        ]);
    }
}
