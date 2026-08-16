<x-layouts.dashboard heading="تعديل {{ $seller->name }}" nav="admin">
    <div style="max-width:720px">
        <form method="POST" action="{{ route('admin.sellers.update', $seller) }}">
            @csrf @method('PUT')

            <div class="stack">
                <section class="panel">
                    <div class="panel__head"><h2>البيانات</h2></div>
                    <div class="panel__body">
                        <x-ui.field name="name" label="اسم المتجر" :value="$seller->name" required />
                        <x-ui.field name="description" label="نبذة" type="textarea" :value="$seller->description" />

                        <x-ui.select-field
                            name="status"
                            label="حالة المتجر"
                            :options="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()"
                            :selected="$seller->status->value"
                            hint="إيقاف المتجر بيخفي كل عروضه من الموقع فورًا."
                            required
                        />

                        <label class="check" style="margin-block-start:12px">
                            <input type="checkbox" name="is_verified" value="1" @checked($seller->is_verified)>
                            <span>متجر موثّق</span>
                        </label>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel__head"><h2>التوصيل</h2></div>
                    <div class="panel__body">
                        <p class="field__label">مناطق التوصيل</p>
                        <div class="field-grid field-grid--2" style="margin-block-end:14px">
                            @foreach ($zones as $zone)
                                <label class="check">
                                    <input type="checkbox" name="zones[]" value="{{ $zone->id }}"
                                           @checked($seller->zones->contains('id', $zone->id))>
                                    <span>{{ $zone->name }}</span>
                                </label>
                            @endforeach
                        </div>

                        <p class="field__label">شركات التوصيل</p>
                        <div class="stack-8">
                            @foreach ($providers as $provider)
                                <label class="check">
                                    <input type="checkbox" name="providers[]" value="{{ $provider->id }}"
                                           @checked($seller->shippingProviders->contains('id', $provider->id))>
                                    <span>{{ $provider->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </section>

                <div class="row" style="gap:8px">
                    <button type="submit" class="btn btn--primary">حفظ</button>
                    <a href="{{ route('admin.sellers.index') }}" class="btn">رجوع</a>
                </div>
            </div>
        </form>
    </div>
</x-layouts.dashboard>
