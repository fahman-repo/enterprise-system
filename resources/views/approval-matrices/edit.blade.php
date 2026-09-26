<x-app-layout title='Configure-Approval-Matrix'>
    <div class='mx-auto max-w-5xl space-y-6'>
        @php
            $matrixActionUrl = route('approval-matrices.update', $moduleKey);
            $matrixIndexUrl = route('approval-matrices.index');
            $matrixIsActive = (bool) old('is_active', $matrix?->is_active ?? false);
            $roleOptions = $roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->values()->all();
        @endphp

        {{-- Page Header --}}
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <a
                    href="{{ $matrixIndexUrl }}"
                    class="inline-flex size-9 items-center justify-center rounded-lg border border-input bg-background text-muted-foreground shadow-2xs transition-colors hover:bg-accent hover:text-foreground"
                    title="{{ __('Back to Approval Matrices') }}"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('Configure :module', ['module' => $module->label()]) }}</h1>
                        @if ($matrix?->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ __('Set up makers who can submit changes, the approval mode, and the approver roles required for approval.') }}
                    </p>
                </div>
            </div>

            @if ($matrix)
                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <span class="rounded-md border border-border bg-muted/40 px-2 py-1">
                        {{ __('Version :version', ['version' => $matrix->configuration_version]) }}
                    </span>
                </div>
            @endif
        </div>

        @include('partials.flash')
        <form
            method='POST'
            action='{{ $matrixActionUrl }}'
            class='space-y-6'
            x-data="{
                stages: @js($stageRows),
                roles: @js($roleOptions),
                makerRoles: @js(array_map('intval', (array) $makerRoles)),
                addStage() {
                    if (this.stages.length >= 10) return;
                    this.stages.push({
                        stage_number: this.stages.length + 1,
                        name: '',
                        role_ids: []
                    });
                },
                removeStage(index) {
                    if (this.stages.length > 1) {
                        this.stages.splice(index, 1);
                        this.stages.forEach((s, idx) => { s.stage_number = idx + 1; });
                    }
                }
            }"
        >
            @csrf
            @method('PUT')
            <input type='hidden' name='module_key' value='{{ $moduleKey }}'>

            {{-- Module Status & General Settings --}}
            <x-ui.card>
                <x-ui.card-header class="pb-3">
                    <x-ui.card-title class="text-base">{{ __('Module Status') }}</x-ui.card-title>
                    <x-ui.card-description>{{ __('Control whether approval workflow is enforced for this module.') }}</x-ui.card-description>
                </x-ui.card-header>
                <x-ui.card-content>
                    <label class="flex items-start gap-3 rounded-lg border border-border/70 bg-accent/20 p-3.5 transition-colors hover:bg-accent/40 cursor-pointer">
                        <input type='hidden' name='is_active' value='0'>
                        <x-ui.checkbox name='is_active' value='1' :checked='$matrixIsActive' class="mt-0.5" />
                        <div class="flex flex-col gap-0.5">
                            <span class="text-sm font-semibold text-foreground">{{ __('Enable approval workflow for this module') }}</span>
                            <span class="text-xs text-muted-foreground">{{ __('When enabled, creation, updates, and deletions will generate approval requests requiring review under the selected mode.') }}</span>
                        </div>
                    </label>
                </x-ui.card-content>
            </x-ui.card>

            {{-- Approval Mode --}}
            <x-ui.card>
                <x-ui.card-header class="pb-3">
                    <x-ui.card-title class="text-base">{{ __('Approval mode') }}</x-ui.card-title>
                    <x-ui.card-description>{{ __('Sequential approves stages in order. Parallel opens all stages and the first approval completes the request.') }}</x-ui.card-description>
                </x-ui.card-header>
                <x-ui.card-content class="space-y-3">
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-border/70 bg-accent/20 p-3.5 transition-colors hover:bg-accent/40">
                        <input type='radio' name='mode' value='sequential' @checked($mode === 'sequential') class='mt-0.5'>
                        <span class="flex flex-col gap-0.5">
                            <span class="text-sm font-semibold text-foreground">{{ __('Sequential') }}</span>
                            <span class="text-xs text-muted-foreground">{{ __('Stages approve in order; each stage must approve before the next one opens.') }}</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-border/70 bg-accent/20 p-3.5 transition-colors hover:bg-accent/40">
                        <input type='radio' name='mode' value='parallel' @checked($mode === 'parallel') class='mt-0.5'>
                        <span class="flex flex-col gap-0.5">
                            <span class="text-sm font-semibold text-foreground">{{ __('Parallel') }}</span>
                            <span class="text-xs text-muted-foreground">{{ __('All stages stay open; the first approval wins and skips the rest, while any rejection rejects the request.') }}</span>
                        </span>
                    </label>
                </x-ui.card-content>
            </x-ui.card>

            {{-- Maker Roles Card --}}
            <x-ui.card>
                <x-ui.card-header class="pb-3">
                    <div class="flex items-center gap-2">
                        <div class="flex size-7 items-center justify-center rounded-md bg-primary/10 text-primary">
                            <x-icon.users class="size-4" />
                        </div>
                        <div>
                            <x-ui.card-title class="text-base">{{ __('Eligible maker roles') }}</x-ui.card-title>
                            <x-ui.card-description>{{ __('Active users assigned to any of these roles can initiate submissions.') }}</x-ui.card-description>
                        </div>
                    </div>
                </x-ui.card-header>
                <x-ui.card-content class="space-y-3">
                    <x-ui.multi-select-dropdown
                        id="eligible-maker-roles"
                        name="maker_roles[]"
                        :options="$roleOptions"
                        :selected="$makerRoles"
                        dynamic-selected="makerRoles"
                        :placeholder="__('Select maker roles...')"
                        :search-placeholder="__('Filter roles...')"
                        :aria-label="__('Eligible maker roles')"
                    />

                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <x-icon.alert-circle class="size-3.5 shrink-0" />
                        <span>{{ __('Note: Maker roles cannot overlap with any approver roles in the stages below.') }}</span>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
            {{-- Approval Stages Section --}}
            <x-ui.card>
                <x-ui.card-header class="border-b border-border/60 pb-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <div class="flex size-7 items-center justify-center rounded-md bg-primary/10 text-primary">
                                <x-icon.layers class="size-4" />
                            </div>
                            <div>
                                <x-ui.card-title class="text-base">{{ __('Approval stages') }}</x-ui.card-title>
                                <x-ui.card-description>{{ __('Each stage lists the roles allowed to decide. Sequential mode approves stages in order; parallel mode lets any pending stage decide and the first approval completes the request. Configure between 1 and 10 stages.') }}</x-ui.card-description>
                            </div>
                        </div>

                        <x-ui.button
                            type="button"
                            variant="outline"
                            size="sm"
                            x-on:click="addStage"
                            x-bind:disabled="stages.length >= 10"
                            class="gap-1.5"
                        >
                            <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span>{{ __('Add stage') }}</span>
                        </x-ui.button>
                    </div>
                </x-ui.card-header>

                <x-ui.card-content class="pt-6">
                    <div class="space-y-4">
                        <template x-for="(stage, index) in stages" :key="index">
                            <div class="relative rounded-xl border border-border/80 bg-card/60 p-4 shadow-2xs backdrop-blur-xs transition-all hover:border-border">
                                <input type="hidden" :name="'stages['+index+'][stage_number]'" :value="index+1">

                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex size-6 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground shadow-2xs" x-text="index+1"></span>
                                        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground" x-text="'Stage ' + (index+1)"></span>
                                    </div>

                                    <x-ui.button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        x-on:click="removeStage(index)"
                                        x-bind:disabled="stages.length === 1"
                                        class="h-7 text-xs text-muted-foreground hover:bg-destructive/10 hover:text-destructive disabled:opacity-30"
                                        title="{{ __('Remove this stage') }}"
                                    >
                                        <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                        <span>{{ __('Remove') }}</span>
                                    </x-ui.button>
                                </div>

                                <div class="mt-4 grid gap-4 md:grid-cols-2">
                                    {{-- Stage Name Input --}}
                                    <div class="flex flex-col gap-1.5">
                                        <x-ui.label x-bind:for="'stage-name-'+index" class="text-xs font-medium">
                                            {{ __('Stage name') }} <span class="text-destructive">*</span>
                                        </x-ui.label>
                                        <x-ui.input
                                            x-bind:id="'stage-name-'+index"
                                            x-bind:name="'stages['+index+'][name]'"
                                            x-model="stage.name"
                                            placeholder="{{ __('e.g., Department Manager Review') }}"
                                            maxlength="100"
                                            required
                                        />
                                    </div>

                                    {{-- Stage Approver Roles Dropdown --}}
                                    <div class="flex flex-col gap-1.5">
                                        <x-ui.label x-bind:for="'stage-roles-'+index" class="text-xs font-medium">
                                            {{ __('Approver roles') }} <span class="text-destructive">*</span>
                                        </x-ui.label>
                                        <x-ui.multi-select-dropdown
                                            x-bind:id="'stage-roles-'+index"
                                            :dynamic-name="'\'stages[\'+index+\'][role_ids][]\''"
                                            :options="$roleOptions"
                                            dynamic-selected="stage.role_ids"
                                            :placeholder="__('Select approver roles...')"
                                            :search-placeholder="__('Filter roles...')"
                                            aria-label="{{ __('Approver roles') }}"
                                        />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

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

            <div class='flex items-center justify-end gap-3 border-t border-border pt-4'>
                <x-ui.button variant='outline' href='{{ $matrixIndexUrl }}' type='button'>{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type='submit'>{{ __('Save configuration') }}</x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>



