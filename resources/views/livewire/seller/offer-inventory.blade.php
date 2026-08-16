<div>
    @if ($flash)
        <x-ui.alert tone="good" style="margin-block-end:16px">{{ $flash }}</x-ui.alert>
    @endif

    <div class="row row--wrap" style="gap:8px;margin-block-end:16px">
        <label for="offer-q" class="sr-only">ابحث في عروضك</label>
        <input type="search" id="offer-q" class="input" style="max-width:260px"
               placeholder="ابحث باسم المنتج"
               wire:model.live.debounce.400ms="search">

        <label for="offer-status" class="sr-only">الحالة</label>
        <select id="offer-status" class="select" style="width:auto;min-width:150px" wire:model.live="status">
            <option value="">كل الحالات</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>

        <span wire:loading class="small dim" style="align-self:center">جارٍ التحديث…</span>
    </div>

    @if ($offers->isEmpty())
        <x-ui.empty
            title="مفيش عروض مطابقة"
            text="ابدأ بالبحث عن المنتج في الكتالوج المركزي، وبعدين ضيف سعرك والكمية المتاحة."
        >
            <x-slot:action>
                <a href="{{ route('seller.offers.create') }}" class="btn btn--primary">إضافة عرض</a>
            </x-slot:action>
        </x-ui.empty>
    @else
        <div class="panel">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>السعر</th>
                            <th>المخزون</th>
                            <th>الحالة</th>
                            <th>آخر تحديث للمخزون</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($offers as $offer)
                            <tr wire:key="offer-{{ $offer->id }}">
                                <td>
                                    <a href="{{ route('products.show', $offer->product->slug) }}">
                                        {{ $offer->product->displayName() }}
                                    </a>
                                    <div class="xsmall dim">{{ $offer->condition->label() }}</div>
                                </td>

                                @if ($editingId === $offer->id)
                                    {{-- Inline edit: same service, same audit trail. --}}
                                    <td>
                                        <label class="sr-only" for="price-{{ $offer->id }}">السعر</label>
                                        <input type="number" step="0.01" min="1" id="price-{{ $offer->id }}"
                                               class="input" style="max-width:120px"
                                               wire:model="price" wire:keydown.enter="save">
                                        @error('price') <span class="field__error">{{ $message }}</span> @enderror
                                    </td>
                                    <td>
                                        <label class="sr-only" for="stock-{{ $offer->id }}">الكمية</label>
                                        <input type="number" min="0" id="stock-{{ $offer->id }}"
                                               class="input" style="max-width:90px"
                                               wire:model="stock" wire:keydown.enter="save">
                                        @error('stock') <span class="field__error">{{ $message }}</span> @enderror
                                    </td>
                                    <td colspan="2" class="small muted">
                                        الحفظ بيحدّث وقت آخر تأكيد للمخزون تلقائيًا.
                                    </td>
                                    <td>
                                        <div class="table__actions">
                                            <button type="button" class="btn btn--sm btn--primary"
                                                    wire:click="save" wire:loading.attr="disabled">حفظ</button>
                                            <button type="button" class="btn btn--sm" wire:click="cancel">إلغاء</button>
                                        </div>
                                    </td>
                                @else
                                    <td class="table__num">
                                        {{ money($offer->price_cents) }}
                                        @if ($offer->hasDiscount())
                                            <div class="xsmall dim"><s>{{ money($offer->compare_at_price_cents) }}</s></div>
                                        @endif
                                    </td>
                                    <td class="table__num">{{ $offer->stock }}</td>
                                    <td><x-ui.badge :tone="$offer->status->tone()">{{ $offer->status->label() }}</x-ui.badge></td>
                                    <td><x-offer.freshness :offer="$offer" /></td>
                                    <td>
                                        <div class="table__actions">
                                            <button type="button" class="btn btn--sm"
                                                    wire:click="confirm({{ $offer->id }})"
                                                    wire:target="confirm({{ $offer->id }})"
                                                    wire:loading.attr="disabled"
                                                    title="تأكيد أن المخزون لسه صحيح">
                                                <x-ui.icon name="refresh" :size="14" />
                                                <span class="sr-only">تأكيد المخزون</span>
                                            </button>
                                            <button type="button" class="btn btn--sm" wire:click="edit({{ $offer->id }})">
                                                تعديل سريع
                                            </button>
                                            <a href="{{ route('seller.offers.edit', $offer) }}" class="btn btn--sm">التفاصيل</a>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{ $offers->links() }}
    @endif
</div>
