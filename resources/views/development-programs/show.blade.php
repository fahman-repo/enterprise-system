<x-app-layout :title="$program->name">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        @include('partials.flash')

        <x-ui.card>
            <x-ui.card-content class="pt-6">
                <div class="flex flex-wrap items-start justify-between gap-6">
                    <div class="flex flex-col gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl font-semibold tracking-tight">{{ $program->name }}</h1>

                            @if ($program->is_active)
                                <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                            @else
                                <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                            @endif

                            <x-ui.badge variant="outline">{{ $program->typeLabel() }}</x-ui.badge>
                            <x-ui.badge variant="outline">{{ $program->statusLabel() }}</x-ui.badge>
                        </div>

                        <p class="text-sm text-muted-foreground">
                            <span class="font-mono">{{ $program->code }}</span>
                            · {{ $program->start_date?->format('d M Y') }} – {{ $program->end_date?->format('d M Y') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-ui.button variant="outline" href="{{ route('development-programs.index') }}">
                            {{ __('Back') }}
                        </x-ui.button>

                        @can('development-programs.update')
                            <x-ui.button href="{{ route('development-programs.edit', $program) }}">
                                {{ __('Edit') }}
                            </x-ui.button>
                        @endcan
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>{{ __('Program details') }}</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                @php
                    $enrolled = $program->enrollments->whereNotIn('status', ['cancelled'])->count();
                    $details = [
                        __('Code') => $program->code,
                        __('Type') => $program->typeLabel(),
                        __('Status') => $program->statusLabel(),
                        __('Start date') => $program->start_date?->format('d M Y'),
                        __('End date') => $program->end_date?->format('d M Y'),
                        __('Organizer') => $program->organizer,
                        __('Location') => $program->location,
                        __('Capacity') => $program->capacity !== null ? __(':enrolled / :capacity', ['enrolled' => number_format($enrolled), 'capacity' => number_format($program->capacity)]) : __('Unlimited'),
                        __('Cost') => $program->cost !== null ? number_format((float) $program->cost, 2) : null,
                        __('Description') => $program->description,
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
                <x-ui.card-title>{{ __('Participants') }}</x-ui.card-title>
                <x-ui.card-description>{{ __('Enroll employees and record their outcomes directly.') }}</x-ui.card-description>
            </x-ui.card-header>
            <x-ui.card-content class="flex flex-col gap-6">
                @can('development-programs.update')
                    <form method="POST" action="{{ route('development-programs.enrollments.store', $program) }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div class="flex min-w-64 flex-1 flex-col gap-2">
                            <x-ui.label for="employee_id">{{ __('Employee') }}</x-ui.label>
                            <x-ui.select id="employee_id" name="employee_id" required>
                                <option value="">{{ __('Select an employee') }}</option>
                                @foreach ($activeEmployees as $employee)
                                    <option value="{{ $employee->id }}" @selected((string) old('employee_id') === (string) $employee->id)>
                                        {{ $employee->name }} ({{ $employee->employee_number }})
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </div>
                        <x-ui.button type="submit">{{ __('Enroll') }}</x-ui.button>
                    </form>

                    <x-ui.separator />
                @endcan

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <th class="px-4 py-3 font-medium">{{ __('Employee') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Score') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Completed') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Certificate') }}</th>
                                <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($program->enrollments as $enrollment)
                                <tr class="hover:bg-accent/50">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('employees.show', $enrollment->employee) }}" class="font-medium hover:underline">{{ $enrollment->employee?->name ?? __('—') }}</a>
                                        <span class="font-mono text-xs text-muted-foreground">{{ $enrollment->employee?->employee_number }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-ui.badge variant="outline">{{ $enrollment->status }}</x-ui.badge>
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ $enrollment->score ?? __('—') }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ $enrollment->completed_at?->format('d M Y') ?? __('—') }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ $enrollment->certificate_no ?? __('—') }}</td>
                                    <td class="px-4 py-3">
                                        @can('development-programs.update')
                                            <details class="flex justify-end">
                                                <summary class="cursor-pointer text-sm font-medium hover:underline">{{ __('Record result') }}</summary>
                                                <form method="POST" action="{{ route('development-programs.enrollments.update', [$program, $enrollment]) }}" class="mt-2 flex flex-col gap-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <x-ui.select name="status" required>
                                                        @foreach (App\Models\DevelopmentEnrollment::STATUSES as $status)
                                                            <option value="{{ $status }}" @selected(old('status', $enrollment->status) === $status)>{{ $status }}</option>
                                                        @endforeach
                                                    </x-ui.select>
                                                    <x-ui.input name="score" type="number" step="0.01" min="0" max="100" placeholder="{{ __('Score') }}" :value="old('score', $enrollment->score)" />
                                                    <x-ui.input name="completed_at" type="date" :value="old('completed_at', $enrollment->completed_at?->format('Y-m-d'))" />
                                                    <x-ui.input name="certificate_no" placeholder="{{ __('Certificate no.') }}" :value="old('certificate_no', $enrollment->certificate_no)" />
                                                    <x-ui.textarea name="notes" rows="2" placeholder="{{ __('Notes') }}">{{ old('notes', $enrollment->notes) }}</x-ui.textarea>
                                                    <div class="flex justify-end gap-2">
                                                        <x-ui.button size="sm" type="submit">{{ __('Save') }}</x-ui.button>
                                                    </div>
                                                </form>
                                                <form method="POST" action="{{ route('development-programs.enrollments.destroy', [$program, $enrollment]) }}"
                                                    onsubmit="return confirm('{{ __('Remove this enrollment?') }}')" class="mt-2 flex justify-end">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-ui.button variant="destructive" size="sm" type="submit">
                                                        {{ __('Unenroll') }}
                                                    </x-ui.button>
                                                </form>
                                            </details>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">{{ __('No participants yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card-content>
        </x-ui.card>
    </div>
</x-app-layout>
