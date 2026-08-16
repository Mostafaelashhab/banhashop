<x-layouts.app>
    <header class="stack-8" style="margin-block-end:20px">
        <h1>متاجر {{ config('banha.city') }}</h1>
        <p class="lead">المتاجر المحلية اللي بتعرض منتجاتها على بنها شوب.</p>
    </header>

    @if ($sellers->isEmpty())
        <x-ui.empty title="مفيش متاجر متاحة حاليًا" text="إحنا في مرحلة ضم المتاجر المحلية." />
    @else
        <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
            @foreach ($sellers as $seller)
                <x-seller.card :seller="$seller" />
            @endforeach
        </div>

        {{ $sellers->links() }}
    @endif
</x-layouts.app>
