<?php

namespace App\Services\Approvals;

use App\Models\ApprovalRequest;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeesApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'employees';
    }

    public function label(): string
    {
        return __('Employees');
    }

    public function targetLabel(): string
    {
        return __('Employee');
    }

    protected function modelClass(): string
    {
        return Employee::class;
    }

    protected function fields(): array
    {
        return [
            'user_id',
            'manager_id',
            'employee_number',
            'name',
            'gender',
            'birth_place',
            'birth_date',
            'religion_id',
            'marital_status_id',
            'education_level_id',
            'email',
            'phone',
            'identity_number',
            'npwp',
            'bpjs_kesehatan',
            'bpjs_ketenagakerjaan',
            'address',
            'city',
            'province',
            'postal_code',
            'emergency_contact_name',
            'emergency_contact_relationship',
            'emergency_contact_phone',
            'bank_name',
            'bank_account_number',
            'bank_account_name',
            'division_id',
            'department_id',
            'org_unit_id',
            'position_id',
            'grade_id',
            'work_location_id',
            'employment_status_id',
            'join_date',
            'end_date',
            'probation_end_date',
            'photo_path',
            'is_active',
        ];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'user_id' => ['nullable', Rule::exists('users', 'id'), Rule::unique('employees', 'user_id')->ignore($target?->getKey())],
            'manager_id' => ['nullable', Rule::exists('employees', 'id')->whereNull('deleted_at'), Employee::managerCycleRule($target instanceof Employee ? $target : null)],
            'employee_number' => ['nullable', Rule::unique('employees', 'employee_number')->ignore($target?->getKey())],
            'name' => ['required'],
            'gender' => ['required', Rule::in(Employee::GENDERS)],
            'religion_id' => ['nullable', Rule::exists('religions', 'id')],
            'marital_status_id' => ['nullable', Rule::exists('marital_statuses', 'id')],
            'education_level_id' => ['nullable', Rule::exists('education_levels', 'id')],
            'email' => ['nullable', Rule::unique('employees', 'email')->ignore($target?->getKey())],
            'identity_number' => ['nullable', Rule::unique('employees', 'identity_number')->ignore($target?->getKey())],
            'division_id' => ['required', Rule::exists('divisions', 'id')],
            'department_id' => ['required', Rule::exists('departments', 'id')],
            'org_unit_id' => ['nullable', Rule::exists('org_units', 'id')],
            'position_id' => ['required', Rule::exists('positions', 'id')],
            'grade_id' => ['nullable', Rule::exists('grades', 'id')],
            'work_location_id' => ['nullable', Rule::exists('work_locations', 'id')],
            'employment_status_id' => ['required', Rule::exists('employment_statuses', 'id')],
            'join_date' => ['required'],
        ];
    }

    /**
     * Generate the employee number at final approval when the maker left
     * it blank, mirroring the controller's in-transaction generation.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function prepareCreate(ApprovalRequest $request, array $payload): array
    {
        if (blank($payload['employee_number'] ?? null)) {
            $payload['employee_number'] = Employee::generateEmployeeNumber();
        }

        return $payload;
    }

    /**
     * Persist the update and delete the photo that was replaced or removed.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function applyUpdate(ApprovalRequest $request, Model $target, array $payload): void
    {
        /** @var Employee $target */
        $old = $target->photo_path;

        $target->update($payload);

        if (array_key_exists('photo_path', $payload) && $old && $old !== $payload['photo_path']) {
            Storage::disk('public')->delete($old);
        }
    }

    /**
     * Delete the file stored when the request was submitted but never applied.
     */
    public function discard(ApprovalRequest $request): void
    {
        $new = $request->proposed_payload['photo_path'] ?? null;
        $old = $request->before_payload['photo_path'] ?? null;

        if ($new && $new !== $old) {
            Storage::disk('public')->delete($new);
        }
    }
}
