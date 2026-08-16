<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Enums\SellerOrderStatus;
use App\Enums\SellerStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductRequest;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Support\Seo\SeoData;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The metric that decides whether this marketplace is working is not signups
 * or page views — it is how many products have real local competition.
 */
class AdminDashboardController extends Controller
{
    public function index(SeoData $seo): View
    {
        $seo->title('مؤشرات المنصة')->noindex(follow: false);

        return view('pages.admin.dashboard', [
            'competition' => $this->competitionBreakdown(),
            'productsWithOffers' => Product::published()->where('offers_count', '>', 0)->count(),
            'productsTotal' => Product::published()->count(),
            'pendingProducts' => Product::where('status', ProductStatus::Pending)->count(),
            'activeSellers' => Seller::where('status', SellerStatus::Active)->count(),
            'pendingSellers' => Seller::where('status', SellerStatus::Pending)->count(),
            'openSellerOrders' => SellerOrder::open()->count(),
            'ordersThisMonth' => Order::where('placed_at', '>=', now()->startOfMonth())->count(),
            'revenueThisMonth' => (int) Order::where('placed_at', '>=', now()->startOfMonth())
                ->whereNot('status', 'cancelled')->sum('grand_total_cents'),
            'acceptanceRate' => $this->acceptanceRate(),
            'topRequests' => $this->topProductRequests(),
        ]);
    }

    /**
     * How many published products have 1 seller, 2 sellers, 3+ sellers.
     * Moving products up this ladder is the whole growth job.
     *
     * @return array<string, int>
     */
    private function competitionBreakdown(): array
    {
        $rows = DB::table('products')
            ->where('status', ProductStatus::Published->value)
            ->selectRaw('sellers_count, COUNT(*) as total')
            ->groupBy('sellers_count')
            ->pluck('total', 'sellers_count');

        $breakdown = ['none' => 0, 'one' => 0, 'two' => 0, 'three_plus' => 0];

        foreach ($rows as $sellers => $total) {
            $key = match (true) {
                $sellers == 0 => 'none',
                $sellers == 1 => 'one',
                $sellers == 2 => 'two',
                default => 'three_plus',
            };

            $breakdown[$key] += (int) $total;
        }

        return $breakdown;
    }

    /** Null until there is enough real data to mean anything. */
    private function acceptanceRate(): ?float
    {
        $total = SellerOrder::count();

        if ($total < 5) {
            return null;
        }

        $accepted = SellerOrder::whereNotIn('status', [
            SellerOrderStatus::Pending->value,
            SellerOrderStatus::Rejected->value,
        ])->count();

        return round($accepted / $total * 100, 1);
    }

    private function topProductRequests()
    {
        return ProductRequest::query()
            ->where('status', ProductRequest::STATUS_OPEN)
            ->selectRaw('normalized_key, MAX(query_text) as query_text, COUNT(*) as requests')
            ->groupBy('normalized_key')
            ->orderByDesc('requests')
            ->limit(6)
            ->get();
    }
}
