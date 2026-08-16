@if (session('status') || session('error') || $errors->any())
    <div class="stack-12" style="margin-block-end:18px">
        @if (session('status'))
            <x-ui.alert tone="good" icon="check-circle">{{ session('status') }}</x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert tone="bad" icon="alert">{{ session('error') }}</x-ui.alert>
        @endif

        {{-- Field-level errors render next to their inputs; this is the summary
             for anything that has no field to attach to. --}}
        @if ($errors->has('checkout') || $errors->has('general'))
            <x-ui.alert tone="bad" icon="alert">
                {{ $errors->first('checkout') ?: $errors->first('general') }}
            </x-ui.alert>
        @endif
    </div>
@endif
