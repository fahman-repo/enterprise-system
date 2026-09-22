<x-app-layout :title="__('Entities')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Entities') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Manage the vendor and customer entity master records.') }}</p>
            </div>

            @can('entities.create')
                <x-ui.button href="{{ route('entities.create') }}">
                    {{ __('New entity') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'name', 'label' => __('Entity'), 'sortable' => true],
                ['key' => 'type', 'label' => __('Type'), 'sortable' => true],
                ['key' => 'role', 'label' => __('Role'), 'sortable' => true],
                ['key' => 'contact', 'label' => __('Contact'), 'sortable' => false],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];

            $entityFilters = [
                'role' => [
                    'label' => __('Role'),
                    'all' => __('All roles'),
                    'options' => [
                        ['value' => 'vendor', 'label' => __('Vendor')],
                        ['value' => 'customer', 'label' => __('Customer')],
                        ['value' => 'both', 'label' => __('Vendor & customer')],
                    ],
                ],
                'type' => [
                    'label' => __('Type'),
                    'all' => __('All types'),
                    'options' => [
                        ['value' => 'company', 'label' => __('Company')],
                        ['value' => 'personal', 'label' => __('Personal')],
                    ],
                ],
                'status' => [
                    'label' => __('Status'),
                    'all' => __('All statuses'),
                    'options' => [
                        ['value' => 'active', 'label' => __('Active')],
                        ['value' => 'inactive', 'label' => __('Inactive')],
                    ],
                ],
            ];
        @endphp

        <x-ui.data-table :columns="$columns" :paginator="$entities" :sort="$sort" :direction="$direction" :empty="__('No entities found.')" :exclude-params="array_keys($entityFilters)">
            <x-slot:filters>
                <x-ui.filter-bar :filters="$entityFilters" />
            </x-slot:filters>

            @foreach ($entities as $entity)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <x-ui.avatar :name="$entity->name" />

                            <div class="flex flex-col">
                                <a href="{{ route('entities.show', $entity) }}" class="font-medium hover:underline">{{ $entity->name }}</a>
                                <span class="font-mono text-xs text-muted-foreground">{{ $entity->code }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $entity->typeLabel() }}</td>
                    <td class="px-4 py-3">
                        <x-ui.badge variant="outline">{{ $entity->roleLabel() }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">
                        {{ $entity->email ?? __('—') }}
                        @if ($entity->phone)
                            <span class="block text-xs">{{ $entity->phone }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if ($entity->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            <x-ui.button variant="outline" size="sm" href="{{ route('entities.show', $entity) }}">
                                {{ __('View') }}
                            </x-ui.button>

                            @can('entities.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('entities.edit', $entity) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('entities.delete')
                                <form method="POST" action="{{ route('entities.destroy', $entity) }}"
                                    onsubmit="return confirm('{{ __('Delete this entity?') }}')">
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
