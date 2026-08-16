<x-layouts.app>
    <x-layout.breadcrumbs :trail="$trail" />

    <header class="stack-8" style="margin-block-end:20px">
        <h1>{{ $heading }}</h1>
        @if ($subheading)
            <p class="lead">{{ $subheading }}</p>
        @endif
    </header>

    @if ($category && $category->children->isNotEmpty())
        <nav class="pill-group" aria-label="أقسام فرعية" style="margin-block-end:18px">
            @foreach ($category->children as $child)
                <a href="{{ $child->url() }}" class="pill">{{ $child->name }}</a>
            @endforeach
        </nav>
    @endif

    <x-catalog.toolbar :sort="$sort" :brands="$brands" :total="$products->total()" />

    @if ($products->isEmpty())
        <x-ui.empty
            title="لا توجد منتجات مطابقة"
            text="جرّب تقليل عوامل التصفية، أو اطلب المنتج من متاجر {{ config('banha.city') }}."
        >
            <x-slot:action>
                <a href="{{ route('product-requests.create') }}" class="btn btn--primary">اطلب منتجًا</a>
            </x-slot:action>
        </x-ui.empty>
    @else
        <x-product.grid :products="$products" />
        {{ $products->links() }}
    @endif
</x-layouts.app>
