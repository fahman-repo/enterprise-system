<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\Department;
use App\Models\Division;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentsController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of departments.
     */
    public function index(Request $request): View
    {
        $query = Department::query()
            ->select('departments.*')
            ->leftJoin('divisions', 'divisions.id', '=', 'departments.division_id')
            ->with('division')
            ->withCount(['orgUnits', 'employees']);

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'departments.code',
            'departments.name',
            'departments.description',
            'divisions.name',
        ]);

        $this->applyTableFilters($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'code' => 'departments.code',
            'name' => 'departments.name',
            'division' => 'divisions.name',
            'org_units' => 'org_units_count',
            'employees' => 'employees_count',
            'is_active' => 'departments.is_active',
        ], $request->query('sort'), $request->query('direction'), 'name');

        $departments = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('departments.index', [
            'departments' => $departments,
            'sort' => $sort,
            'direction' => $direction,
            'divisions' => Division::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a department.
     */
    public function create(): View
    {
        return view('departments.create', $this->formOptions());
    }

    /**
     * Store a newly created department.
     */
    public function store(Request $request): RedirectResponse
    {
        Department::create($this->validated($request));

        return redirect()->route('departments.index')->with('status', __('Department created.'));
    }

    /**
     * Show the form for editing a department.
     */
    public function edit(Department $department): View
    {
        return view('departments.edit', [
            'department' => $department,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified department.
     */
    public function update(Request $request, Department $department): RedirectResponse
    {
        $department->update($this->validated($request, $department));

        return redirect()->route('departments.index')->with('status', __('Department updated.'));
    }

    /**
     * Remove the department; departments with org units or employees cannot be deleted.
     */
    public function destroy(Department $department): RedirectResponse
    {
        if ($department->orgUnits()->exists() || $department->employees()->exists()) {
            return back()->withErrors(['department' => __('This department has org units or employees and cannot be deleted.')]);
        }

        $department->delete();

        return redirect()->route('departments.index')->with('status', __('Department deleted.'));
    }

    /**
     * Select options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'divisions' => Division::query()->orderBy('name')->get(),
        ];
    }

    /**
     * Apply the division and status filters from the query string.
     *
     * The list UI allows a single active filter; only the first matching
     * parameter is applied so stale URLs cannot combine filters.
     */
    protected function applyTableFilters(Builder $query, Request $request): void
    {
        if ($divisionId = $request->query('division_id')) {
            $query->where('departments.division_id', $divisionId);

            return;
        }

        $this->applyTableStatusFilter($query, $request, 'departments.is_active');
    }

    /**
     * Validate and normalize the department payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Department $department = null): array
    {
        $data = $request->validate([
            'division_id' => ['required', Rule::exists('divisions', 'id')],
            'code' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('departments', 'code')->ignore($department?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
