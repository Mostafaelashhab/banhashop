@props(['placeholder' => 'ابحث عن منتج، ماركة، أو موديل', 'value' => null])

{{-- A plain GET form: results are a real, linkable, server-rendered page. --}}
<form method="GET" action="{{ route('search') }}" class="search-form" role="search">
    <label for="{{ $id = 'q-'.uniqid() }}" class="sr-only">ابحث في منتجات {{ config('banha.city') }}</label>
    <input
        type="search"
        id="{{ $id }}"
        name="q"
        class="search-form__input"
        placeholder="{{ $placeholder }}"
        value="{{ $value ?? request('q') }}"
        autocomplete="off"
        enterkeyhint="search"
    >
    <button type="submit" class="search-form__submit" aria-label="ابحث">
        <x-ui.icon name="search" :size="18" />
    </button>
</form>
