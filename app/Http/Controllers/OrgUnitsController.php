<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalRequest;
use App\Models\Department;
use App\Models\OrgUnit;
use App\Services\ApprovalWorkflowService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrgUnitsController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of organizational units.
     */
    public function index(Request $request): View
    {
        $query = OrgUnit::query()
            ->select('org_units.*')
            ->leftJoin('departments', 'departments.id', '=', 'org_units.department_id')
            ->with('department.division')
            ->withCount('employees');

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'org_units.code',
            'org_units.name',
            'org_units.description',
            'departments.name',
        ]);

        $this->applyTableFilters($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'code' => 'org_units.code',
            'name' => 'org_units.name',
            'department' => 'departments.name',
            'employees' => 'employees_count',
            'is_active' => 'org_units.is_active',
        ], $request->query('sort'), $request->query('direction'), 'name');

        $orgUnits = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('org-units.index', [
            'orgUnits' => $orgUnits,
            'sort' => $sort,
            'direction' => $direction,
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating an org unit.
     */
    public function create(): View
    {
        return view('org-units.create', $this->formOptions());
    }

    /**
     * Store a newly created org unit.
     */
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('org-units')) {
            $workflow->submit($request->user(), 'org-units', ApprovalRequest::ACTION_CREATE, null, $data);

            return redirect()->route('org-units.index')
                ->with('status', __('Org Unit change request submitted for approval.'));
        }

        OrgUnit::create($data);

        return redirect()->route('org-units.index')->with('status', __('Org unit created.'));
    }

    /**
     * Show the form for editing an org unit.
     */
    public function edit(OrgUnit $orgUnit): View
    {
        return view('org-units.edit', [
            'orgUnit' => $orgUnit,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified org unit.
     */
    public function update(Request $request, OrgUnit $orgUnit, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $orgUnit);

        if ($workflow->isApprovalActive('org-units')) {
            $workflow->submit($request->user(), 'org-units', ApprovalRequest::ACTION_UPDATE, $orgUnit->id, $data);

            return redirect()->route('org-units.index')
                ->with('status', __('Org Unit change request submitted for approval.'));
        }

        $orgUnit->update($data);

        return redirect()->route('org-units.index')->with('status', __('Org unit updated.'));
    }

    /**
     * Remove the org unit; org units with employees cannot be deleted.
     */
    public function destroy(OrgUnit $orgUnit, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($orgUnit->employees()->exists()) {
            return back()->withErrors(['org_unit' => __('This org unit has employees and cannot be deleted.')]);
        }

        if ($workflow->isApprovalActive('org-units')) {
            $workflow->submit(request()->user(), 'org-units', ApprovalRequest::ACTION_DELETE, $orgUnit->id, []);

            return redirect()->route('org-units.index')
                ->with('status', __('Org Unit change request submitted for approval.'));
        }

        $orgUnit->delete();

        return redirect()->route('org-units.index')->with('status', __('Org unit deleted.'));
    }

    /**
     * Select options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'departments' => Department::query()->with('division')->orderBy('name')->get(),
        ];
    }

    /**
     * Apply the department and status filters from the query string.
     *
     * The list UI allows a single active filter; only the first matching
     * parameter is applied so stale URLs cannot combine filters.
     */
    protected function applyTableFilters(Builder $query, Request $request): void
    {
        if ($departmentId = $request->query('department_id')) {
            $query->where('org_units.department_id', $departmentId);

            return;
        }

        $this->applyTableStatusFilter($query, $request, 'org_units.is_active');
    }

    /**
     * Validate and normalize the org unit payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?OrgUnit $orgUnit = null): array
    {
        $data = $request->validate([
            'department_id' => ['required', Rule::exists('departments', 'id')],
            'code' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('org_units', 'code')->ignore($orgUnit?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
