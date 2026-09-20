<x-app-layout :title="__('Users')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Users') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Manage application users and their roles.') }}</p>
            </div>

            @can('users.create')
                <x-ui.button href="{{ route('users.create') }}">
                    {{ __('New user') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'email', 'label' => __('Email'), 'sortable' => true],
                ['key' => 'role', 'label' => __('Role'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];
        @endphp

        <x-ui.data-table :columns="$columns" :paginator="$users" :sort="$sort" :direction="$direction" :empty="__('No users found.')">
            @foreach ($users as $user)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        @if ($user->role)
                            <x-ui.badge variant="secondary">{{ $user->role->name }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('No role') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @can('users.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('users.edit', $user) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('users.delete')
                                <form method="POST" action="{{ route('users.destroy', $user) }}"
                                    onsubmit="return confirm('{{ __('Delete this user?') }}')">
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
