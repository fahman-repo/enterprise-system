<x-app-layout title='Configure-Approval-Matrix'>
    <div class='mx-auto max-w-5xl'>
        @php
            $matrixActionUrl = route('approval-matrices.update', $moduleKey);
            $matrixIndexUrl = route('approval-matrices.index');
            $matrixIsActive = (bool) old('is_active', $matrix?->is_active ?? false);
        @endphp
        @include('partials.flash')

        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>{{ __('Configure :module', ['module' => $module->label()]) }}</x-ui.card-title>
                <x-ui.card-description>{{ __('Saving replaces this module configuration atomically. Existing requests keep their stage snapshots.') }}</x-ui.card-description>
            </x-ui.card-header>

            <x-ui.card-content>
                <form method='POST' action='{{ $matrixActionUrl }}' class='flex flex-col gap-6'>
                    @csrf
                    @method('PUT')
                    <input type='hidden' name='module_key' value='{{ $moduleKey }}'>

                    <label class='flex items-center gap-3 text-sm font-medium'>
                        <input type='hidden' name='is_active' value='0'>
                        <x-ui.checkbox name='is_active' value='1' :checked='$matrixIsActive' />
                        {{ __('Enable submissions for this module') }}
                    </label>

                    <div class='flex flex-col gap-3'>
                        <div>
                            <h2 class='text-sm font-semibold'>{{ __('Eligible maker roles') }}</h2>
                            <p class='text-xs text-muted-foreground'>{{ __('Any active user in these roles may submit changes.') }}</p>
                        </div>
                        <x-ui.select id='eligible-maker-roles' name='maker_roles[]' multiple size='3' required aria-label="{{ __('Eligible maker roles') }}">
                            @foreach ($roles as $role)
                                <option value='{{ $role->id }}' @selected(in_array($role->id, $makerRoles))>{{ $role->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class='flex flex-col gap-3' x-data="{ stages: @js($stageRows), addStage() { this.stages.push({ stage_number: this.stages.length + 1, name: '', role_ids: [] }) }, removeStage(index) { if (this.stages.length > 1) { this.stages.splice(index, 1) } } }">
                        <div class='flex flex-wrap items-end justify-between gap-3'>
                            <div>
                                <h2 class='text-sm font-semibold'>{{ __('Sequential approval stages') }}</h2>
                                <p class='text-xs text-muted-foreground'>{{ __('One approval is required at each stage. Add between 1 and 10 stages.') }}</p>
                            </div>
                            <x-ui.button type='button' variant='outline' size='sm' x-on:click='addStage'>{{ __('Add stage') }}</x-ui.button>
                        </div>

                        <div class='flex flex-col gap-3'>
                            <template x-for='(stage, index) in stages' :key='index'>
                                <div class='grid gap-3 rounded-md border border-border p-4 sm:grid-cols-[3rem_1fr_1fr_auto] sm:items-end'>
                                    <input type='hidden' :name='"stages["+index+"][stage_number]"' :value='index+1'>
                                    <div class='flex h-9 items-center justify-center rounded-md bg-muted text-sm font-semibold' x-text='index+1'></div>
                                    <div class='flex flex-col gap-2'>
                                        <x-ui.label x-bind:for="'stage-name-'+index">{{ __('Stage name') }}</x-ui.label>
                                        <x-ui.input x-bind:id="'stage-name-'+index" x-bind:name="'stages['+index+'][name]'" x-model='stage.name' maxlength='100' required />
                                    </div>
                                    <div class='flex flex-col gap-2'>
                                        <x-ui.label x-bind:for="'stage-roles-'+index">{{ __('Approver roles') }}</x-ui.label>
                                        <x-ui.select x-bind:id="'stage-roles-'+index" x-bind:name="'stages['+index+'][role_ids][]'" multiple size='3' required>
                                            @foreach ($roles as $role)
                                                <option value='{{ $role->id }}' :selected='stage.role_ids.includes({{ $role->id }})'>{{ $role->name }}</option>
                                            @endforeach
                                        </x-ui.select>
                                    </div>
                                    <x-ui.button type='button' variant='ghost' size='sm' x-on:click='removeStage(index)' x-bind:disabled='stages.length===1'>{{ __('Remove') }}</x-ui.button>
                                </div>
                            </template>
                        </div>
                    </div>

                    @if ($errors->any())
                        <x-ui.alert variant='destructive'>
                            <x-slot:title>{{ __('Please review the configuration') }}</x-slot:title>
                            <ul class='list-inside list-disc text-sm'>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-ui.alert>
                    @endif

                    <div class='flex items-center justify-end gap-2 border-t pt-4'>
                        <x-ui.button variant='outline' href='{{ $matrixIndexUrl }}' type='button'>{{ __('Cancel') }}</x-ui.button>
                        <x-ui.button type='submit'>{{ __('Save configuration') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card-content>
        </x-ui.card>
    </div>
</x-app-layout>
