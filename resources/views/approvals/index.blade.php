<x-app-layout title='Approvals'>
    <div class='mx-auto flex max-w-7xl flex-col gap-6'>
        <div class='flex flex-col gap-1'>
            <h1 class='text-xl font-semibold tracking-tight'>{{ __('Approvals') }}</h1>
            <p class='text-sm text-muted-foreground'>{{ __('Review, approve, reject, or cancel organization-wide change requests.') }}</p>
        </div>

        @include('partials.flash')

        @php
            $requestFilters = [
                'status' => [
                    'label' => __('Status'),
                    'all' => __('All statuses'),
                    'options' => [
                        ['value' => 'Pending', 'label' => __('Pending')],
                        ['value' => 'Approved', 'label' => __('Approved')],
                        ['value' => 'Rejected', 'label' => __('Rejected')],
                        ['value' => 'Cancelled', 'label' => __('Cancelled')],
                    ],
                ],
                'module' => [
                    'label' => __('Module'),
                    'all' => __('All modules'),
                    'options' => collect($modules)->map(fn ($module, $key) => ['value' => $key, 'label' => $module->label()])->all(),
                ],
                'action' => [
                    'label' => __('Action'),
                    'all' => __('All actions'),
                    'options' => [
                        ['value' => 'create', 'label' => __('Create')],
                        ['value' => 'update', 'label' => __('Update')],
                        ['value' => 'delete', 'label' => __('Delete')],
                    ],
                ],
                'scope' => [
                    'label' => __('View'),
                    'all' => __('All requests'),
                    'options' => [
                        ['value' => 'mine', 'label' => __('My requests')],
                        ['value' => 'awaiting', 'label' => __('Awaiting me')],
                    ],
                ],
            ];
            $columns = [
                ['key' => 'request', 'label' => __('Request')],
                ['key' => 'module', 'label' => __('Module')],
                ['key' => 'action', 'label' => __('Action')],
                ['key' => 'maker', 'label' => __('Maker')],
                ['key' => 'stage', 'label' => __('Current stage')],
                ['key' => 'status', 'label' => __('Status')],
                ['key' => 'submitted', 'label' => __('Submitted')],
                ['key' => 'actions', 'label' => __('Actions')],
            ];
        @endphp

        <x-ui.data-table :columns='$columns' :paginator='$requests' empty='No approval requests found.' :exclude-params='array_keys($requestFilters)' search-placeholder='Search requests...'>
            <x-slot:filters>
                <x-ui.filter-bar :filters='$requestFilters' />
            </x-slot:filters>
            @foreach ($requests as $approvalRequest)
                @php
                    $showUrl = route('approvals.show', $approvalRequest);
                @endphp
                <tr class='hover:bg-accent/40'>
                    <td class='px-4 py-3 font-medium'>#{{ $approvalRequest->id }}</td>
                    <td class='px-4 py-3'>{{ $modules[$approvalRequest->module_key]->label() }}</td>
                    <td class='px-4 py-3'>{{ $approvalRequest->actionLabel() }}</td>
                    <td class='px-4 py-3'>{{ $approvalRequest->maker_snapshot['name'] ?? $approvalRequest->maker?->name ?? '—' }}</td>
                    <td class='px-4 py-3 text-muted-foreground'>{{ $approvalRequest->currentStage()?->name ?? '—' }}</td>
                    <td class='px-4 py-3'><x-ui.badge :variant='$approvalRequest->statusVariant()'>{{ $approvalRequest->status }}</x-ui.badge></td>
                    <td class='px-4 py-3 text-muted-foreground'>{{ $approvalRequest->submitted_at?->format('d M Y H:i') }}</td>
                    <td class='px-4 py-3 text-right'>
                        <x-ui.button variant='outline' size='sm' href='{{ $showUrl }}'>{{ __('View') }}</x-ui.button>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>
    </div>
</x-app-layout>
