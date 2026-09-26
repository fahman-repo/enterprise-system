<x-app-layout title='Approval-Request'>
    <div class='mx-auto flex max-w-6xl flex-col gap-6'>
        @php
            $approvalsIndexUrl = route('approvals.index');
            $approveUrl = route('approvals.approve', $request);
            $rejectUrl = route('approvals.reject', $request);
            $cancelUrl = route('approvals.cancel', $request);
        @endphp
        <div class='flex flex-wrap items-start justify-between gap-4'>
            <div class='flex flex-col gap-1'>
                <x-ui.button variant='ghost' size='sm' href='{{ $approvalsIndexUrl }}'>{{ __('Back to approvals') }}</x-ui.button>
                <h1 class='text-xl font-semibold tracking-tight'>{{ __('Request #:id', ['id' => $request->id]) }}</h1>
                <p class='text-sm text-muted-foreground'>{{ $module->label() }} · {{ $request->actionLabel() }}</p>
            </div>
            <x-ui.badge :variant='$request->statusVariant()'>{{ $request->status }}</x-ui.badge>
        </div>

        @include('partials.flash')

        <div class='grid gap-6 lg:grid-cols-[1fr_22rem]'>
            <div class='flex flex-col gap-6'>
                <x-ui.card>
                    <x-ui.card-header>
                        <x-ui.card-title>{{ __('Proposed changes') }}</x-ui.card-title>
                        <x-ui.card-description>{{ __('Values are the submitted snapshot and are not applied until final approval.') }}</x-ui.card-description>
                    </x-ui.card-header>
                    <div class='overflow-x-auto border-t'>
                        <table class='w-full text-sm'>
                            <thead class='bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground'>
                                <tr>
                                    <th class='px-4 py-3 font-medium'>{{ __('Field') }}</th>
                                    <th class='px-4 py-3 font-medium'>{{ __('Before') }}</th>
                                    <th class='px-4 py-3 font-medium'>{{ __('Proposed') }}</th>
                                </tr>
                            </thead>
                            <tbody class='divide-y'>
                                @forelse ($changes as $field => $change)
                                    <tr class='align-top'>
                                        <td class='px-4 py-3 font-medium'>{{ __(str_replace('_', ' ', ucfirst($field))) }}</td>
                                        <td class='px-4 py-3 text-muted-foreground'>{{ is_bool($change['old']) ? ($change['old'] ? __('Yes') : __('No')) : ($change['old'] ?? '—') }}</td>
                                        <td class='px-4 py-3'>{{ is_bool($change['new']) ? ($change['new'] ? __('Yes') : __('No')) : ($change['new'] ?? '—') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan='3' class='px-4 py-8 text-center text-muted-foreground'>{{ __('No field changes.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <x-ui.card-header>
                        <x-ui.card-title>{{ __('Stage history') }}</x-ui.card-title>
                        <x-ui.card-description>{{ __('Approver assignments are immutable snapshots of the matrix at submission.') }}</x-ui.card-description>
                    </x-ui.card-header>
                    <div class='overflow-x-auto border-t'>
                        <table class='w-full text-sm'>
                            <thead class='bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground'>
                                <tr>
                                    <th class='px-4 py-3 font-medium'>{{ __('Stage') }}</th>
                                    <th class='px-4 py-3 font-medium'>{{ __('Eligible roles') }}</th>
                                    <th class='px-4 py-3 font-medium'>{{ __('Decision') }}</th>
                                    <th class='px-4 py-3 font-medium'>{{ __('Comment') }}</th>
                                </tr>
                            </thead>
                            <tbody class='divide-y'>
                                @foreach ($request->stages as $stage)
                                    <tr class='align-top'>
                                        <td class='px-4 py-3 font-medium'>{{ $stage->stage_number }}. {{ $stage->name }}</td>
                                        <td class='px-4 py-3 text-muted-foreground'>{{ $stage->roles->pluck('role_name')->implode(', ') }}</td>
                                        <td class='px-4 py-3'>
                                            <x-ui.badge :variant='$stage->statusVariant()'>{{ $stage->status }}</x-ui.badge>
                                            @if ($stage->decided_at)
                                                <div class='mt-1 text-xs text-muted-foreground'>{{ $stage->decided_by_snapshot['name'] ?? $stage->decidedBy?->name }} · {{ $stage->decided_at->format('d M Y H:i') }}</div>
                                            @endif
                                        </td>
                                        <td class='px-4 py-3 text-muted-foreground'>{{ $stage->comment ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <x-ui.card-header>
                        <x-ui.card-title>{{ __('Timeline') }}</x-ui.card-title>
                        <x-ui.card-description>{{ __('Submission and decision events for this request.') }}</x-ui.card-description>
                    </x-ui.card-header>
                    <div class='border-t p-6'>
                        @if ($timeline->isEmpty())
                            <p class='text-sm text-muted-foreground'>{{ __('No timeline events.') }}</p>
                        @else
                            <ol class='flex flex-col gap-4'>
                                @foreach ($timeline as $activity)
                                    <li class='border-l-2 border-border pl-4'>
                                        <div class='flex flex-wrap items-center gap-2'>
                                            <span class='text-sm font-medium'>{{ $activity->eventLabel() }}</span>
                                            <x-ui.badge variant='outline'>{{ $activity->created_at?->format('d M Y H:i') }}</x-ui.badge>
                                        </div>
                                        <div class='mt-1 text-sm text-muted-foreground'>{{ $activity->causerName() }}</div>
                                        @if ($activity->description)
                                            <div class='mt-1 text-sm'>{{ $activity->description }}</div>
                                        @endif
                                        @if ($activity->getExtraProperty('comment'))
                                            <div class='mt-1 text-sm text-muted-foreground'>{{ $activity->getExtraProperty('comment') }}</div>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </x-ui.card>
            </div>
            <div class='flex flex-col gap-6'>
                <x-ui.card>
                    <x-ui.card-header>
                        <x-ui.card-title>{{ __('Request summary') }}</x-ui.card-title>
                    </x-ui.card-header>
                    <dl class='grid gap-4 border-t p-6 text-sm'>
                        <div><dt class='text-muted-foreground'>{{ __('Maker') }}</dt><dd class='font-medium'>{{ $request->maker_snapshot['name'] ?? $request->maker?->name ?? '—' }}</dd></div>
                        <div><dt class='text-muted-foreground'>{{ __('Submitted') }}</dt><dd>{{ $request->submitted_at?->format('d M Y H:i') }}</dd></div>
                        <div><dt class='text-muted-foreground'>{{ __('Matrix version') }}</dt><dd>{{ $request->matrix_configuration_version }}</dd></div>
                        <div><dt class='text-muted-foreground'>{{ __('Mode') }}</dt><dd>{{ __(ucfirst($request->approval_mode ?? 'sequential')) }}</dd></div>
                        @if ($request->resolution_comment)
                            <div><dt class='text-muted-foreground'>{{ __('Resolution comment') }}</dt><dd>{{ $request->resolution_comment }}</dd></div>
                        @endif
                    </dl>
                </x-ui.card>

                @can('approvals.update')
                    @if ($canDecide)
                        <x-ui.card
                            x-data="{ decision: null }"
                        >
                            <x-ui.card-header>
                                <x-ui.card-title>{{ __('Record a decision') }}</x-ui.card-title>
                                @if (($request->approval_mode ?? 'sequential') === 'parallel')
                                    <x-ui.card-description>{{ __('Any eligible approver from any pending stage can approve; the first approval completes the request.') }}</x-ui.card-description>
                                @else
                                    <x-ui.card-description>{{ __('Approval is optional-comment; rejection requires a comment.') }}</x-ui.card-description>
                                @endif
                            </x-ui.card-header>
                            <x-ui.card-content class='flex gap-3'>
                                <x-ui.button type='button' class='flex-1' x-on:click="decision = 'approve'">{{ __('Approve stage') }}</x-ui.button>
                                <x-ui.button type='button' variant='destructive' class='flex-1' x-on:click="decision = 'reject'">{{ __('Reject request') }}</x-ui.button>
                            </x-ui.card-content>

                            <template x-if="decision !== null">
                                <div
                                    class='fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4'
                                    x-on:click.self="decision = null"
                                    x-on:keydown.escape.window="decision = null"
                                >
                                    <div role='dialog' aria-modal='true' aria-labelledby='decision-dialog-title' class='w-full max-w-md rounded-xl border bg-card p-6 text-card-foreground shadow-lg'>
                                        <h2 id='decision-dialog-title' class='text-lg font-semibold tracking-tight' x-text="decision === 'approve' ? @js(__('Approve stage')) : @js(__('Reject request'))"></h2>
                                        <p class='mt-1 text-sm text-muted-foreground'>{{ __('Add a comment for your decision.') }}</p>
                                        <form method='POST' x-bind:action="decision === 'approve' ? @js($approveUrl) : @js($rejectUrl)" class='mt-4 flex flex-col gap-3'>
                                            @csrf
                                            <x-ui.label for='decision-comment'>{{ __('Comment') }}</x-ui.label>
                                            <x-ui.textarea
                                                id='decision-comment'
                                                name='comment'
                                                rows='3'
                                                placeholder="{{ __('Add a comment') }}"
                                                x-bind:required="decision === 'reject'"
                                                x-init="$nextTick(() => $el.focus())"
                                            ></x-ui.textarea>
                                            <div class='flex justify-end gap-3'>
                                                <x-ui.button type='button' variant='outline' x-on:click="decision = null">{{ __('Cancel') }}</x-ui.button>
                                                <template x-if="decision === 'approve'">
                                                    <x-ui.button type='submit'>{{ __('Submit') }}</x-ui.button>
                                                </template>
                                                <template x-if="decision === 'reject'">
                                                    <x-ui.button type='submit' variant='destructive'>{{ __('Submit') }}</x-ui.button>
                                                </template>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </template>
                        </x-ui.card>
                    @endif
                @endcan

                @can('approvals.delete')
                    @if ($canCancel)
                        <x-ui.card>
                            <x-ui.card-header>
                                <x-ui.card-title>{{ __('Cancel request') }}</x-ui.card-title>
                            </x-ui.card-header>
                            <x-ui.card-content>
                                <form method='POST' action='{{ $cancelUrl }}' class='flex flex-col gap-3'>
                                    @csrf
                                    <x-ui.label for='cancel-comment'>{{ __('Comment') }}</x-ui.label>
                                    <x-ui.textarea id='cancel-comment' name='comment' rows='3' placeholder='Optional comment'></x-ui.textarea>
                                    <x-ui.button type='submit' variant='outline'>{{ __('Cancel request') }}</x-ui.button>
                                </form>
                            </x-ui.card-content>
                        </x-ui.card>
                    @endif
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
