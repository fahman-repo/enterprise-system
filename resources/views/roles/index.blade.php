<x-app-layout :title="__('Roles')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Roles') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Define roles and what each one may do in every module.') }}</p>
            </div>

            @can('roles.create')
                <x-ui.button href="{{ route('roles.create') }}">
                    {{ __('New role') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'slug', 'label' => __('Slug'), 'sortable' => true],
                ['key' => 'users_count', 'label' => __('Users'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];
        @endphp

        <x-ui.data-table :columns="$columns" :paginator="$roles" :sort="$sort" :direction="$direction" :empty="__('No roles found.')">
            @foreach ($roles as $role)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-medium">{{ $role->name }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $role->slug }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $role->users_count }}</td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @can('roles.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('roles.edit', $role) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('roles.delete')
                                <form method="POST" action="{{ route('roles.destroy', $role) }}"
                                    onsubmit="return confirm('{{ __('Delete this role?') }}')">
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
