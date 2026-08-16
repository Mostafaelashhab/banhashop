<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request, SeoData $seo): View
    {
        $orders = $request->user()->orders()
            ->with(['sellerOrders:id,order_id,seller_id,status,total_cents', 'sellerOrders.seller:id,name,slug'])
            ->latest()
            ->paginate(10);

        $seo->title('طلباتي')->noindex(follow: false);

        return view('pages.account.orders', ['orders' => $orders]);
    }

    public function show(Request $request, Order $order, SeoData $seo): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->load([
            'sellerOrders.seller:id,name,slug,phone',
            'sellerOrders.items',
            'sellerOrders.shippingProvider:id,name',
            'events',
            'payments',
        ]);

        $seo->title('الطلب '.$order->number)->noindex(follow: false);

        return view('pages.account.order', ['order' => $order]);
    }
}
