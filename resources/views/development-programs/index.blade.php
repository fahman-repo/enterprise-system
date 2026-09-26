<x-app-layout :title="__('Development Programs')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Development Programs') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Manage trainings, seminars, workshops and certifications.') }}</p>
            </div>

            @can('development-programs.create')
                <x-ui.button href="{{ route('development-programs.create') }}">
                    {{ __('New program') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $typeFilters = [
                'type' => [
                    'label' => __('Type'),
                    'all' => __('All types'),
                    'options' => collect(App\Models\DevelopmentProgram::TYPES)->map(fn ($type): array => [
                        'value' => $type,
                        'label' => (new App\Models\DevelopmentProgram(['type' => $type]))->typeLabel(),
                    ])->all(),
                ],
                'status' => [
                    'label' => __('Status'),
                    'all' => __('All statuses'),
                    'options' => collect(App\Models\DevelopmentProgram::STATUSES)->map(fn ($status): array => [
                        'value' => $status,
                        'label' => (new App\Models\DevelopmentProgram(['status' => $status]))->statusLabel(),
                    ])->all(),
                ],
            ];

            $columns = [
                ['key' => 'code', 'label' => __('Code'), 'sortable' => true],
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'type', 'label' => __('Type'), 'sortable' => true],
                ['key' => 'start_date', 'label' => __('Dates'), 'sortable' => true],
                ['key' => 'participants', 'label' => __('Participants'), 'sortable' => false, 'align' => 'right'],
                ['key' => 'status', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];
        @endphp

        <x-ui.data-table :columns="$columns" :paginator="$programs" :sort="$sort" :direction="$direction" :empty="__('No development programs found.')" :exclude-params="array_keys($typeFilters)">
            <x-slot:filters>
                <x-ui.filter-bar :filters="$typeFilters" />
            </x-slot:filters>
            @foreach ($programs as $program)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ $program->code }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('development-programs.show', $program) }}" class="font-medium hover:underline">{{ $program->name }}</a>
                    </td>
                    <td class="px-4 py-3">
                        <x-ui.badge variant="outline">{{ $program->typeLabel() }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $program->start_date?->format('d M Y') }} – {{ $program->end_date?->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($program->enrollments_count) }}</td>
                    <td class="px-4 py-3">
                        <x-ui.badge variant="outline">{{ $program->statusLabel() }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            <x-ui.button variant="outline" size="sm" href="{{ route('development-programs.show', $program) }}">
                                {{ __('View') }}
                            </x-ui.button>

                            @can('development-programs.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('development-programs.edit', $program) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('development-programs.delete')
                                <form method="POST" action="{{ route('development-programs.destroy', $program) }}"
                                    onsubmit="return confirm('{{ __('Delete this program?') }}')">
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
