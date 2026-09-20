<x-app-layout :title="__('Religions')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Religions') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Manage the religions available on employee records.') }}</p>
            </div>

            @can('religions.create')
                <x-ui.button href="{{ route('religions.create') }}">
                    {{ __('New religion') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'employees', 'label' => __('Employees'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'sort_order', 'label' => __('Sort'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];
        @endphp

        <x-ui.data-table :columns="$columns" :paginator="$religions" :sort="$sort" :direction="$direction" :empty="__('No religions found.')">
            @foreach ($religions as $religion)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-medium">{{ $religion->name }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($religion->employees_count) }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ $religion->sort_order }}</td>
                    <td class="px-4 py-3">
                        @if ($religion->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @can('religions.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('religions.edit', $religion) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('religions.delete')
                                <form method="POST" action="{{ route('religions.destroy', $religion) }}"
                                    onsubmit="return confirm('{{ __('Delete this religion?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button variant="destructive" size="sm" type="submit">
                                        {{ __('Delete') }}
                                    </x-ui.button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>
    </div>
</x-app-layout>
