<?php

namespace Database\Seeders;

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
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Given names used to build deterministic employee names.
     *
     * @var list<string>
     */
    private const GIVEN = [
        'Agus', 'Budi', 'Dewi', 'Eko', 'Fitri', 'Gunawan', 'Hesti', 'Indra',
        'Joko', 'Kartika', 'Lestari', 'Made', 'Nurul', 'Putri', 'Rudi', 'Siti',
    ];

    /**
     * Family names used to build deterministic employee names.
     *
     * @var list<string>
     */
    private const FAMILY = [
        'Santoso', 'Wijaya', 'Kusuma', 'Hidayat', 'Pratama', 'Saputra',
        'Rahmawati', 'Utami', 'Nugroho', 'Setiawan', 'Halim', 'Susanto',
    ];

    /**
     * Seed a deterministic workforce following the org structure:
     * CEO, division heads, department heads, unit heads, then staff.
     * Rows are upserted by employee number so re-runs stay idempotent.
     */
    public function run(): void
    {
        $divisions = Division::query()->orderBy('code')->get();
        $departments = Department::query()->orderBy('code')->get();
        $orgUnits = OrgUnit::query()->orderBy('code')->get();
        $positions = Position::query()->orderBy('code')->get();
        $grades = Grade::query()->get()->keyBy('name');
        $statuses = EmploymentStatus::query()->get()->keyBy('code');
        $locations = WorkLocation::query()->orderBy('code')->get()->values();
        $religions = Religion::query()->orderBy('name')->get()->values();
        $maritalStatuses = MaritalStatus::query()->orderBy('name')->get()->values();
        $educationLevels = EducationLevel::query()->orderBy('name')->get()->values();

        if ($divisions->isEmpty() || $departments->isEmpty() || $orgUnits->isEmpty() || $positions->isEmpty()) {
            return;
        }

        $genericPositionId = fn (string $code): ?int => $positions->firstWhere('code', $code)?->id;
        $gradeId = fn (string $name): ?int => $grades->get($name)?->id;

        $seq = 0;

        /**
         * @param  array{manager_id: ?int, grade_id: ?int, position_id: ?int, division_id: ?int, department_id: ?int, org_unit_id: ?int}  $placement
         */
        $create = function (array $placement) use (
            &$seq,
            $locations,
            $statuses,
            $religions,
            $maritalStatuses,
            $educationLevels,
        ): Employee {
            $seq++;
            $number = sprintf('EMP-%05d', $seq);
            $given = self::GIVEN[$seq % count(self::GIVEN)];
            $family = self::FAMILY[intdiv($seq, count(self::GIVEN)) % count(self::FAMILY)];
            $name = $given.' '.$family;
            $joinDate = Carbon::createFromDate(2014, 1, 1)->addDays($seq * 11);

            $employmentStatusId = match (true) {
                $seq % 21 === 0 => $statuses->get('PROB')?->id,
                $seq % 15 === 0 => $statuses->get('CONT')?->id,
                default => $statuses->get('PERM')?->id,
            };

            $attributes = [
                'user_id' => null,
                'name' => $name,
                'gender' => $seq % 2 === 0 ? 'male' : 'female',
                'birth_place' => 'Birth City '.$seq,
                'birth_date' => sprintf('%04d-%02d-%02d', 1975 + ($seq % 25), 1 + ($seq % 12), 1 + ($seq % 28)),
                'religion_id' => $religions->isEmpty() ? null : $religions[$seq % $religions->count()]->id,
                'marital_status_id' => $maritalStatuses->isEmpty() ? null : $maritalStatuses[$seq % $maritalStatuses->count()]->id,
                'education_level_id' => $educationLevels->isEmpty() ? null : $educationLevels[$seq % $educationLevels->count()]->id,
                'email' => strtolower($given.'.'.$family.$seq).'@example.test',
                'phone' => '08'.str_pad((string) $seq, 10, '0', STR_PAD_LEFT),
                'identity_number' => str_pad((string) (9000000000000000 + $seq), 16, '0', STR_PAD_LEFT),
                'npwp' => sprintf('8%015d', $seq),
                'bpjs_kesehatan' => sprintf('7%012d', $seq),
                'bpjs_ketenagakerjaan' => sprintf('6%012d', $seq),
                'address' => 'Jl. Contoh No. '.$seq,
                'city' => 'City '.$seq,
                'province' => 'Province '.$seq,
                'postal_code' => str_pad((string) ($seq % 10000), 5, '0', STR_PAD_LEFT),
                'emergency_contact_name' => 'Emergency Contact '.$seq,
                'emergency_contact_relationship' => 'Spouse',
                'emergency_contact_phone' => '08'.str_pad((string) ($seq + 5000), 10, '0', STR_PAD_LEFT),
                'bank_name' => ['BCA', 'BNI', 'BRI', 'Mandiri'][$seq % 4],
                'bank_account_number' => sprintf('9%09d', $seq),
                'bank_account_name' => $name,
                'work_location_id' => $locations->isEmpty() ? null : $locations[$seq % $locations->count()]->id,
                'employment_status_id' => $employmentStatusId,
                'join_date' => $joinDate,
                'end_date' => null,
                'probation_end_date' => null,
                'photo_path' => null,
                'is_active' => true,
            ] + $placement;

            return Employee::query()->updateOrCreate(
                ['employee_number' => $number],
                $attributes,
            );
        };

        // CEO: no manager, no placement; the chart root's only leaf.
        $ceoId = $create([
            'manager_id' => null,
            'grade_id' => $gradeId('Director'),
            'position_id' => $genericPositionId('POS-CEO'),
            'division_id' => null,
            'department_id' => null,
            'org_unit_id' => null,
        ])->id;

        // Division heads report to the CEO.
        $divisionHeadIds = [];
        foreach ($divisions as $division) {
            $divisionHeadIds[$division->id] = $create([
                'manager_id' => $ceoId,
                'grade_id' => $gradeId('General Manager'),
                'position_id' => $genericPositionId('POS-DIV-HEAD'),
                'division_id' => $division->id,
                'department_id' => null,
                'org_unit_id' => null,
            ])->id;
        }

        // Department heads report to their division head.
        $departmentHeadIds = [];
        foreach ($departments as $deptIndex => $department) {
            $departmentHeadIds[$department->id] = $create([
                'manager_id' => $divisionHeadIds[$department->division_id] ?? $ceoId,
                'grade_id' => $gradeId($deptIndex % 2 === 0 ? 'Manager' : 'Assistant Manager'),
                'position_id' => $genericPositionId('POS-DEP-HEAD'),
                'division_id' => $department->division_id,
                'department_id' => $department->id,
                'org_unit_id' => null,
            ])->id;
        }

        // Unit heads report to their department head.
        $departmentsById = $departments->keyBy('id');
        $unitHeadIds = [];
        foreach ($orgUnits as $orgUnit) {
            $unitHeadIds[$orgUnit->id] = $create([
                'manager_id' => $departmentHeadIds[$orgUnit->department_id] ?? $ceoId,
                'grade_id' => $gradeId('Supervisor'),
                'position_id' => $genericPositionId('POS-UNIT-HEAD'),
                'division_id' => $departmentsById[$orgUnit->department_id]?->division_id,
                'department_id' => $orgUnit->department_id,
                'org_unit_id' => $orgUnit->id,
            ])->id;
        }

        // Staff report to their unit head; 2-4 per unit keeps sizes uneven.
        foreach ($orgUnits as $unitIndex => $orgUnit) {
            $department = $departmentsById[$orgUnit->department_id] ?? null;
            $staffPositionId = $positions->firstWhere('department_id', $orgUnit->department_id)?->id
                ?? $genericPositionId('POS-STAFF');

            for ($staffIndex = 0; $staffIndex < 2 + ($unitIndex % 3); $staffIndex++) {
                $employee = $create([
                    'manager_id' => $unitHeadIds[$orgUnit->id] ?? $departmentHeadIds[$orgUnit->department_id] ?? $ceoId,
                    'grade_id' => $gradeId($staffIndex % 2 === 0 ? 'Staff' : 'Senior Staff'),
                    'position_id' => $staffPositionId,
                    'division_id' => $department?->division_id,
                    'department_id' => $orgUnit->department_id,
                    'org_unit_id' => $orgUnit->id,
                ]);

                if ($seq % 25 === 0) {
                    $anniversary = $employee->join_date->copy()->addYears(3);

                    $employee->update([
                        'is_active' => false,
                        'end_date' => $anniversary->isPast() ? $anniversary : Carbon::createFromDate(2025, 6, 30),
                    ]);
                }
            }
        }
    }
}
