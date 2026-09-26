<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalRequest;
use App\Models\Department;
use App\Models\Division;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Grade;
use App\Models\MaritalStatus;
use App\Models\OrgUnit;
use App\Models\Position;
use App\Models\Religion;
use App\Models\User;
use App\Models\WorkLocation;
use App\Services\ApprovalWorkflowService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeesController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of employees.
     */
    public function index(Request $request): View
    {
        $query = Employee::query()
            ->select('employees.*')
            ->leftJoin('divisions', 'divisions.id', '=', 'employees.division_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
            ->leftJoin('employment_statuses', 'employment_statuses.id', '=', 'employees.employment_status_id')
            ->with(['division', 'department', 'orgUnit', 'position', 'employmentStatus']);

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'employees.employee_number',
            'employees.name',
            'employees.email',
            'employees.phone',
            'employees.identity_number',
            'departments.name',
            'positions.name',
        ]);

        $this->applyTableFilters($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'employee_number' => 'employees.employee_number',
            'name' => 'employees.name',
            'department' => 'departments.name',
            'position' => 'positions.name',
            'employment_status' => 'employment_statuses.name',
            'join_date' => 'employees.join_date',
            'is_active' => 'employees.is_active',
        ], $request->query('sort'), $request->query('direction'), 'name');

        $employees = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'summary' => $this->summary(),
            'sort' => $sort,
            'direction' => $direction,
            'divisions' => Division::query()->orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'orgUnits' => OrgUnit::query()->orderBy('name')->get(),
            'positions' => Position::query()->orderBy('name')->get(),
            'employmentStatuses' => EmploymentStatus::query()->orderBy('sort_order')->get(),
            'workLocations' => WorkLocation::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Display the employee profile.
     */
    public function show(Employee $employee): View
    {
        $employee->load([
            'user',
            'manager:id,name',
            'religion',
            'maritalStatus',
            'educationLevel',
            'division',
            'department',
            'orgUnit',
            'position',
            'grade',
            'workLocation',
            'employmentStatus',
        ]);

        $employee->loadCount('reports');

        return view('employees.show', ['employee' => $employee]);
    }

    /**
     * Show the form for creating an employee.
     */
    public function create(): View
    {
        return view('employees.create', $this->formOptions());
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('employees')) {
            if (blank($data['employee_number'] ?? null)) {
                unset($data['employee_number']);
            }

            unset($data['photo'], $data['remove_photo']);

            $storedPath = null;

            if ($photo = $request->file('photo')) {
                $data['photo_path'] = $storedPath = $photo->store('employees', 'public');
            }

            try {
                $workflow->submit($request->user(), 'employees', ApprovalRequest::ACTION_CREATE, null, $data);
            } catch (ValidationException $exception) {
                if ($storedPath !== null) {
                    Storage::disk('public')->delete($storedPath);
                }

                throw $exception;
            }

            return redirect()->route('employees.index')
                ->with('status', __('Employee change request submitted for approval.'));
        }

        DB::transaction(function () use ($request, &$data): void {
            if (blank($data['employee_number'] ?? null)) {
                $data['employee_number'] = Employee::generateEmployeeNumber();
            }

            if ($photo = $request->file('photo')) {
                $data['photo_path'] = $photo->store('employees', 'public');
            }

            unset($data['photo'], $data['remove_photo']);

            Employee::create($data);
        });

        return redirect()->route('employees.index')->with('status', __('Employee created.'));
    }

    /**
     * Show the form for editing an employee.
     */
    public function edit(Employee $employee): View
    {
        return view('employees.edit', [
            'employee' => $employee,
            ...$this->formOptions($employee),
        ]);
    }

    /**
     * Update the specified employee.
     */
    public function update(Request $request, Employee $employee, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $employee);

        if ($workflow->isApprovalActive('employees')) {
            unset($data['photo'], $data['remove_photo']);

            if (blank($data['employee_number'] ?? null)) {
                $data['employee_number'] = $employee->employee_number;
            }

            $data['photo_path'] = $employee->photo_path;

            if ($request->boolean('remove_photo')) {
                $data['photo_path'] = null;
            }

            $storedPath = null;

            if ($photo = $request->file('photo')) {
                $data['photo_path'] = $storedPath = $photo->store('employees', 'public');
            }

            try {
                $workflow->submit($request->user(), 'employees', ApprovalRequest::ACTION_UPDATE, $employee->id, $data);
            } catch (ValidationException $exception) {
                if ($storedPath !== null) {
                    Storage::disk('public')->delete($storedPath);
                }

                throw $exception;
            }

            return redirect()->route('employees.index')
                ->with('status', __('Employee change request submitted for approval.'));
        }

        if (blank($data['employee_number'] ?? null)) {
            unset($data['employee_number']);
        }

        if ($request->boolean('remove_photo') && $employee->photo_path) {
            Storage::disk('public')->delete($employee->photo_path);
            $data['photo_path'] = null;
        }

        if ($photo = $request->file('photo')) {
            if ($employee->photo_path) {
                Storage::disk('public')->delete($employee->photo_path);
            }

            $data['photo_path'] = $photo->store('employees', 'public');
        }

        unset($data['photo'], $data['remove_photo']);

        $employee->update($data);

        return redirect()->route('employees.index')->with('status', __('Employee updated.'));
    }

    /**
     * Soft delete the employee; the photo is kept for audit.
     */
    public function destroy(Employee $employee, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($workflow->isApprovalActive('employees')) {
            $workflow->submit(request()->user(), 'employees', ApprovalRequest::ACTION_DELETE, $employee->id, []);

            return redirect()->route('employees.index')
                ->with('status', __('Employee change request submitted for approval.'));
        }

        $employee->delete();

        return redirect()->route('employees.index')->with('status', __('Employee deleted.'));
    }

    /**
     * Select options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(?Employee $employee = null): array
    {
        $linkedUserIds = Employee::query()
            ->whereNotNull('user_id')
            ->when($employee, fn (Builder $query) => $query->whereKeyNot($employee->getKey()))
            ->pluck('user_id');

        return [
            'divisions' => Division::query()->orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'orgUnits' => OrgUnit::query()->orderBy('name')->get(),
            'positions' => Position::query()->orderBy('name')->get(),
            'grades' => Grade::query()->orderBy('level')->get(),
            'employmentStatuses' => EmploymentStatus::query()->orderBy('sort_order')->get(),
            'workLocations' => WorkLocation::query()->orderBy('name')->get(),
            'religions' => Religion::query()->orderBy('sort_order')->get(),
            'educationLevels' => EducationLevel::query()->orderBy('level')->get(),
            'maritalStatuses' => MaritalStatus::query()->orderBy('sort_order')->get(),
            'users' => User::query()->whereNotIn('id', $linkedUserIds)->orderBy('name')->get(),
            'managers' => Employee::query()
                ->where('is_active', true)
                ->when($employee !== null, fn (Builder $query) => $query->where('id', '!=', $employee->id))
                ->orderBy('name')
                ->get(['id', 'name', 'employee_number']),
        ];
    }

    /**
     * Headline counts for the summary cards.
     *
     * Deliberately independent of the listing query so the figures always
     * describe the whole table, never the current search or filters.
     *
     * @return array{total: int, active: int, inactive: int, divisions: int, departments: int}
     */
    protected function summary(): array
    {
        return [
            'total' => Employee::query()->count(),
            'active' => Employee::query()->where('is_active', true)->count(),
            'inactive' => Employee::query()->where('is_active', false)->count(),
            'divisions' => Division::query()->whereHas('employees')->count(),
            'departments' => Department::query()->whereHas('employees')->count(),
        ];
    }

    /**
     * Apply the placement and status filters from the query string.
     *
     * The list UI allows a single active filter; only the first matching
     * parameter is applied so stale URLs cannot combine filters.
     */
    protected function applyTableFilters(Builder $query, Request $request): void
    {
        $placementFilters = [
            'division_id',
            'department_id',
            'org_unit_id',
            'position_id',
            'employment_status_id',
            'work_location_id',
        ];

        foreach ($placementFilters as $filter) {
            if ($value = $request->query($filter)) {
                $query->where('employees.'.$filter, $value);

                return;
            }
        }

        if (in_array($request->query('status'), ['active', 'inactive'], true)) {
            $query->where('employees.is_active', $request->query('status') === 'active');
        }
    }

    /**
     * Validate and normalize the employee payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Employee $employee = null): array
    {
        $data = $request->validate([
            'user_id' => ['nullable', Rule::exists('users', 'id'), Rule::unique('employees', 'user_id')->ignore($employee?->id)],
            'manager_id' => ['nullable', Rule::exists('employees', 'id')->whereNull('deleted_at'), Employee::managerCycleRule($employee)],
            'employee_number' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('employees', 'employee_number')->ignore($employee?->id)],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(Employee::GENDERS)],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'religion_id' => ['nullable', Rule::exists('religions', 'id')],
            'marital_status_id' => ['nullable', Rule::exists('marital_statuses', 'id')],
            'education_level_id' => ['nullable', Rule::exists('education_levels', 'id')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employee?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'identity_number' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'identity_number')->ignore($employee?->id)],
            'npwp' => ['nullable', 'string', 'max:50'],
            'bpjs_kesehatan' => ['nullable', 'string', 'max:50'],
            'bpjs_ketenagakerjaan' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'division_id' => ['required', Rule::exists('divisions', 'id')],
            'department_id' => ['required', Rule::exists('departments', 'id')->where('division_id', $request->integer('division_id'))],
            'org_unit_id' => ['nullable', Rule::exists('org_units', 'id')->where('department_id', $request->integer('department_id'))],
            'position_id' => ['required', Rule::exists('positions', 'id')],
            'grade_id' => ['nullable', Rule::exists('grades', 'id')],
            'work_location_id' => ['nullable', Rule::exists('work_locations', 'id')],
            'employment_status_id' => ['required', Rule::exists('employment_statuses', 'id')],
            'join_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'probation_end_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
