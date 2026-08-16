@php
    /** @var \Illuminate\Support\Collection $zones */
    $redirectTo = $redirectTo ?? route('account.addresses');
    $address = $address ?? null;
@endphp

<form method="POST" action="{{ $address ? route('account.addresses.update', $address) : route('account.addresses.store') }}">
    @csrf
    @if ($address)
        @method('PUT')
    @endif
    <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">

    <div class="field-grid field-grid--2">
        <x-ui.field name="recipient_name" label="اسم المستلم" :value="$address?->recipient_name" required autocomplete="name" />
        <x-ui.field name="phone" label="رقم الموبايل" :value="$address?->phone" required inputmode="tel"
                    placeholder="01xxxxxxxxx" autocomplete="tel" />
    </div>

    <x-ui.select-field
        name="shipping_zone_id"
        label="المنطقة"
        :options="$zones->pluck('name', 'id')->all()"
        :selected="$address?->shipping_zone_id"
        placeholder="اختر المنطقة"
        hint="تكلفة التوصيل بتتحسب حسب المنطقة دي."
        required
    />

    <x-ui.field name="street" label="الشارع" :value="$address?->street" required autocomplete="street-address" />

    <div class="field-grid field-grid--3">
        <x-ui.field name="building" label="رقم العقار" :value="$address?->building" />
        <x-ui.field name="floor" label="الدور" :value="$address?->floor" />
        <x-ui.field name="apartment" label="الشقة" :value="$address?->apartment" />
    </div>

    <x-ui.field name="landmark" label="علامة مميزة" :value="$address?->landmark"
                hint="اختياري — بيسهّل على المندوب يلاقيك." />
    <x-ui.field name="label" label="اسم العنوان" :value="$address?->label" placeholder="البيت، الشغل…" />

    <label class="check" style="margin-block-start:12px">
        <input type="checkbox" name="is_default" value="1" @checked($address?->is_default)>
        <span>اجعله العنوان الافتراضي</span>
    </label>

    <button type="submit" class="btn btn--primary" style="margin-block-start:14px">
        {{ $address ? 'حفظ التعديلات' : 'حفظ العنوان' }}
    </button>
</form>
