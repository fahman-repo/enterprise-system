<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Menu;
use App\Models\OrgUnit;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

function actingOrgUnitAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'org-units']);

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
    $this->actingAs(actingOrgUnitAdmin());
});

test('org units index lists org units with their department', function () {
    $department = Department::factory()->create(['name' => 'Northwind Department']);
    OrgUnit::factory()->forDepartment($department)->create(['name' => 'Quixotic Unit']);

    $this->get(route('org-units.index'))
        ->assertOk()
        ->assertSee('Quixotic Unit')
        ->assertSee('Northwind Department');
});

test('org units index search matches name and code', function () {
    $unit = OrgUnit::factory()->create(['name' => 'Quixotic Unit']);
    $unmatched = OrgUnit::factory()->create(['name' => 'Nothing Here']);

    $this->get(route('org-units.index', ['search' => 'quixotic']))
        ->assertOk()
        ->assertSee($unit->name)
        ->assertDontSee($unmatched->name);
});

test('org units index filters by department', function () {
    $department = Department::factory()->create();
    $unit = OrgUnit::factory()->forDepartment($department)->create();
    $other = OrgUnit::factory()->create();

    $this->get(route('org-units.index', ['department_id' => $department->id]))
        ->assertOk()
        ->assertSee($unit->name)
        ->assertDontSee($other->name);
});

test('admin can create and update an org unit', function () {
    $department = Department::factory()->create();

    $this->post(route('org-units.store'), [
        'department_id' => $department->id,
        'code' => 'ORG-NEW',
        'name' => 'New Org Unit',
        'description' => 'Org unit description.',
        'is_active' => '1',
    ])->assertRedirect(route('org-units.index'));

    $orgUnit = OrgUnit::query()->where('code', 'ORG-NEW')->sole();

    expect($orgUnit->department_id)->toBe($department->id);

    $this->put(route('org-units.update', $orgUnit), [
        'department_id' => $department->id,
        'code' => 'ORG-REN',
        'name' => 'Renamed Org Unit',
        'is_active' => '0',
    ])->assertRedirect(route('org-units.index'));

    expect($orgUnit->fresh()->name)->toBe('Renamed Org Unit')
        ->and($orgUnit->fresh()->is_active)->toBeFalse();
});

test('org unit requires an existing department', function () {
    $this->post(route('org-units.store'), [
        'code' => 'ORG-ORPHAN',
        'name' => 'Orphan Org Unit',
    ])->assertSessionHasErrors('department_id');
});

test('org unit code must be unique', function () {
    $existing = OrgUnit::factory()->create(['code' => 'ORG-TAKEN']);

    $this->post(route('org-units.store'), [
        'department_id' => $existing->department_id,
        'code' => $existing->code,
        'name' => 'Another Org Unit',
    ])->assertSessionHasErrors('code');
});

test('an org unit with employees cannot be deleted', function () {
    $orgUnit = OrgUnit::factory()->create();
    Employee::factory()->create([
        'division_id' => $orgUnit->department?->division_id,
        'department_id' => $orgUnit->department_id,
        'org_unit_id' => $orgUnit->id,
    ]);

    $this->delete(route('org-units.destroy', $orgUnit))
        ->assertSessionHasErrors('org_unit');

    $this->assertDatabaseHas('org_units', ['id' => $orgUnit->id]);
});

test('an unused org unit can be deleted', function () {
    $orgUnit = OrgUnit::factory()->create();

    $this->delete(route('org-units.destroy', $orgUnit))
        ->assertRedirect(route('org-units.index'));

    $this->assertModelMissing($orgUnit);
});
