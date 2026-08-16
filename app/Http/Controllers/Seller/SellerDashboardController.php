<?php

namespace App\Http\Controllers\Seller;

use App\Enums\OfferStatus;
use App\Enums\SellerOrderStatus;
use App\Models\SellerOffer;
use App\Models\SellerOrder;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SellerDashboardController extends SellerController
{
    public function index(Request $request, SeoData $seo): View
    {
        $seller = $this->seller($request);
        $seo->title('لوحة المتجر')->noindex(follow: false);

        $staleCutoff = Carbon::now()->subHours((int) config('banha.inventory.stale_after_hours'));

        return view('pages.seller.dashboard', [
            'seller' => $seller,
            'activeOffers' => $seller->active_offers_count,
            'outOfStock' => SellerOffer::where('seller_id', $seller->id)
                ->where('status', OfferStatus::OutOfStock)->count(),
            // The number that actually needs the seller's attention today.
            'staleOffers' => SellerOffer::where('seller_id', $seller->id)
                ->where('status', OfferStatus::Active)
                ->where(fn ($q) => $q->whereNull('inventory_updated_at')->orWhere('inventory_updated_at', '<', $staleCutoff))
                ->count(),
            'pendingOrders' => SellerOrder::where('seller_id', $seller->id)
                ->where('status', SellerOrderStatus::Pending)->count(),
            'recentOrders' => SellerOrder::where('seller_id', $seller->id)
                ->with(['order:id,number,customer_name,placed_at', 'items:id,seller_order_id,product_name,quantity'])
                ->latest()->limit(6)->get(),
            'needsAttention' => SellerOffer::where('seller_id', $seller->id)
                ->where('status', OfferStatus::Active)
                ->where(fn ($q) => $q->whereNull('inventory_updated_at')->orWhere('inventory_updated_at', '<', $staleCutoff))
                ->with('product:id,name,slug,variant_label')
                ->orderBy('inventory_updated_at')
                ->limit(6)->get(),
        ]);
    }
}
