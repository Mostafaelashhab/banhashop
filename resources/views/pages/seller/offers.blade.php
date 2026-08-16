<x-layouts.dashboard heading="عروضي">
    <x-slot:actions>
        <a href="{{ route('seller.offers.create') }}" class="btn btn--primary btn--sm">
            <x-ui.icon name="plus" :size="15" class="btn__icon" />
            إضافة عرض
        </a>
    </x-slot:actions>

    <livewire:seller.offer-inventory />
</x-layouts.dashboard>
