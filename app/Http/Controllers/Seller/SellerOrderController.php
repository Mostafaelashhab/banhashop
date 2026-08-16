<?php

namespace App\Http\Controllers\Seller;

use App\Enums\SellerOrderStatus;
use App\Models\SellerOrder;
use App\Services\Orders\SellerOrderWorkflow;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class SellerOrderController extends SellerController
{
    public function index(Request $request, SeoData $seo): View
    {
        $seller = $this->seller($request);
        $seo->title('طلبات المتجر')->noindex(follow: false);

        $orders = SellerOrder::where('seller_id', $seller->id)
            ->with(['order:id,number,customer_name,customer_phone,shipping_zone_name,placed_at', 'items'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->query('view') === 'open', fn ($q) => $q->open())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.seller.orders', [
            'orders' => $orders,
            'statuses' => SellerOrderStatus::cases(),
        ]);
    }

    public function show(Request $request, SellerOrder $sellerOrder, SeoData $seo): View
    {
        $this->authorizeOrder($request, $sellerOrder);
        $seo->title('طلب '.$sellerOrder->reference)->noindex(follow: false);

        $sellerOrder->load([
            'order',
            'items',
            'shippingProvider:id,name',
            'shipment',
        ]);

        return view('pages.seller.order', ['sellerOrder' => $sellerOrder]);
    }

    public function transition(
        Request $request,
        SellerOrder $sellerOrder,
        SellerOrderWorkflow $workflow,
    ): RedirectResponse {
        $this->authorizeOrder($request, $sellerOrder);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(SellerOrderStatus::class)],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $target = SellerOrderStatus::from($validated['status']);

        // Rejecting is a real cost to the customer: make the store say why.
        if ($target === SellerOrderStatus::Rejected && blank($validated['reason'] ?? null)) {
            return back()->withErrors(['reason' => 'اكتب سبب الرفض عشان العميل يفهم اللي حصل.']);
        }

        try {
            $workflow->transition($sellerOrder, $target, $request->user(), $validated['reason'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'تم تحديث حالة الطلب إلى "'.$target->label().'".');
    }

    private function authorizeOrder(Request $request, SellerOrder $sellerOrder): void
    {
        abort_unless($sellerOrder->seller_id === $this->seller($request)->id, 403);
    }
}
