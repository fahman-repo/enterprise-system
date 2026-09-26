<x-app-layout title='Benefit Enrollments'>
    <div class='mx-auto flex max-w-6xl flex-col gap-6'>
        @php
            $enrollmentCreateUrl = route('benefit-enrollments.create');
            $activeEnrollmentTab = request()->query('tab') === 'requests' ? 'requests' : 'enrollments';
        @endphp
        <div class='flex flex-wrap items-end justify-between gap-4'>
            <div class='flex flex-col gap-1'>
                <h1 class='text-xl font-semibold tracking-tight'>{{ __('Benefit Enrollments') }}</h1>
                <p class='text-sm text-muted-foreground'>{{ __('Enroll eligible employees through the organization-wide approval workflow.') }}</p>
            </div>
            @can('benefit-enrollments.create')
                <x-ui.button href='{{ $enrollmentCreateUrl }}'>{{ __('New enrollment request') }}</x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @if (! $approvalReady)
            <x-ui.alert variant='destructive'>
                <x-slot:title>{{ __('Approval matrix required') }}</x-slot:title>
                {{ __('Enrollment changes apply immediately until an administrator configures and activates the Benefit Enrollments approval matrix.') }}
            </x-ui.alert>
        @endif

        <x-ui.tabs default='{{ $activeEnrollmentTab }}'>
            <x-ui.tabs-list>
                <x-ui.tabs-trigger value='enrollments'>{{ __('Enrollments') }}</x-ui.tabs-trigger>
                <x-ui.tabs-trigger value='requests'>{{ __('Change Requests') }}</x-ui.tabs-trigger>
            </x-ui.tabs-list>
        <x-ui.tabs-content value='enrollments'>
            @php
                $columns = [
                    ['key' => 'benefit', 'label' => __('Benefit'), 'sortable' => true],
                    ['key' => 'employee', 'label' => __('Employee'), 'sortable' => true],
                    ['key' => 'status', 'label' => __('Status'), 'sortable' => true],
                    ['key' => 'effective_from', 'label' => __('Effective'), 'sortable' => true],
                    ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
                ];
                $enrollmentFilters = [
                    'benefit_id' => [
                        'label' => __('Benefit'),
                        'all' => __('All benefits'),
                        'options' => $benefits->map(fn ($benefit) => ['value' => (string) $benefit->id, 'label' => $benefit->name])->values()->all(),
                    ],
                    'status' => [
                        'label' => __('Status'),
                        'all' => __('All statuses'),
                        'options' => collect(App\Models\BenefitEnrollment::STATUSES)->map(fn ($status) => ['value' => $status, 'label' => ucfirst($status)])->all(),
                    ],
                ];
            @endphp

            <x-ui.data-table :columns='$columns' :paginator='$enrollments' :sort='$sort' :direction='$direction' empty='No enrollments found.' :exclude-params='array_keys($enrollmentFilters)'>
                <x-slot:filters>
                    <x-ui.filter-bar :filters='$enrollmentFilters' />
                </x-slot:filters>
                    @foreach ($enrollments as $enrollment)
                        @php
                            $enrollmentShowUrl = route('benefit-enrollments.show', $enrollment);
                            $enrollmentEditUrl = route('benefit-enrollments.edit', $enrollment);
                            $enrollmentDeleteUrl = route('benefit-enrollments.destroy', $enrollment);
                        @endphp
                        <tr class='hover:bg-accent/50'>
                        <td class='px-4 py-3 font-medium'>{{ $enrollment->benefit?->name ?? __('—') }}</td>
                        <td class='px-4 py-3 text-muted-foreground'>{{ $enrollment->employee?->name ?? __('—') }}</td>
                        <td class='px-4 py-3'><x-ui.badge :variant='$enrollment->statusVariant()'>{{ ucfirst($enrollment->status) }}</x-ui.badge></td>
                        <td class='px-4 py-3 text-muted-foreground'>{{ $enrollment->effective_from?->format('d M Y') }}{{ $enrollment->effective_to ? ' – '.$enrollment->effective_to->format('d M Y') : '' }}</td>
                        <td class='px-4 py-3'>
                            <div class='flex justify-end gap-2'>
                                <x-ui.button variant='outline' size='sm' href='{{ $enrollmentShowUrl }}'>{{ __('View') }}</x-ui.button>
                                @can('benefit-enrollments.update')
                                    <x-ui.button variant='outline' size='sm' href='{{ $enrollmentEditUrl }}'>{{ __('Edit') }}</x-ui.button>
                                @endcan
                                @can('benefit-enrollments.delete')
                                    <form method='POST' action='{{ $enrollmentDeleteUrl }}'>
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
                    <x-ui.card-title>{{ __('Enrollment change requests') }}</x-ui.card-title>
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
                                <tr><td colspan='6' class='px-4 py-8 text-center text-muted-foreground'>{{ __('No enrollment change requests found.') }}</td></tr>
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
