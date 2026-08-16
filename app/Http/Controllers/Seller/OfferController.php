<?php

namespace App\Http\Controllers\Seller;

use App\Enums\OfferCondition;
use App\Enums\OfferStatus;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\OfferInventoryLog;
use App\Models\Product;
use App\Models\SellerOffer;
use App\Services\Catalog\OfferInventoryService;
use App\Services\Catalog\ProductQueryService;
use App\Support\Money;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OfferController extends SellerController
{
    public function __construct(private readonly OfferInventoryService $inventory) {}

    /** The offers table is a Livewire component; this only guards the page. */
    public function index(Request $request, SeoData $seo): View
    {
        $this->seller($request);
        $seo->title('عروضي')->noindex(follow: false);

        return view('pages.seller.offers');
    }

    /**
     * Adding an offer starts from the central catalog, never from a blank
     * product form — that is what keeps duplicates out of the catalog.
     */
    public function create(Request $request, ProductQueryService $catalog, SeoData $seo): View
    {
        $seller = $this->seller($request);
        $seo->title('إضافة عرض')->noindex(follow: false);

        $term = trim((string) $request->query('q', ''));
        $results = $term === ''
            ? collect()
            : $catalog->paginate($term, [], 'relevance', 10);

        $product = $request->filled('product')
            ? Product::query()->where('slug', $request->string('product'))->first()
            : null;

        // A store may not list the same product twice under the same condition.
        $existing = $product
            ? SellerOffer::where('seller_id', $seller->id)->where('product_id', $product->id)->first()
            : null;

        return view('pages.seller.offer-create', [
            'term' => $term,
            'results' => $results,
            'product' => $product,
            'existing' => $existing,
            'conditions' => OfferCondition::cases(),
            // Leaf categories only — a product belongs to "غسالات", not to
            // "أجهزة منزلية". Resolved here so Blade never queries.
            'categories' => Category::query()->whereNotNull('parent_id')
                ->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $seller = $this->seller($request);

        $validated = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('status', ProductStatus::Published->value)],
            'price' => ['required', 'numeric', 'min:1', 'max:10000000'],
            'compare_at_price' => ['nullable', 'numeric', 'gte:price', 'max:10000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'condition' => ['required', Rule::enum(OfferCondition::class)],
            'sku' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:200'],
        ], [
            'compare_at_price.gte' => 'السعر قبل الخصم لازم يكون أكبر من أو يساوي السعر الحالي.',
        ]);

        $duplicate = SellerOffer::where('seller_id', $seller->id)
            ->where('product_id', $validated['product_id'])
            ->where('condition', $validated['condition'])
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['product_id' => 'عندك عرض بالفعل على نفس المنتج بنفس الحالة. عدّله بدل ما تضيف عرض جديد.']);
        }

        $offer = $this->inventory->create([
            'product_id' => $validated['product_id'],
            'seller_id' => $seller->id,
            'price_cents' => Money::toCents($validated['price']),
            'compare_at_price_cents' => isset($validated['compare_at_price'])
                ? Money::toCents($validated['compare_at_price'])
                : null,
            'stock' => $validated['stock'],
            'condition' => $validated['condition'],
            'sku' => $validated['sku'] ?? null,
            'note' => $validated['note'] ?? null,
            'status' => $validated['stock'] > 0 ? OfferStatus::Active : OfferStatus::OutOfStock,
        ], $request->user());

        return redirect()->route('seller.offers.index')
            ->with('status', 'تمت إضافة العرض على "'.$offer->product?->name.'".');
    }

    public function edit(Request $request, SellerOffer $offer, SeoData $seo): View
    {
        $this->authorizeOffer($request, $offer);
        $seo->title('تعديل العرض')->noindex(follow: false);

        $offer->load('product:id,name,slug,variant_label,image_path');

        return view('pages.seller.offer-edit', [
            'offer' => $offer,
            'conditions' => OfferCondition::cases(),
            'logs' => $offer->inventoryLogs()->latest('created_at')->limit(10)->get(),
        ]);
    }

    public function update(Request $request, SellerOffer $offer): RedirectResponse
    {
        $this->authorizeOffer($request, $offer);

        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:1', 'max:10000000'],
            'compare_at_price' => ['nullable', 'numeric', 'gte:price', 'max:10000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'status' => ['required', Rule::in(array_map(fn ($s) => $s->value, OfferStatus::sellerSelectable()))],
            'sku' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $this->inventory->update($offer, [
            'price_cents' => Money::toCents($validated['price']),
            'compare_at_price_cents' => isset($validated['compare_at_price'])
                ? Money::toCents($validated['compare_at_price'])
                : null,
            'stock' => $validated['stock'],
            'status' => OfferStatus::from($validated['status']),
            'sku' => $validated['sku'] ?? null,
            'note' => $validated['note'] ?? null,
        ], $request->user());

        return redirect()->route('seller.offers.index')->with('status', 'تم تحديث العرض.');
    }

    /**
     * "Still accurate" — a one-tap confirmation that refreshes the inventory
     * timestamp without changing any number. This is what keeps the freshness
     * indicator meaningful instead of decorative.
     */
    public function confirm(Request $request, SellerOffer $offer): RedirectResponse
    {
        $this->authorizeOffer($request, $offer);

        $this->inventory->update($offer, [], $request->user(), OfferInventoryLog::REASON_SELLER_UPDATE);

        return back()->with('status', 'تم تأكيد المخزون. الوقت المعروض للعملاء اتحدّث دلوقتي.');
    }

    public function destroy(Request $request, SellerOffer $offer): RedirectResponse
    {
        $this->authorizeOffer($request, $offer);
        $offer->delete();

        return redirect()->route('seller.offers.index')->with('status', 'تم حذف العرض.');
    }

    /** Catalog lookup used by the "add offer" flow. */
    public function catalogSearch(Request $request, ProductQueryService $catalog, SeoData $seo): View
    {
        return $this->create($request, $catalog, $seo);
    }

    /** No catalog match? Submit the product for the admin to review. */
    public function requestProduct(Request $request): RedirectResponse
    {
        // Ownership check only — the submission belongs to the signed-in user.
        $this->seller($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'model' => ['nullable', 'string', 'max:120'],
            'barcode' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        Product::create($validated + [
            'status' => ProductStatus::Pending,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('seller.offers.create')
            ->with('status', 'تم إرسال المنتج لفريق الكتالوج للمراجعة. هيظهرلك أول ما يتوافق عليه.');
    }

    private function authorizeOffer(Request $request, SellerOffer $offer): void
    {
        abort_unless($offer->seller_id === $this->seller($request)->id, 403);
    }
}
