<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The catalog is the platform's most valuable asset, so it has exactly one
 * gate: an admin. Sellers submit products; nothing reaches the storefront
 * without passing through here.
 */
class AdminProductController extends Controller
{
    public function index(Request $request, SeoData $seo): View
    {
        $seo->title('الكتالوج')->noindex(follow: false);

        $products = Product::query()
            ->with(['brand:id,name', 'category:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->when($request->boolean('no_image'), fn ($q) => $q->whereNull('image_path'))
            // Pending submissions first — this screen is a work queue.
            ->orderByRaw("FIELD(status, 'pending', 'draft', 'published', 'rejected')")
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('pages.admin.products', [
            'products' => $products,
            'statuses' => ProductStatus::cases(),
            'pendingCount' => Product::where('status', ProductStatus::Pending)->count(),
            // A product with no photograph renders as an empty grey square in
            // the catalog, so the gap is a work queue, not a statistic.
            'withoutImageCount' => Product::whereNull('image_path')->count(),
        ]);
    }

    public function create(SeoData $seo): View
    {
        $seo->title('إضافة منتج للكتالوج')->noindex(follow: false);

        return view('pages.admin.product-form', [
            'product' => new Product,
            'categories' => $this->categoryOptions(),
            'brands' => Brand::orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $product = Product::create($validated + [
            'created_by' => $request->user()->id,
            'approved_by' => $request->user()->id,
            'published_at' => $validated['status'] === ProductStatus::Published->value ? now() : null,
        ]);

        return redirect()->route('admin.products.edit', $product)
            ->with('status', 'تمت إضافة المنتج للكتالوج.');
    }

    public function edit(Product $product, SeoData $seo): View
    {
        $seo->title('تعديل منتج')->noindex(follow: false);

        $product->load(['attributes', 'images']);

        return view('pages.admin.product-form', [
            'product' => $product,
            'categories' => $this->categoryOptions(),
            'brands' => Brand::orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validated($request);

        $product->update($validated + [
            'published_at' => $validated['status'] === ProductStatus::Published->value
                ? ($product->published_at ?? now())
                : null,
        ]);

        return redirect()->route('admin.products.edit', $product)->with('status', 'تم حفظ المنتج.');
    }

    /** Approve or reject a seller-submitted product. */
    public function review(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        if ($validated['decision'] === 'approve') {
            $product->update([
                'status' => ProductStatus::Published,
                'category_id' => $validated['category_id'] ?? $product->category_id,
                'approved_by' => $request->user()->id,
                'published_at' => $product->published_at ?? now(),
                'rejection_reason' => null,
            ]);

            return back()->with('status', 'تم اعتماد المنتج ونشره في الكتالوج.');
        }

        $product->update([
            'status' => ProductStatus::Rejected,
            'rejection_reason' => $validated['rejection_reason'] ?? null,
            'approved_by' => $request->user()->id,
        ]);

        return back()->with('status', 'تم رفض المنتج.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'variant_label' => ['nullable', 'string', 'max:120'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'model' => ['nullable', 'string', 'max:120'],
            'mpn' => ['nullable', 'string', 'max:80'],
            'barcode' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:4000'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ]);
    }

    /** @return array<int, string> */
    private function categoryOptions(): array
    {
        return Category::with('parent:id,name')
            ->orderBy('parent_id')
            ->orderBy('position')
            ->get()
            ->mapWithKeys(fn (Category $c) => [
                $c->id => $c->parent ? $c->parent->name.' › '.$c->name : $c->name,
            ])
            ->all();
    }
}
