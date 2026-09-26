<x-app-layout :title="__('Claim')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        @include('partials.flash')

        <x-ui.card>
            <x-ui.card-content class="pt-6">
                <div class="flex flex-wrap items-start justify-between gap-6">
                    <div class="flex flex-col gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl font-semibold tracking-tight">{{ $claim->benefit?->name ?? __('Claim') }}</h1>
                            <x-ui.badge :variant="$claim->statusVariant()">{{ ucfirst($claim->status) }}</x-ui.badge>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ $claim->employee?->name }} ({{ $claim->employee?->employee_number }})
                            · {{ $claim->claim_date?->format('d M Y') }}
                            · {{ number_format((float) $claim->amount, 2) }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button variant="outline" href="{{ route('benefit-claims.index') }}">{{ __('Back') }}</x-ui.button>
                        @can('benefit-claims.update')
                            @if (in_array($claim->status, App\Models\BenefitClaim::RECOVERABLE, true))
                                <x-ui.button href="{{ route('benefit-claims.edit', $claim) }}">{{ __('Edit') }}</x-ui.button>
                            @endif
                        @endcan
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>{{ __('Claim details') }}</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                @php
                    $details = [
                        __('Benefit') => $claim->benefit?->name,
                        __('Enrollment') => $claim->enrollment ? '#'.$claim->enrollment->id : null,
                        __('Employee') => $claim->employee?->name,
                        __('Claim date') => $claim->claim_date?->format('d M Y'),
                        __('Amount') => number_format((float) $claim->amount, 2),
                        __('Consumed this period') => number_format((float) $consumed, 2),
                        __('Remaining this period') => number_format((float) $remaining, 2),
                        __('Decided at') => $claim->decided_at?->format('d M Y H:i'),
                        __('Paid at') => $claim->paid_at?->format('d M Y H:i'),
                        __('Resolution') => $claim->resolution_comment,
                        __('Description') => $claim->description,
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

                @if ($claim->receipt_path && Storage::disk('public')->exists($claim->receipt_path))
                    <div class="mt-4">
                        <a href="{{ Storage::disk('public')->url($claim->receipt_path) }}" class="text-sm text-primary hover:underline" target="_blank" rel="noopener">
                            {{ __('View receipt') }}
                        </a>
                    </div>
                @endif

                @if (! $approvalActive)
                    <div class="mt-6 flex flex-wrap gap-2">
                        @if ($claim->status === 'pending')
                            <form method="POST" action="{{ route('benefit-claims.approve', $claim) }}">
                                @csrf
                                <x-ui.button type="submit">{{ __('Approve') }}</x-ui.button>
                            </form>
                            <details>
                                <summary class="cursor-pointer text-sm font-medium hover:underline">{{ __('Reject') }}</summary>
                                <form method="POST" action="{{ route('benefit-claims.reject', $claim) }}" class="mt-2 flex flex-col gap-2">
                                    @csrf
                                    <x-ui.textarea name="comment" rows="2" placeholder="{{ __('Rejection reason') }}" required></x-ui.textarea>
                                    <x-ui.button variant="destructive" size="sm" type="submit">{{ __('Reject claim') }}</x-ui.button>
                                </form>
                            </details>
                            <form method="POST" action="{{ route('benefit-claims.cancel', $claim) }}">
                                @csrf
                                <x-ui.button variant="outline" type="submit">{{ __('Cancel') }}</x-ui.button>
                            </form>
                        @endif
                        @if ($claim->status === 'approved')
                            <form method="POST" action="{{ route('benefit-claims.paid', $claim) }}">
                                @csrf
                                <x-ui.button type="submit">{{ __('Mark as paid') }}</x-ui.button>
                            </form>
                        @endif
                    </div>
                @else
                    <p class="mt-6 text-sm text-muted-foreground">{{ __('Decisions happen on the Approvals page while the Benefit Claims matrix is active.') }}</p>
                @endif
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>{{ __('Approval history') }}</x-ui.card-title>
                <x-ui.card-description>{{ __('Maker-checker requests linked to this claim.') }}</x-ui.card-description>
            </x-ui.card-header>
            <div class="overflow-x-auto border-t">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('Request') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Action') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Maker') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($changeRequests as $changeRequest)
                            <tr class="hover:bg-accent/40">
                                <td class="px-4 py-3 font-medium">#{{ $changeRequest->id }}</td>
                                <td class="px-4 py-3">{{ $changeRequest->actionLabel() }}</td>
                                <td class="px-4 py-3">{{ $changeRequest->maker_snapshot['name'] ?? $changeRequest->maker?->name ?? '—' }}</td>
                                <td class="px-4 py-3"><x-ui.badge :variant="$changeRequest->statusVariant()">{{ $changeRequest->status }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-right">
                                    <x-ui.button variant="outline" size="sm" href="{{ route('approvals.show', $changeRequest) }}">{{ __('View') }}</x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-muted-foreground">{{ __('No approval history.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($changeRequests->hasPages())
                <div class="border-t p-4">{{ $changeRequests->links() }}</div>
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
