<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Grade;
use App\Models\MaritalStatus;
use App\Models\Position;
use App\Models\Religion;
use App\Models\WorkLocation;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Seed a demo workforce spread across the org structure.
     */
    public function run(): void
    {
        if (Employee::query()->withTrashed()->exists()) {
            return;
        }

        $departments = Department::query()->get();
        $positions = Position::query()->get();
        $grades = Grade::query()->get();
        $statuses = EmploymentStatus::query()->pluck('id');
        $locations = WorkLocation::query()->pluck('id');
        $religions = Religion::query()->pluck('id');
        $maritalStatuses = MaritalStatus::query()->pluck('id');
        $educationLevels = EducationLevel::query()->pluck('id');

        if ($departments->isEmpty() || $positions->isEmpty() || $statuses->isEmpty()) {
            return;
        }

        for ($index = 1; $index <= 24; $index++) {
            $department = $departments->random();
            $position = $positions->where('department_id', $department->getKey())->first()
                ?? $positions->random();

            $factory = Employee::factory()
                ->forDepartment($department)
                ->state([
                    'employee_number' => 'EMP-'.str_pad((string) $index, 5, '0', STR_PAD_LEFT),
                    'position_id' => $position->getKey(),
                    'grade_id' => $grades->isNotEmpty() ? $grades->random()->getKey() : null,
                    'work_location_id' => $locations->isNotEmpty() ? $locations->random() : null,
                    'employment_status_id' => $statuses->random(),
                    'religion_id' => $religions->isNotEmpty() ? $religions->random() : null,
                    'marital_status_id' => $maritalStatuses->isNotEmpty() ? $maritalStatuses->random() : null,
                    'education_level_id' => $educationLevels->isNotEmpty() ? $educationLevels->random() : null,
                ]);

            if ($index % 8 === 0) {
                $factory = $factory->inactive()->state([
                    'end_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                ]);
            }

            $factory->create();
        }
    }
}
