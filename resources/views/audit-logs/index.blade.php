<x-app-layout :title="__('Audit Logs')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-xl font-semibold tracking-tight">{{ __('Audit Logs') }}</h1>
            <p class="text-sm text-muted-foreground">{{ __('Every data change and authentication event, newest first.') }}</p>
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'created_at', 'label' => __('Time'), 'sortable' => true],
                ['key' => 'event', 'label' => __('Event'), 'sortable' => true],
                ['key' => 'causer', 'label' => __('Actor'), 'sortable' => true],
                ['key' => 'subject', 'label' => __('Record'), 'sortable' => true],
                ['key' => 'changes', 'label' => __('Changes'), 'sortable' => false],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];
        @endphp

        <x-ui.data-table :columns="$columns" :paginator="$activities" :sort="$sort" :direction="$direction" :empty="__('No audit entries found.')">
            <x-slot:filters>
                <x-ui.select
                    name="event"
                    class="w-40"
                    aria-label="{{ __('Event') }}"
                    x-data
                    @change="$el.form.requestSubmit()"
                >
                    <option value="">{{ __('All events') }}</option>
                    @foreach (\App\Http\Controllers\AuditLogController::EVENTS as $event)
                        <option value="{{ $event }}" @selected(request('event') === $event)>{{ str_replace('_', ' ', ucfirst($event)) }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select
                    name="causer"
                    class="w-44"
                    aria-label="{{ __('Actor') }}"
                    x-data
                    @change="$el.form.requestSubmit()"
                >
                    <option value="">{{ __('All actors') }}</option>
                    @foreach ($causers as $id => $name)
                        <option value="{{ $id }}" @selected((string) request('causer') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </x-ui.select>

                <label class="flex items-center gap-2 text-sm text-muted-foreground">
                    {{ __('From') }}
                    <x-ui.input type="date" name="from" class="w-auto" value="{{ request('from') }}" />
                </label>

                <label class="flex items-center gap-2 text-sm text-muted-foreground">
                    {{ __('To') }}
                    <x-ui.input type="date" name="to" class="w-auto" value="{{ request('to') }}" />
                </label>
            </x-slot:filters>

            @foreach ($activities as $activity)
                <tr class="hover:bg-accent/50">
                    <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">
                        {{ $activity->created_at?->format('Y-m-d H:i:s') }}
                    </td>
                    <td class="px-4 py-3">
                        <x-ui.badge :variant="$activity->variant()">{{ $activity->eventLabel() }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-3 font-medium">{{ $activity->causerName() }}</td>
                    <td class="px-4 py-3">
                        <span class="text-muted-foreground">{{ $activity->subjectTypeLabel() }}</span>
                        <span class="font-medium">{{ $activity->subjectLabel() }}</span>
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">
                        {{ $activity->changesSummary() }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end">
                            <x-ui.button variant="outline" size="sm" href="{{ route('audit-logs.show', $activity) }}">
                                {{ __('View') }}
                            </x-ui.button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>
    </div>
</x-app-layout>
