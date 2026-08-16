@props(['products', 'wide' => false])

<div class="product-grid {{ $wide ? 'product-grid--wide' : '' }}">
    @foreach ($products as $product)
        <x-product.card :product="$product" :eager="$loop->index < 4" />
    @endforeach
</div>
