<x-app-layout :title="__('Grades')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Grades') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Manage the job grades employees can be placed in.') }}</p>
            </div>

            @can('grades.create')
                <x-ui.button href="{{ route('grades.create') }}">
                    {{ __('New grade') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'level', 'label' => __('Level'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'employees', 'label' => __('Employees'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];
        @endphp

        <x-ui.data-table :columns="$columns" :paginator="$grades" :sort="$sort" :direction="$direction" :empty="__('No grades found.')">
            @foreach ($grades as $grade)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-medium">{{ $grade->name }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ $grade->level }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($grade->employees_count) }}</td>
                    <td class="px-4 py-3">
                        @if ($grade->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @can('grades.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('grades.edit', $grade) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('grades.delete')
                                <form method="POST" action="{{ route('grades.destroy', $grade) }}"
                                    onsubmit="return confirm('{{ __('Delete this grade?') }}')">
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
