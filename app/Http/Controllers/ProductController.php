<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\OfferComparisonService;
use App\Services\Catalog\ProductQueryService;
use App\Services\Shipping\ZoneContext;
use App\Support\Seo\IndexingPolicy;
use App\Support\Seo\JsonLd;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    public function index(
        Request $request,
        ProductQueryService $catalog,
        SeoData $seo,
        IndexingPolicy $indexing,
    ): View {
        $filters = $this->filtersFrom($request);
        $sort = $request->string('sort')->toString() ?: 'relevance';

        $products = $catalog->paginate(null, $filters, $sort);

        $trail = [
            ['label' => 'الرئيسية', 'url' => route('home')],
            ['label' => 'كل المنتجات', 'url' => route('products.index')],
        ];

        $seo->title('كل المنتجات في '.config('banha.city'))
            ->description('تصفح منتجات متاجر '.config('banha.city').' وقارن الأسعار والتوصيل قبل الطلب.')
            ->breadcrumbs($trail);

        $indexing->apply($seo, $request, route('products.index'));

        return view('pages.products', [
            'products' => $products,
            'heading' => 'كل المنتجات',
            'subheading' => 'كل ما تعرضه متاجر '.config('banha.city').' في مكان واحد.',
            'category' => null,
            'brands' => $this->brandFilterOptions(),
            'sort' => $sort,
            'trail' => $trail,
        ]);
    }

    public function show(
        Request $request,
        Product $product,
        OfferComparisonService $comparison,
        ZoneContext $zones,
        SeoData $seo,
        IndexingPolicy $indexing,
    ): View {
        if (! $product->isPublished()) {
            throw new NotFoundHttpException;
        }

        $product->load([
            'brand:id,name,slug',
            'category:id,name,slug,parent_id',
            'category.parent:id,name,slug',
            'images',
            'attributes',
        ]);

        $zone = $zones->current();
        $board = $comparison->build($product, $zone, $request->string('sort')->toString());

        // Sibling variants (other capacities/colours of the same model).
        $variants = $product->parent_id
            ? Product::query()->published()
                ->where('parent_id', $product->parent_id)
                ->orderBy('id')->get(['id', 'name', 'slug', 'variant_label'])
            : Product::query()->published()
                ->where('parent_id', $product->id)
                ->orderBy('id')->get(['id', 'name', 'slug', 'variant_label']);

        $trail = $this->breadcrumbTrailFor($product);

        $seo->title($product->meta_title ?: $product->displayName())
            ->description($product->meta_description ?: $product->description)
            ->ogType('product')
            ->breadcrumbs($trail)
            ->addSchema(JsonLd::product($product, $board, $this->imageUrl($product)));

        $indexing->apply($seo, $request, $product->url());

        return view('pages.product', [
            'product' => $product,
            'board' => $board,
            'zone' => $zone,
            'variants' => $variants,
            'trail' => $trail,
        ]);
    }

    /** @return array<int, array{label: string, url: ?string}> */
    private function breadcrumbTrailFor(Product $product): array
    {
        $trail = [['label' => 'الرئيسية', 'url' => route('home')]];

        foreach ($product->category?->ancestors() ?? [] as $ancestor) {
            $trail[] = ['label' => $ancestor->name, 'url' => $ancestor->url()];
        }

        if ($product->category) {
            $trail[] = ['label' => $product->category->name, 'url' => $product->category->url()];
        }

        $trail[] = ['label' => $product->displayName(), 'url' => $product->url()];

        return $trail;
    }

    private function imageUrl(Product $product): ?string
    {
        return $product->image_path ? asset('storage/'.$product->image_path) : null;
    }

    /** @return array<string, mixed> */
    private function filtersFrom(Request $request): array
    {
        return array_filter([
            'brand_id' => $request->integer('brand'),
            'min_price_cents' => $request->integer('min') ? $request->integer('min') * 100 : null,
            'max_price_cents' => $request->integer('max') ? $request->integer('max') * 100 : null,
            'available_only' => $request->boolean('available'),
        ]);
    }

    /** @return array<int, string> */
    private function brandFilterOptions(): array
    {
        return Brand::query()->active()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function category(
        Request $request,
        Category $category,
        ProductQueryService $catalog,
        SeoData $seo,
        IndexingPolicy $indexing,
    ): View {
        abort_unless($category->is_active, 404);

        $category->load(['children:id,parent_id,name,slug,position', 'parent:id,name,slug,parent_id']);

        $categoryIds = $category->children->pluck('id')->push($category->id)->all();

        $filters = $this->filtersFrom($request) + ['category_id' => $categoryIds];
        $sort = $request->string('sort')->toString() ?: 'relevance';

        $products = $catalog->paginate(null, $filters, $sort);

        $trail = [['label' => 'الرئيسية', 'url' => route('home')]];
        foreach ($category->ancestors() as $ancestor) {
            $trail[] = ['label' => $ancestor->name, 'url' => $ancestor->url()];
        }
        $trail[] = ['label' => $category->name, 'url' => $category->url()];

        $seo->title($category->meta_title ?: $category->name.' في '.config('banha.city'))
            ->description($category->meta_description ?: $category->description
                ?: 'قارن أسعار '.$category->name.' بين متاجر '.config('banha.city').' واعرف السعر النهائي شامل التوصيل.')
            ->breadcrumbs($trail);

        $indexing->apply($seo, $request, $category->url());

        return view('pages.products', [
            'products' => $products,
            'heading' => $category->name,
            'subheading' => $category->description,
            'category' => $category,
            'brands' => $this->brandFilterOptions(),
            'sort' => $sort,
            'trail' => $trail,
        ]);
    }
}
