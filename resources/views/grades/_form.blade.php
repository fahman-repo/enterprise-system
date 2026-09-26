@php
    $gradeActionUrl = $grade ? route('grades.update', $grade) : route('grades.store');
    $gradesIndexUrl = route('grades.index');
    $oldName = old('name', $grade?->name);
    $oldLevel = old('level', $grade?->level ?? 1);
    $gradeIsActive = (bool) old('is_active', $grade?->is_active ?? true);
@endphp
<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $grade ? __('Edit grade request') : __('New grade request') }}</x-ui.card-title>
        <x-ui.card-description>{{ __('Grades describe seniority. This form submits a request; the grade is only created or updated after final approval.') }}</x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form method='POST' action='{{ $gradeActionUrl }}' class='flex flex-col gap-4'>
            @csrf
            @if ($grade)
                @method('PUT')
            @endif

            <div class='grid gap-4 sm:grid-cols-2'>
                <div class='flex flex-col gap-2'>
                    <x-ui.label for='name'>{{ __('Name') }}</x-ui.label>
                    <x-ui.input id='name' name='name' :value='$oldName' required autofocus />
                </div>

                <div class='flex flex-col gap-2'>
                    <x-ui.label for='level'>{{ __('Level') }}</x-ui.label>
                    <x-ui.input id='level' name='level' type='number' min='0' :value='$oldLevel' required />
                </div>
            </div>

            <div class='flex flex-col gap-2'>
                <x-ui.label for='description'>{{ __('Description') }}</x-ui.label>
                <x-ui.textarea id='description' name='description' rows='3'>{{ old('description', $grade?->description) }}</x-ui.textarea>
            </div>

            <label class='flex items-center gap-2 text-sm'>
                <input type='hidden' name='is_active' value='0'>
                <x-ui.checkbox name='is_active' value='1' :checked='$gradeIsActive' />
                {{ __('Active') }}
            </label>

            <div class='flex items-center justify-end gap-2 pt-2'>
                <x-ui.button variant='outline' href='{{ $gradesIndexUrl }}' type='button'>{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type='submit'>{{ __('Submit for approval') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
