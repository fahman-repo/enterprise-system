<?php

use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Menu;
use App\Models\OrgUnit;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkLocation;
use App\Services\PermissionService;

function actingOrgChartAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'org-chart', 'route_name' => 'org-chart.index']);

    $role->menus()->sync([$menu->id => [
        'can_view' => true,
        'can_create' => true,
        'can_update' => true,
        'can_delete' => true,
    ]]);

    app(PermissionService::class)->flush();

    return User::factory()->create(['role_id' => $role->id]);
}

beforeEach(function () {
    $this->actingAs(actingOrgChartAdmin());
});

test('guests are redirected to login from the org chart routes', function () {
    auth()->logout();

    $this->get(route('org-chart.index'))->assertRedirect(route('login'));
    $this->get(route('org-chart.data'))->assertRedirect(route('login'));
});

test('a user without view permission is forbidden from the org chart routes', function () {
    $menu = Menu::query()->where('slug', 'org-chart')->sole();
    $role = Role::factory()->create();

    $role->menus()->sync([$menu->id => [
        'can_view' => false,
        'can_create' => false,
        'can_update' => false,
        'can_delete' => false,
    ]]);

    app(PermissionService::class)->flush();

    $this->actingAs(User::factory()->create(['role_id' => $role->id]));

    $this->get(route('org-chart.index'))->assertForbidden();
    $this->get(route('org-chart.data'))->assertForbidden();
});

test('the org chart page renders the canvas and the location filter', function () {
    WorkLocation::factory()->create(['name' => 'Head Office Jakarta']);

    $this->get(route('org-chart.index'))
        ->assertOk()
        ->assertSee('data-org-chart')
        ->assertSee('Head Office Jakarta');
});

test('structure mode nests employees at their deepest placement', function () {
    $division = Division::factory()->create();
    $department = Department::factory()->forDivision($division)->create();
    $unit = OrgUnit::factory()->forDepartment($department)->create();

    $inUnit = Employee::factory()->create([
        'division_id' => $division->id,
        'department_id' => $department->id,
        'org_unit_id' => $unit->id,
    ]);
    $inDepartment = Employee::factory()->create([
        'division_id' => $division->id,
        'department_id' => $department->id,
        'org_unit_id' => null,
    ]);
    $inDivision = Employee::factory()->create([
        'division_id' => $division->id,
        'department_id' => null,
        'org_unit_id' => null,
    ]);
    $inCompany = Employee::factory()->create([
        'division_id' => null,
        'department_id' => null,
        'org_unit_id' => null,
    ]);

    $nodes = $this->getJson(route('org-chart.data', ['mode' => 'structure']))->json('nodes');
    $byId = collect($nodes)->keyBy('id');

    expect($nodes[0]['id'])->toBe('company')
        ->and($byId['emp-'.$inUnit->id]['parentId'])->toBe('unit-'.$unit->id)
        ->and($byId['emp-'.$inDepartment->id]['parentId'])->toBe('dep-'.$department->id)
        ->and($byId['emp-'.$inDivision->id]['parentId'])->toBe('div-'.$division->id)
        ->and($byId['emp-'.$inCompany->id]['parentId'])->toBe('company')
        ->and($byId['dep-'.$department->id]['parentId'])->toBe('div-'.$division->id)
        ->and($byId['unit-'.$unit->id]['parentId'])->toBe('dep-'.$department->id);
});

test('reporting mode nests employees under their managers', function () {
    $manager = Employee::factory()->create();
    $report = Employee::factory()->reportsTo($manager)->create();
    $top = Employee::factory()->create();

    $nodes = $this->getJson(route('org-chart.data', ['mode' => 'reporting']))->json('nodes');
    $byId = collect($nodes)->keyBy('id');

    expect($byId['emp-'.$report->id]['parentId'])->toBe('emp-'.$manager->id)
        ->and($byId['emp-'.$manager->id]['parentId'])->toBe('company')
        ->and($byId['emp-'.$top->id]['parentId'])->toBe('company');
});

test('reporting mode keeps manager chains connected across the location filter', function () {
    $office = WorkLocation::factory()->create();
    $remote = WorkLocation::factory()->create();

    $manager = Employee::factory()->create(['work_location_id' => $office->id]);
    $report = Employee::factory()->reportsTo($manager)->create(['work_location_id' => $remote->id]);

    $nodes = $this->getJson(route('org-chart.data', [
        'mode' => 'reporting',
        'work_location_id' => $remote->id,
    ]))->json('nodes');
    $byId = collect($nodes)->keyBy('id');

    expect($byId->has('emp-'.$report->id))->toBeTrue()
        ->and($byId->has('emp-'.$manager->id))->toBeTrue()
        ->and($byId['emp-'.$report->id]['parentId'])->toBe('emp-'.$manager->id);
});

test('structure mode filters employees by work location and counts filtered leaves', function () {
    $office = WorkLocation::factory()->create();
    $remote = WorkLocation::factory()->create();

    $division = Division::factory()->create();
    $department = Department::factory()->forDivision($division)->create();
    $unit = OrgUnit::factory()->forDepartment($department)->create();

    $local = Employee::factory()->create([
        'work_location_id' => $office->id,
        'division_id' => $division->id,
        'department_id' => $department->id,
        'org_unit_id' => $unit->id,
    ]);
    $away = Employee::factory()->create([
        'work_location_id' => $remote->id,
        'division_id' => $division->id,
        'department_id' => $department->id,
        'org_unit_id' => $unit->id,
    ]);

    $nodes = $this->getJson(route('org-chart.data', [
        'mode' => 'structure',
        'work_location_id' => $office->id,
    ]))->json('nodes');
    $byId = collect($nodes)->keyBy('id');

    expect($byId->has('emp-'.$local->id))->toBeTrue()
        ->and($byId->has('emp-'.$away->id))->toBeFalse()
        ->and($byId->has('unit-'.$unit->id))->toBeTrue()
        ->and($byId['unit-'.$unit->id]['subtitle'])->toBe('1 employees');
});

test('inactive and soft deleted employees are excluded from both modes', function () {
    $inactive = Employee::factory()->inactive()->create();
    $deleted = Employee::factory()->create();
    $deleted->delete();

    foreach (['structure', 'reporting'] as $mode) {
        $ids = collect($this->getJson(route('org-chart.data', ['mode' => $mode]))->json('nodes'))->pluck('id');

        expect($ids)->not->toContain('emp-'.$inactive->id)
            ->not->toContain('emp-'.$deleted->id);
    }
});

test('an unknown mode is rejected', function () {
    $this->get(route('org-chart.data', ['mode' => 'bogus']))
        ->assertSessionHasErrors('mode');
});
