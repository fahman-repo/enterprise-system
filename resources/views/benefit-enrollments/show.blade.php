<x-app-layout :title="__('Enrollment')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        @include('partials.flash')

        <x-ui.card>
            <x-ui.card-content class="pt-6">
                <div class="flex flex-wrap items-start justify-between gap-6">
                    <div class="flex flex-col gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl font-semibold tracking-tight">{{ $enrollment->benefit?->name ?? __('Enrollment') }}</h1>
                            <x-ui.badge :variant="$enrollment->statusVariant()">{{ ucfirst($enrollment->status) }}</x-ui.badge>
                            @if ($enrollment->isUsable())
                                <x-ui.badge variant="success">{{ __('Usable') }}</x-ui.badge>
                            @else
                                <x-ui.badge variant="muted">{{ __('Expired') }}</x-ui.badge>
                            @endif
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ $enrollment->employee?->name }} ({{ $enrollment->employee?->employee_number }})
                            · {{ $enrollment->effective_from?->format('d M Y') }}{{ $enrollment->effective_to ? ' – '.$enrollment->effective_to->format('d M Y') : '' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-ui.button variant="outline" href="{{ route('benefit-enrollments.index') }}">{{ __('Back') }}</x-ui.button>
                        @can('benefit-enrollments.update')
                            <x-ui.button href="{{ route('benefit-enrollments.edit', $enrollment) }}">{{ __('Edit') }}</x-ui.button>
                        @endcan
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>{{ __('Balance') }}</x-ui.card-title>
                <x-ui.card-description>{{ __('Current period consumption for this enrollment.') }}</x-ui.card-description>
            </x-ui.card-header>
            <x-ui.card-content>
                @php
                    $details = [
                        __('Benefit') => $enrollment->benefit?->name,
                        __('Limit') => $enrollment->benefit ? number_format((float) $enrollment->benefit->limit_amount, 2).' ('.$enrollment->benefit->periodLabel().')' : null,
                        __('Consumed') => number_format((float) $consumed, 2),
                        __('Remaining') => number_format((float) $remaining, 2),
                        __('Notes') => $enrollment->notes,
                    ];
                @endphp
                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($details as $label => $value)
                        <div class="flex flex-col gap-1">
                            <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ $label }}</dt>
                            <dd class="text-sm">{{ $value ?? __('—') }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>{{ __('Claims') }}</x-ui.card-title>
                <x-ui.card-description>{{ __('Claims filed against this enrollment.') }}</x-ui.card-description>
            </x-ui.card-header>
            <div class="overflow-x-auto border-t">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-right font-medium">{{ __('Amount') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($enrollment->claims as $claim)
                            <tr class="hover:bg-accent/40">
                                <td class="px-4 py-3">{{ $claim->claim_date?->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((float) $claim->amount, 2) }}</td>
                                <td class="px-4 py-3"><x-ui.badge :variant="$claim->statusVariant()">{{ ucfirst($claim->status) }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-right">
                                    <x-ui.button variant="outline" size="sm" href="{{ route('benefit-claims.show', $claim) }}">{{ __('View') }}</x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-muted-foreground">{{ __('No claims yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
