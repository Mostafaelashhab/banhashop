<x-layouts.app>
    <header class="stack-8" style="margin-block-end:20px">
        <h1>
            @if ($term !== '')
                نتائج البحث عن «{{ $term }}»
            @else
                ابحث في منتجات {{ config('banha.city') }}
            @endif
        </h1>
        @if ($term !== '')
            <p class="small muted num">{{ $products->total() }} نتيجة</p>
        @endif
    </header>

    <div style="max-width:520px;margin-block-end:22px">
        <x-search.form :value="$term" />
    </div>

    @if ($term === '')
        <x-ui.empty
            title="اكتب اسم المنتج أو الماركة"
            text="تقدر تبحث بالاسم، الماركة، الموديل، أو الباركود."
        />
    @elseif ($products->isEmpty())
        {{-- A failed search is a lead, not a dead end. --}}
        <x-ui.empty
            illustration="search"
            {{-- Arabic guillemets, not ASCII quotes. A double quote inside a
                 component attribute has to be written as &quot;, which Blade
                 passes through as six literal characters and then escapes
                 again on output — the entity ends up on screen. These need no
                 escaping, and unlike the bidi-neutral ASCII quote they mirror
                 correctly around a Latin term in RTL text. --}}
            title="مفيش نتائج لـ «{{ $term }}»"
            text="المنتج ده لسه مش موجود في كتالوج بنها شوب. اطلبه وهنستخدم طلبك عشان نوصل لمتاجر {{ config('banha.city') }} اللي توفره."
        >
            <x-slot:action>
                <a href="{{ route('product-requests.create', ['q' => $term]) }}" class="btn btn--primary">
                    اطلب «{{ \Illuminate\Support\Str::limit($term, 40) }}»
                </a>
            </x-slot:action>
        </x-ui.empty>
    @else
        <x-product.grid :products="$products" />
        {{ $products->links() }}
    @endif
</x-layouts.app>
