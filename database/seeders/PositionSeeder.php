<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Seed the job positions, scoped to their departments.
     */
    public function run(): void
    {
        $positions = [
            ['code' => 'POS-HRO', 'name' => 'HR Officer', 'department' => 'DEP-HRO'],
            ['code' => 'POS-REC', 'name' => 'Recruiter', 'department' => 'DEP-REC'],
            ['code' => 'POS-FIN', 'name' => 'Finance Staff', 'department' => 'DEP-FIN'],
            ['code' => 'POS-ACC', 'name' => 'Accounting Staff', 'department' => 'DEP-ACC'],
            ['code' => 'POS-PRD', 'name' => 'Production Operator', 'department' => 'DEP-PRD'],
            ['code' => 'POS-WHS', 'name' => 'Warehouse Staff', 'department' => 'DEP-LOG'],
            ['code' => 'POS-SLS', 'name' => 'Sales Executive', 'department' => 'DEP-SLS'],
            ['code' => 'POS-MKT', 'name' => 'Marketing Officer', 'department' => 'DEP-MKT'],
            ['code' => 'POS-ITS', 'name' => 'IT Support', 'department' => 'DEP-ITS'],
            ['code' => 'POS-ENG', 'name' => 'Software Engineer', 'department' => 'DEP-ENG'],
        ];

        foreach ($positions as $attributes) {
            $departmentCode = $attributes['department'];
            unset($attributes['department']);

            $attributes['department_id'] = Department::query()->where('code', $departmentCode)->value('id');

            Position::query()->updateOrCreate(
                ['code' => $attributes['code']],
                $attributes,
            );
        }

        $genericRoles = [
            ['code' => 'POS-CEO', 'name' => 'President Director'],
            ['code' => 'POS-DIV-HEAD', 'name' => 'Division Head'],
            ['code' => 'POS-DEP-HEAD', 'name' => 'Department Head'],
            ['code' => 'POS-UNIT-HEAD', 'name' => 'Unit Head'],
            ['code' => 'POS-STAFF', 'name' => 'Staff'],
        ];

        foreach ($genericRoles as $attributes) {
            Position::query()->updateOrCreate(
                ['code' => $attributes['code']],
                ['name' => $attributes['name'], 'department_id' => null],
            );
        }

        $departmentsWithPositions = Position::query()
            ->whereNotNull('department_id')
            ->pluck('department_id');

        Department::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->reject(fn (Department $department): bool => $departmentsWithPositions->contains($department->id))
            ->each(function (Department $department): void {
                Position::query()->updateOrCreate(
                    ['code' => 'POS-'.substr($department->code, 4).'-STAFF'],
                    ['name' => $department->name.' Staff', 'department_id' => $department->id],
                );
            });
    }
}
