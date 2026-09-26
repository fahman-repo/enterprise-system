<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalRequest;
use App\Models\DevelopmentProgram;
use App\Models\Employee;
use App\Services\ApprovalWorkflowService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DevelopmentProgramsController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of development programs.
     */
    public function index(Request $request): View
    {
        $query = DevelopmentProgram::query()->withCount('enrollments');

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'development_programs.name',
            'development_programs.code',
            'development_programs.organizer',
            'development_programs.location',
        ]);

        $this->applyTableFilters($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'code' => 'development_programs.code',
            'name' => 'development_programs.name',
            'type' => 'development_programs.type',
            'start_date' => 'development_programs.start_date',
            'status' => 'development_programs.status',
        ], $request->query('sort'), $request->query('direction'), 'start_date');

        $programs = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('development-programs.index', [
            'programs' => $programs,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Show the form for creating a development program.
     */
    public function create(): View
    {
        return view('development-programs.create');
    }

    /**
     * Store a newly created development program.
     */
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('development-programs')) {
            if (blank($data['code'] ?? null)) {
                unset($data['code']);
            }

            $workflow->submit($request->user(), 'development-programs', ApprovalRequest::ACTION_CREATE, null, $data);

            return redirect()->route('development-programs.index')
                ->with('status', __('Program change request submitted for approval.'));
        }

        DB::transaction(function () use (&$data): void {
            if (blank($data['code'] ?? null)) {
                $data['code'] = DevelopmentProgram::generateCode();
            }

            DevelopmentProgram::create($data);
        });

        return redirect()->route('development-programs.index')->with('status', __('Development program created.'));
    }

    /**
     * Display the program details with its roster.
     */
    public function show(DevelopmentProgram $developmentProgram): View
    {
        $developmentProgram->load(['enrollments.employee']);

        $statusCounts = $developmentProgram->enrollments
            ->countBy('status')
            ->all();

        $activeEmployees = Employee::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'employee_number']);

        return view('development-programs.show', [
            'program' => $developmentProgram,
            'statusCounts' => $statusCounts,
            'activeEmployees' => $activeEmployees,
        ]);
    }

    /**
     * Show the form for editing a development program.
     */
    public function edit(DevelopmentProgram $developmentProgram): View
    {
        return view('development-programs.edit', ['program' => $developmentProgram]);
    }

    /**
     * Update the specified development program.
     */
    public function update(Request $request, DevelopmentProgram $developmentProgram, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $developmentProgram);

        if ($workflow->isApprovalActive('development-programs')) {
            if (blank($data['code'] ?? null)) {
                $data['code'] = $developmentProgram->code;
            }

            $workflow->submit($request->user(), 'development-programs', ApprovalRequest::ACTION_UPDATE, $developmentProgram->id, $data);

            return redirect()->route('development-programs.index')
                ->with('status', __('Program change request submitted for approval.'));
        }

        $developmentProgram->update($data);

        return redirect()->route('development-programs.index')->with('status', __('Development program updated.'));
    }

    /**
     * Remove the program; programs with participants cannot be deleted.
     */
    public function destroy(DevelopmentProgram $developmentProgram, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($developmentProgram->enrollments()->exists()) {
            return back()->withErrors(['development_program' => __('This program has participants and cannot be deleted.')]);
        }

        if ($workflow->isApprovalActive('development-programs')) {
            $workflow->submit(request()->user(), 'development-programs', ApprovalRequest::ACTION_DELETE, $developmentProgram->id, []);

            return redirect()->route('development-programs.index')
                ->with('status', __('Program change request submitted for approval.'));
        }

        $developmentProgram->delete();

        return redirect()->route('development-programs.index')->with('status', __('Development program deleted.'));
    }

    /**
     * Apply the type and lifecycle-status filters from the query string.
     *
     * Only the first matching parameter is applied so stale URLs cannot
     * combine filters.
     */
    protected function applyTableFilters(Builder $query, Request $request): void
    {
        if (in_array($request->query('type'), DevelopmentProgram::TYPES, true)) {
            $query->where('development_programs.type', $request->query('type'));

            return;
        }

        if (in_array($request->query('status'), DevelopmentProgram::STATUSES, true)) {
            $query->where('development_programs.status', $request->query('status'));
        }
    }

    /**
     * Validate and normalize the program payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?DevelopmentProgram $program = null): array
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:255', Rule::unique('development_programs', 'code')->ignore($program?->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(DevelopmentProgram::TYPES)],
            'description' => ['nullable', 'string'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(DevelopmentProgram::STATUSES)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
