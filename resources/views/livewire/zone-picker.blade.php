{{-- Still a real POST form: with JavaScript off the customer picks a zone and
     presses "تغيير", and the page reloads re-priced. With Livewire loaded the
     change is intercepted and only the offer board re-renders. --}}
<form method="POST" action="{{ route('zone.update') }}" class="zone-picker" wire:submit>
    @csrf
    <input type="hidden" name="redirect" value="{{ request()->fullUrl() }}">

    <x-ui.icon name="map-pin" :size="15" class="zone-picker__icon" />
    <span class="zone-picker__label" aria-hidden="true">التوصيل إلى</span>

    <label for="zone-select" class="sr-only">منطقة التوصيل</label>
    <select
        id="zone-select"
        name="zone_id"
        class="zone-picker__select"
        wire:model.live="zoneId"
    >
        @foreach ($zones as $zone)
            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
        @endforeach
    </select>

    <span wire:loading wire:target="zoneId" class="xsmall dim">…</span>

    <noscript>
        <button type="submit" class="btn btn--sm">تغيير</button>
    </noscript>
</form>
