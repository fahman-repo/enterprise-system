<x-app-layout title='Benefit Claims'>
    <div class='mx-auto flex max-w-6xl flex-col gap-6'>
        @php
            $claimCreateUrl = route('benefit-claims.create');
            $activeClaimTab = request()->query('tab') === 'requests' ? 'requests' : 'claims';
        @endphp
        <div class='flex flex-wrap items-end justify-between gap-4'>
            <div class='flex flex-col gap-1'>
                <h1 class='text-xl font-semibold tracking-tight'>{{ __('Benefit Claims') }}</h1>
                <p class='text-sm text-muted-foreground'>{{ __('Process monetary claims through the organization-wide approval workflow.') }}</p>
            </div>
            @can('benefit-claims.create')
                <x-ui.button href='{{ $claimCreateUrl }}'>{{ __('New claim request') }}</x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @if (! $approvalReady)
            <x-ui.alert variant='destructive'>
                <x-slot:title>{{ __('Approval matrix required') }}</x-slot:title>
                {{ __('Claim changes apply immediately until an administrator configures and activates the Benefit Claims approval matrix.') }}
            </x-ui.alert>
        @endif

        <x-ui.tabs default='{{ $activeClaimTab }}'>
            <x-ui.tabs-list>
                <x-ui.tabs-trigger value='claims'>{{ __('Claims') }}</x-ui.tabs-trigger>
                <x-ui.tabs-trigger value='requests'>{{ __('Change Requests') }}</x-ui.tabs-trigger>
            </x-ui.tabs-list>
        <x-ui.tabs-content value='claims'>
            @php
                $columns = [
                    ['key' => 'claim_date', 'label' => __('Date'), 'sortable' => true],
                    ['key' => 'benefit', 'label' => __('Benefit'), 'sortable' => true],
                    ['key' => 'employee', 'label' => __('Employee'), 'sortable' => true],
                    ['key' => 'amount', 'label' => __('Amount'), 'sortable' => true, 'align' => 'right'],
                    ['key' => 'status', 'label' => __('Status'), 'sortable' => true],
                    ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
                ];
                $claimFilters = [
                    'status' => [
                        'label' => __('Status'),
                        'all' => __('All statuses'),
                        'options' => collect(App\Models\BenefitClaim::STATUSES)->map(fn ($status) => ['value' => $status, 'label' => ucfirst($status)])->all(),
                    ],
                    'benefit_id' => [
                        'label' => __('Benefit'),
                        'all' => __('All benefits'),
                        'options' => $benefits->map(fn ($benefit) => ['value' => (string) $benefit->id, 'label' => $benefit->name])->values()->all(),
                    ],
                ];
            @endphp

            <x-ui.data-table :columns='$columns' :paginator='$claims' :sort='$sort' :direction='$direction' empty='No claims found.' :exclude-params='array_keys($claimFilters)'>
                <x-slot:filters>
                    <x-ui.filter-bar :filters='$claimFilters' />
                </x-slot:filters>
                    @foreach ($claims as $claim)
                        @php
                            $claimShowUrl = route('benefit-claims.show', $claim);
                            $claimEditUrl = route('benefit-claims.edit', $claim);
                            $claimDeleteUrl = route('benefit-claims.destroy', $claim);
                        @endphp
                        <tr class='hover:bg-accent/50'>
                        <td class='px-4 py-3 text-muted-foreground'>{{ $claim->claim_date?->format('d M Y') }}</td>
                        <td class='px-4 py-3 font-medium'>{{ $claim->benefit?->name ?? __('—') }}</td>
                        <td class='px-4 py-3 text-muted-foreground'>{{ $claim->employee?->name ?? __('—') }}</td>
                        <td class='px-4 py-3 text-right text-muted-foreground'>{{ number_format((float) $claim->amount, 2) }}</td>
                        <td class='px-4 py-3'><x-ui.badge :variant='$claim->statusVariant()'>{{ ucfirst($claim->status) }}</x-ui.badge></td>
                        <td class='px-4 py-3'>
                            <div class='flex justify-end gap-2'>
                                <x-ui.button variant='outline' size='sm' href='{{ $claimShowUrl }}'>{{ __('View') }}</x-ui.button>
                                @can('benefit-claims.update')
                                    <x-ui.button variant='outline' size='sm' href='{{ $claimEditUrl }}'>{{ __('Edit') }}</x-ui.button>
                                @endcan
                                @can('benefit-claims.delete')
                                    <form method='POST' action='{{ $claimDeleteUrl }}'>
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
                    <x-ui.card-title>{{ __('Claim change requests') }}</x-ui.card-title>
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
                                <tr><td colspan='6' class='px-4 py-8 text-center text-muted-foreground'>{{ __('No claim change requests found.') }}</td></tr>
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
