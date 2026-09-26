<?php

namespace App\Http\Controllers;

use App\Models\DevelopmentEnrollment;
use App\Models\DevelopmentProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DevelopmentProgramEnrollmentsController extends Controller
{
    /**
     * Enroll an employee in the program directly (no approval workflow).
     */
    public function store(Request $request, DevelopmentProgram $developmentProgram): RedirectResponse
    {
        $data = $this->validated($request, $developmentProgram);

        $this->assertCapacity($developmentProgram);

        $developmentProgram->enrollments()->create($data);

        return redirect()->route('development-programs.show', $developmentProgram)
            ->with('status', __('Employee enrolled.'));
    }

    /**
     * Record the outcome of an enrollment directly (no approval workflow).
     */
    public function update(Request $request, DevelopmentProgram $developmentProgram, DevelopmentEnrollment $enrollment): RedirectResponse
    {
        if ($enrollment->development_program_id !== $developmentProgram->id) {
            abort(404);
        }

        $data = $this->validated($request, $developmentProgram, $enrollment);

        $enrollment->update($data);

        return redirect()->route('development-programs.show', $developmentProgram)
            ->with('status', __('Enrollment updated.'));
    }

    /**
     * Unenroll an employee from the program.
     */
    public function destroy(DevelopmentProgram $developmentProgram, DevelopmentEnrollment $enrollment): RedirectResponse
    {
        if ($enrollment->development_program_id !== $developmentProgram->id) {
            abort(404);
        }

        $enrollment->delete();

        return redirect()->route('development-programs.show', $developmentProgram)
            ->with('status', __('Enrollment removed.'));
    }

    /**
     * Validate the enrollment payload. employee_id is immutable after
     * creation, so updates validate only the recorded outcome fields.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, DevelopmentProgram $program, ?DevelopmentEnrollment $enrollment = null): array
    {
        if ($enrollment === null) {
            return $request->validate([
                'employee_id' => [
                    'required',
                    Rule::exists('employees', 'id')->whereNull('deleted_at'),
                    Rule::unique('development_enrollments', 'employee_id')
                        ->where(fn ($q) => $q->where('development_program_id', $program->id)),
                ],
                'status' => ['required', Rule::in(DevelopmentEnrollment::STATUSES)],
                'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'completed_at' => ['nullable', 'date', 'required_if:status,completed'],
                'certificate_no' => ['nullable', 'string', 'max:100'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ]);
        }

        return $request->validate([
            'status' => ['required', Rule::in(DevelopmentEnrollment::STATUSES)],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'completed_at' => ['nullable', 'date', 'required_if:status,completed'],
            'certificate_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * Reject new enrollments once every non-cancelled seat is taken.
     * Failed seats stay occupied so the seat is not silently reused.
     */
    protected function assertCapacity(DevelopmentProgram $program): void
    {
        if ($program->capacity === null) {
            return;
        }

        $occupied = $program->enrollments()->whereNotIn('status', ['cancelled'])->count();

        if ($occupied >= $program->capacity) {
            throw ValidationException::withMessages(['employee_id' => __('This program has reached its capacity.')]);
        }
    }
}
