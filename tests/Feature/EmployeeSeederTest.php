<?php

use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\OrgUnit;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\EducationLevelSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\EmploymentStatusSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\MaritalStatusSeeder;
use Database\Seeders\OrgUnitSeeder;
use Database\Seeders\PositionSeeder;
use Database\Seeders\ReligionSeeder;
use Database\Seeders\SiteSeeder;

/**
 * Master data first, then the deterministic workforce.
 *
 * @return list<class-string>
 */
function orgSeeders(): array
{
    return [
        DivisionSeeder::class,
        DepartmentSeeder::class,
        OrgUnitSeeder::class,
        GradeSeeder::class,
        PositionSeeder::class,
        EmploymentStatusSeeder::class,
        SiteSeeder::class,
        ReligionSeeder::class,
        EducationLevelSeeder::class,
        MaritalStatusSeeder::class,
        EmployeeSeeder::class,
    ];
}

test('the employee seeder is idempotent', function () {
    $this->seed(orgSeeders());

    $before = Employee::query()->count();
    $managerLinks = Employee::query()->whereNotNull('manager_id')->count();

    $this->seed(orgSeeders());

    expect(Employee::query()->count())->toBe($before)
        ->and(Employee::query()->whereNotNull('manager_id')->count())->toBe($managerLinks)
        ->and(Division::query()->count())->toBe(8)
        ->and(Department::query()->count())->toBe(28)
        ->and(OrgUnit::query()->count())->toBe(56);
});

test('the employee seeder builds a large workforce with resolvable managers', function () {
    $this->seed(orgSeeders());

    $employees = Employee::query()->get(['id', 'manager_id']);
    $ids = $employees->pluck('id');

    expect($employees->count())->toBeGreaterThanOrEqual(180)
        ->toBeLessThanOrEqual(260)
        ->and($employees->pluck('manager_id')->filter()->diff($ids))->toHaveCount(0);
});

test('the employee seeder creates exactly one top-level chief executive', function () {
    $this->seed(orgSeeders());

    $top = Employee::query()->whereNull('manager_id')->get();

    expect($top)->toHaveCount(1)
        ->and($top->first()->position?->code)->toBe('POS-CEO');
});

test('the employee seeder staffs every org unit', function () {
    $this->seed(orgSeeders());

    $staffedUnitIds = Employee::query()
        ->whereNotNull('org_unit_id')
        ->distinct()
        ->pluck('org_unit_id');

    expect(OrgUnit::query()->whereNotIn('id', $staffedUnitIds)->count())->toBe(0);
});

test('the employee seeder reporting lines are acyclic', function () {
    $this->seed(orgSeeders());

    $managers = Employee::query()->pluck('manager_id', 'id');

    foreach ($managers as $id => $managerId) {
        $visited = [$id => true];
        $current = $managerId;
        $hops = 0;

        while ($current !== null && $hops < 100) {
            expect(isset($visited[$current]))->toBeFalse();

            $visited[$current] = true;
            $current = $managers[$current] ?? null;
            $hops++;
        }

        expect($current)->toBeNull();
    }
});
