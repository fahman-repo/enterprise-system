@if (session('status'))
    <x-ui.alert>{{ session('status') }}</x-ui.alert>
@endif

@if ($errors->any())
    <x-ui.alert variant="destructive">
        <x-icon.alert-circle />
        <ul class="list-inside list-disc text-xs">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif
