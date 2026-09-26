<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $role ? __('Edit role') : __('New role') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Set the details and pick what this role may do in each module.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            action="{{ $role ? route('roles.update', $role) : route('roles.store') }}"
            class="flex flex-col gap-6"
        >
            @csrf
            @if ($role)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                    <x-ui.input id="name" name="name" :value="old('name', $role?->name)" required autofocus />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="slug">{{ __('Slug') }}</x-ui.label>
                    <x-ui.input id="slug" name="slug" :value="old('slug', $role?->slug)" required />
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $role?->is_active ?? true)" />
                {{ __('Active') }}
            </label>

            @php
                $permissionFlags = [
                    'can_view' => __('View'),
                    'can_create' => __('Create'),
                    'can_update' => __('Update'),
                    'can_delete' => __('Delete'),
                ];
            @endphp

            <div
                class="flex flex-col gap-3"
                x-data="{
                    bodyBoxes(flag = null) {
                        const boxes = Array.from(this.$root.querySelectorAll('tbody input[type=checkbox]'));

                        return flag ? boxes.filter((box) => box.dataset.flag === flag) : boxes;
                    },
                    syncControl(control, boxes) {
                        const checked = boxes.filter((box) => box.checked).length;

                        control.checked = boxes.length > 0 && checked === boxes.length;
                        control.indeterminate = checked > 0 && checked < boxes.length;
                    },
                    syncControls() {
                        this.syncControl(this.$root.querySelector('[data-select-all]'), this.bodyBoxes());

                        this.$root.querySelectorAll('[data-select-column]').forEach((control) => {
                            this.syncControl(control, this.bodyBoxes(control.dataset.selectColumn));
                        });
                    },
                    tickGroup(flag, checked) {
                        this.bodyBoxes(flag).forEach((box) => {
                            box.checked = checked;
                        });

                        this.syncControls();
                    },
                }"
                x-init="syncControls()"
                @change="syncControls()"
            >
                <div class="flex flex-col gap-1">
                    <x-ui.label>{{ __('Permissions') }}</x-ui.label>
                    <p class="text-sm text-muted-foreground">{{ __('Choose what this role can do in each active module.') }}</p>
                </div>

                <div class="overflow-x-auto rounded-lg border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <th class="px-4 py-3 font-medium">
                                    <div class="flex items-center gap-2">
                                        <x-ui.checkbox
                                            data-select-all
                                            :disabled="$menus->isEmpty()"
                                            aria-label="{{ __('Select all permissions') }}"
                                            @change="tickGroup(null, $event.target.checked)"
                                        />
                                        {{ __('Menu') }}
                                    </div>
                                </th>
                                @foreach ($permissionFlags as $flag => $label)
                                    <th class="px-4 py-3 text-center font-medium">
                                        <div class="flex items-center justify-center gap-2">
                                            <x-ui.checkbox
                                                data-select-column="{{ $flag }}"
                                                :disabled="$menus->isEmpty()"
                                                aria-label="{{ __('Select all :permission permissions', ['permission' => $label]) }}"
                                                @change="tickGroup($event.target.dataset.selectColumn, $event.target.checked)"
                                            />
                                            {{ $label }}
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($menus as $menu)
                                @php
                                    $flags = $role?->menus->firstWhere('id', $menu->id)?->pivot;
                                @endphp
                                <tr>
                                    <td class="px-4 py-2.5 font-medium">{{ $menu->name }}</td>
                                    @foreach (array_keys($permissionFlags) as $flag)
                                        <td class="px-4 py-2.5 text-center">
                                            <x-ui.checkbox
                                                data-flag="{{ $flag }}"
                                                name="permissions[{{ $menu->id }}][{{ $flag }}]"
                                                value="1"
                                                :checked="(bool) ($flags?->{$flag} ?? false)"
                                            />
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                        {{ __('No active menus yet. Create menus first.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <x-ui.button variant="outline" href="{{ route('roles.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
