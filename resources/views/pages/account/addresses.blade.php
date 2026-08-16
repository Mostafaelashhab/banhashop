<x-layouts.app>
    <div class="row row--between" style="margin-block-end:18px">
        <h1>عناويني</h1>
        <a href="{{ route('account.orders') }}" class="btn btn--sm">طلباتي</a>
    </div>

    <div class="split">
        <div class="stack-12">
            @forelse ($addresses as $address)
                <article class="panel">
                    <div class="panel__body">
                        <div class="row row--between row--top">
                            <div>
                                <p class="strong">
                                    {{ $address->recipient_name }}
                                    @if ($address->is_default)
                                        <x-ui.badge tone="brand">افتراضي</x-ui.badge>
                                    @endif
                                </p>
                                <p class="small muted">{{ $address->summary() }}</p>
                                <p class="small muted">{{ $address->zone?->name }}</p>
                                <p class="small muted num">{{ $address->phone }}</p>
                            </div>

                            <form method="POST" action="{{ route('account.addresses.destroy', $address) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="icon-btn" aria-label="حذف العنوان">
                                    <x-ui.icon name="trash" :size="17" />
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <x-ui.empty title="مفيش عناوين محفوظة" text="ضيف عنوانك عشان نحسب تكلفة التوصيل بدقة." />
            @endforelse
        </div>

        <section class="panel">
            <div class="panel__head"><h2>إضافة عنوان</h2></div>
            <div class="panel__body">
                @include('pages.account._address-form', ['zones' => $zones])
            </div>
        </section>
    </div>
</x-layouts.app>
