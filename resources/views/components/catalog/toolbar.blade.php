@props(['sort' => 'relevance', 'brands' => [], 'total' => 0])

@php
    $sorts = [
        'relevance' => 'الأكثر توفرًا',
        'price_asc' => 'الأقل سعرًا',
        'price_desc' => 'الأعلى سعرًا',
        'offers' => 'الأكثر عروضًا',
        'newest' => 'الأحدث',
    ];
@endphp

<div class="catalog-toolbar">
    <p class="small muted num">{{ $total }} منتج</p>

    {{-- Filter/sort links carry rel="nofollow": these URLs are noindex by
         policy and should not consume crawl budget. --}}
    <form method="GET" class="catalog-toolbar__filters">
        @foreach (request()->except(['sort', 'brand', 'page']) as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach

        @if (! empty($brands))
            <label for="brand-filter" class="sr-only">تصفية بالماركة</label>
            <select id="brand-filter" name="brand" class="select" onchange="this.form.submit()">
                <option value="">كل الماركات</option>
                @foreach ($brands as $id => $name)
                    <option value="{{ $id }}" @selected(request('brand') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        @endif

        <label for="sort-select" class="sr-only">ترتيب المنتجات</label>
        <select id="sort-select" name="sort" class="select" onchange="this.form.submit()">
            @foreach ($sorts as $key => $label)
                <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
            @endforeach
        </select>

        <noscript><button type="submit" class="btn btn--sm">تطبيق</button></noscript>
    </form>
</div>
