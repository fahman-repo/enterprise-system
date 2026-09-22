<?php

use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Menu;
use App\Models\OrgUnit;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

function actingDepartmentAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'departments']);

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
    $this->actingAs(actingDepartmentAdmin());
});

test('departments index lists departments with their division', function () {
    $division = Division::factory()->create(['name' => 'Northwind Division']);
    Department::factory()->forDivision($division)->create(['name' => 'Quixotic Department']);

    $this->get(route('departments.index'))
        ->assertOk()
        ->assertSee('Quixotic Department')
        ->assertSee('Northwind Division');
});

test('departments index search matches name and division', function () {
    $division = Division::factory()->create(['name' => 'Umbrella Holdings']);
    $department = Department::factory()->forDivision($division)->create(['name' => 'Quixotic Department']);
    $unmatched = Department::factory()->create(['name' => 'Nothing Here']);

    $this->get(route('departments.index', ['search' => 'umbrella']))
        ->assertOk()
        ->assertSee($department->name)
        ->assertDontSee($unmatched->name);
});

test('departments index filters by division', function () {
    $division = Division::factory()->create();
    $department = Department::factory()->forDivision($division)->create();
    $other = Department::factory()->create();

    $this->get(route('departments.index', ['division_id' => $division->id]))
        ->assertOk()
        ->assertSee($department->name)
        ->assertDontSee($other->name);
});

test('departments index filters by status', function () {
    $active = Department::factory()->create(['name' => 'Active Department']);
    $inactive = Department::factory()->inactive()->create(['name' => 'Inactive Department']);

    $this->get(route('departments.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee($inactive->name)
        ->assertDontSee($active->name);
});

test('departments index ignores status when a division filter is applied', function () {
    $division = Division::factory()->create();
    $department = Department::factory()->forDivision($division)->create(['name' => 'In First Division']);
    $inactive = Department::factory()->inactive()->create(['name' => 'Inactive Elsewhere']);

    $this->get(route('departments.index', ['division_id' => $division->id, 'status' => 'inactive']))
        ->assertOk()
        ->assertSee($department->name)
        ->assertDontSee($inactive->name);
});

test('admin can create and update a department', function () {
    $division = Division::factory()->create();

    $this->post(route('departments.store'), [
        'division_id' => $division->id,
        'code' => 'DEP-NEW',
        'name' => 'New Department',
        'description' => 'Department description.',
        'is_active' => '1',
    ])->assertRedirect(route('departments.index'));

    $department = Department::query()->where('code', 'DEP-NEW')->sole();

    expect($department->division_id)->toBe($division->id);

    $this->put(route('departments.update', $department), [
        'division_id' => $division->id,
        'code' => 'DEP-REN',
        'name' => 'Renamed Department',
        'is_active' => '0',
    ])->assertRedirect(route('departments.index'));

    expect($department->fresh()->name)->toBe('Renamed Department')
        ->and($department->fresh()->is_active)->toBeFalse();
});

test('department requires an existing division', function () {
    $this->post(route('departments.store'), [
        'code' => 'DEP-ORPHAN',
        'name' => 'Orphan Department',
    ])->assertSessionHasErrors('division_id');
});

test('department code must be unique', function () {
    $existing = Department::factory()->create(['code' => 'DEP-TAKEN']);

    $this->post(route('departments.store'), [
        'division_id' => $existing->division_id,
        'code' => $existing->code,
        'name' => 'Another Department',
    ])->assertSessionHasErrors('code');
});

test('a department with org units cannot be deleted', function () {
    $department = Department::factory()->create();
    OrgUnit::factory()->forDepartment($department)->create();

    $this->delete(route('departments.destroy', $department))
        ->assertSessionHasErrors('department');

    $this->assertDatabaseHas('departments', ['id' => $department->id]);
});

test('a department with employees cannot be deleted', function () {
    $department = Department::factory()->create();
    Employee::factory()->forDepartment($department)->create();

    $this->delete(route('departments.destroy', $department))
        ->assertSessionHasErrors('department');

    $this->assertDatabaseHas('departments', ['id' => $department->id]);
});

test('an unused department can be deleted', function () {
    $department = Department::factory()->create();

    $this->delete(route('departments.destroy', $department))
        ->assertRedirect(route('departments.index'));

    $this->assertModelMissing($department);
});
