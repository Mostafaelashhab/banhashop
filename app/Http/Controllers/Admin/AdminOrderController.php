<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminOrderController extends Controller
{
    public function index(Request $request, SeoData $seo): View
    {
        $seo->title('الطلبات')->noindex(follow: false);

        $orders = Order::query()
            ->with(['sellerOrders:id,order_id,seller_id,status,total_cents', 'sellerOrders.seller:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($w) => $w->where('number', 'like', $term)
                    ->orWhere('customer_phone', 'like', $term)
                    ->orWhere('customer_name', 'like', $term));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('pages.admin.orders', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function show(Order $order, SeoData $seo): View
    {
        $seo->title('الطلب '.$order->number)->noindex(follow: false);

        $order->load([
            'sellerOrders.seller:id,name,slug,phone',
            'sellerOrders.items',
            'events',
            'payments',
        ]);

        return view('pages.admin.order', ['order' => $order]);
    }
}
