<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductRequest;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The seller-acquisition worklist: what customers in each area asked for and
 * nobody sells yet. Grouped by normalised text so the same product written
 * five ways still counts as one demand signal.
 */
class AdminProductRequestController extends Controller
{
    public function index(Request $request, SeoData $seo): View
    {
        $seo->title('طلبات المنتجات')->noindex(follow: false);

        $grouped = ProductRequest::query()
            ->selectRaw('normalized_key, MAX(query_text) as query_text, COUNT(*) as requests, MAX(created_at) as last_requested')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->groupBy('normalized_key')
            ->orderByDesc('requests')
            ->paginate(20)
            ->withQueryString();

        // Which areas the demand is coming from, for the same groups.
        $zonesByKey = ProductRequest::query()
            ->whereIn('normalized_key', collect($grouped->items())->pluck('normalized_key'))
            ->with('zone:id,name')
            ->get()
            ->groupBy('normalized_key')
            ->map(fn ($rows) => $rows->pluck('zone.name')->filter()->countBy());

        return view('pages.admin.product-requests', [
            'grouped' => $grouped,
            'zonesByKey' => $zonesByKey,
            'statuses' => [
                ProductRequest::STATUS_OPEN,
                ProductRequest::STATUS_SOURCING,
                ProductRequest::STATUS_FULFILLED,
                ProductRequest::STATUS_DECLINED,
            ],
        ]);
    }

    public function update(Request $request, ProductRequest $productRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,sourcing,fulfilled,declined'],
            'apply_to_group' => ['nullable', 'boolean'],
        ]);

        if ($validated['apply_to_group'] ?? false) {
            ProductRequest::where('normalized_key', $productRequest->normalized_key)
                ->update(['status' => $validated['status']]);
        } else {
            $productRequest->update(['status' => $validated['status']]);
        }

        return back()->with('status', 'تم تحديث حالة الطلب.');
    }
}
