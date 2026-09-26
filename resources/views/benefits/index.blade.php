<x-app-layout title='Benefits'>
    <div class='mx-auto flex max-w-6xl flex-col gap-6'>
        @php
            $benefitCreateUrl = route('benefits.create');
            $activeBenefitTab = request()->query('tab') === 'requests' ? 'requests' : 'benefits';
        @endphp
        <div class='flex flex-wrap items-end justify-between gap-4'>
            <div class='flex flex-col gap-1'>
                <h1 class='text-xl font-semibold tracking-tight'>{{ __('Benefits') }}</h1>
                <p class='text-sm text-muted-foreground'>{{ __('Manage benefit packages through the organization-wide approval workflow.') }}</p>
            </div>
            @can('benefits.create')
                <x-ui.button href='{{ $benefitCreateUrl }}'>{{ __('New benefit request') }}</x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @if (! $approvalReady)
            <x-ui.alert variant='destructive'>
                <x-slot:title>{{ __('Approval matrix required') }}</x-slot:title>
                {{ __('Benefit changes apply immediately until an administrator configures and activates the Benefits approval matrix.') }}
            </x-ui.alert>
        @endif

        <x-ui.tabs default='{{ $activeBenefitTab }}'>
            <x-ui.tabs-list>
                <x-ui.tabs-trigger value='benefits'>{{ __('Benefits') }}</x-ui.tabs-trigger>
                <x-ui.tabs-trigger value='requests'>{{ __('Change Requests') }}</x-ui.tabs-trigger>
            </x-ui.tabs-list>
        <x-ui.tabs-content value='benefits'>
            @php
                $columns = [
                    ['key' => 'code', 'label' => __('Code'), 'sortable' => true],
                    ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                    ['key' => 'type', 'label' => __('Type'), 'sortable' => true],
                    ['key' => 'period', 'label' => __('Period'), 'sortable' => true],
                    ['key' => 'limit_amount', 'label' => __('Limit'), 'sortable' => true, 'align' => 'right'],
                    ['key' => 'enrollments', 'label' => __('Enrollments'), 'sortable' => true, 'align' => 'right'],
                    ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                    ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
                ];
                $benefitFilters = [
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

            <x-ui.data-table :columns='$columns' :paginator='$benefits' :sort='$sort' :direction='$direction' empty='No benefits found.' :exclude-params='array_keys($benefitFilters)'>
                <x-slot:filters>
                    <x-ui.filter-bar :filters='$benefitFilters' />
                </x-slot:filters>
                    @foreach ($benefits as $benefit)
                        @php
                            $benefitEditUrl = route('benefits.edit', $benefit);
                            $benefitDeleteUrl = route('benefits.destroy', $benefit);
                        @endphp
                        <tr class='hover:bg-accent/50'>
                        <td class='px-4 py-3 font-mono text-xs text-muted-foreground'>{{ $benefit->code }}</td>
                        <td class='px-4 py-3 font-medium'>{{ $benefit->name }}</td>
                        <td class='px-4 py-3'><x-ui.badge variant='secondary'>{{ $benefit->typeLabel() }}</x-ui.badge></td>
                        <td class='px-4 py-3'><x-ui.badge variant='outline'>{{ $benefit->periodLabel() }}</x-ui.badge></td>
                        <td class='px-4 py-3 text-right text-muted-foreground'>{{ number_format((float) $benefit->limit_amount, 2) }}</td>
                        <td class='px-4 py-3 text-right text-muted-foreground'>{{ number_format($benefit->enrollments_count) }}</td>
                        <td class='px-4 py-3'>
                            @if ($benefit->is_active)
                                <x-ui.badge variant='success'>{{ __('Active') }}</x-ui.badge>
                            @else
                                <x-ui.badge variant='muted'>{{ __('Inactive') }}</x-ui.badge>
                            @endif
                        </td>
                        <td class='px-4 py-3'>
                            <div class='flex justify-end gap-2'>
                                @can('benefits.update')
                                    <x-ui.button variant='outline' size='sm' href='{{ $benefitEditUrl }}'>{{ __('Edit') }}</x-ui.button>
                                @endcan
                                @can('benefits.delete')
                                    <form method='POST' action='{{ $benefitDeleteUrl }}'>
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button variant='destructive' size='sm' type='submit'>{{ __('Request deletion') }}</x-ui.button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>
        </x-ui.tabs-content>
        <x-ui.tabs-content value='requests'>
            <x-ui.card>
                <x-ui.card-header>
                    <x-ui.card-title>{{ __('Benefit change requests') }}</x-ui.card-title>
                    <x-ui.card-description>{{ __('Requests remain here until every approval stage is complete.') }}</x-ui.card-description>
                </x-ui.card-header>
                <div class='overflow-x-auto border-t'>
                    <table class='w-full text-sm'>
                        <thead class='bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground'>
                            <tr>
                                <th class='px-4 py-3 font-medium'>{{ __('Request') }}</th>
                                <th class='px-4 py-3 font-medium'>{{ __('Action') }}</th>
                                <th class='px-4 py-3 font-medium'>{{ __('Maker') }}</th>
                                <th class='px-4 py-3 font-medium'>{{ __('Current stage') }}</th>
                                <th class='px-4 py-3 font-medium'>{{ __('Status') }}</th>
                                <th class='px-4 py-3 text-right font-medium'>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y'>
                            @forelse ($changeRequests as $changeRequest)
                                @php
                                    $changeRequestUrl = route('approvals.show', $changeRequest);
                                @endphp
                                <tr class='hover:bg-accent/40'>
                                    <td class='px-4 py-3 font-medium'>#{{ $changeRequest->id }}</td>
                                    <td class='px-4 py-3'>{{ $changeRequest->actionLabel() }}</td>
                                    <td class='px-4 py-3'>{{ $changeRequest->maker_snapshot['name'] ?? $changeRequest->maker?->name ?? '—' }}</td>
                                    <td class='px-4 py-3 text-muted-foreground'>{{ $changeRequest->currentStage()?->name ?? '—' }}</td>
                                    <td class='px-4 py-3'><x-ui.badge :variant='$changeRequest->statusVariant()'>{{ $changeRequest->status }}</x-ui.badge></td>
                                    <td class='px-4 py-3 text-right'>
                                        <x-ui.button variant='outline' size='sm' href='{{ $changeRequestUrl }}'>{{ __('View') }}</x-ui.button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan='6' class='px-4 py-8 text-center text-muted-foreground'>{{ __('No benefit change requests found.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($changeRequests->hasPages())
                    <div class='border-t p-4'>{{ $changeRequests->links() }}</div>
                @endif
            </x-ui.card>
        </x-ui.tabs-content>
        </x-ui.tabs>
    </div>
</x-app-layout>
