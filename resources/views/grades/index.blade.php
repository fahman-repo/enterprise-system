<x-app-layout title='Grades'>
    <div class='mx-auto flex max-w-6xl flex-col gap-6'>
        @php
            $gradeCreateUrl = route('grades.create');
            $activeGradeTab = request()->query('tab') === 'requests' ? 'requests' : 'grades';
        @endphp
        <div class='flex flex-wrap items-end justify-between gap-4'>
            <div class='flex flex-col gap-1'>
                <h1 class='text-xl font-semibold tracking-tight'>{{ __('Grades') }}</h1>
                <p class='text-sm text-muted-foreground'>{{ __('Manage job grades through the organization-wide approval workflow.') }}</p>
            </div>
            @can('grades.create')
                <x-ui.button href='{{ $gradeCreateUrl }}'>{{ __('New grade request') }}</x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @if (! $approvalReady)
            <x-ui.alert variant='destructive'>
                <x-slot:title>{{ __('Approval matrix required') }}</x-slot:title>
                {{ __('Grade changes apply immediately until an administrator configures and activates the Grades approval matrix.') }}
            </x-ui.alert>
        @endif

        <x-ui.tabs default='{{ $activeGradeTab }}'>
            <x-ui.tabs-list>
                <x-ui.tabs-trigger value='grades'>{{ __('Grades') }}</x-ui.tabs-trigger>
                <x-ui.tabs-trigger value='requests'>{{ __('Change Requests') }}</x-ui.tabs-trigger>
            </x-ui.tabs-list>
        <x-ui.tabs-content value='grades'>
            @php
                $columns = [
                    ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                    ['key' => 'level', 'label' => __('Level'), 'sortable' => true, 'align' => 'right'],
                    ['key' => 'employees', 'label' => __('Employees'), 'sortable' => true, 'align' => 'right'],
                    ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                    ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
                ];
                $gradeFilters = [
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

            <x-ui.data-table :columns='$columns' :paginator='$grades' :sort='$sort' :direction='$direction' empty='No grades found.' :exclude-params='array_keys($gradeFilters)'>
                <x-slot:filters>
                    <x-ui.filter-bar :filters='$gradeFilters' />
                </x-slot:filters>
                    @foreach ($grades as $grade)
                        @php
                            $gradeEditUrl = route('grades.edit', $grade);
                            $gradeDeleteUrl = route('grades.destroy', $grade);
                        @endphp
                        <tr class='hover:bg-accent/50'>
                        <td class='px-4 py-3 font-medium'>{{ $grade->name }}</td>
                        <td class='px-4 py-3 text-right text-muted-foreground'>{{ $grade->level }}</td>
                        <td class='px-4 py-3 text-right text-muted-foreground'>{{ number_format($grade->employees_count) }}</td>
                        <td class='px-4 py-3'>
                            @if ($grade->is_active)
                                <x-ui.badge variant='success'>{{ __('Active') }}</x-ui.badge>
                            @else
                                <x-ui.badge variant='muted'>{{ __('Inactive') }}</x-ui.badge>
                            @endif
                        </td>
                        <td class='px-4 py-3'>
                            <div class='flex justify-end gap-2'>
                                @can('grades.update')
                                    <x-ui.button variant='outline' size='sm' href='{{ $gradeEditUrl }}'>{{ __('Edit') }}</x-ui.button>
                                @endcan
                                @can('grades.delete')
                                    <form method='POST' action='{{ $gradeDeleteUrl }}'>
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
                    <x-ui.card-title>{{ __('Grade change requests') }}</x-ui.card-title>
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
                                <tr><td colspan='6' class='px-4 py-8 text-center text-muted-foreground'>{{ __('No grade change requests found.') }}</td></tr>
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
