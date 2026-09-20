<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Menu;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

function actingPositionAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'positions']);

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
    $this->actingAs(actingPositionAdmin());
});

test('positions index lists positions with their department', function () {
    $department = Department::factory()->create(['name' => 'Northwind Department']);
    Position::factory()->forDepartment($department)->create(['name' => 'Quixotic Analyst']);

    $this->get(route('positions.index'))
        ->assertOk()
        ->assertSee('Quixotic Analyst')
        ->assertSee('Northwind Department');
});

test('positions index search matches name and department', function () {
    $department = Department::factory()->create(['name' => 'Umbrella Holdings']);
    $position = Position::factory()->forDepartment($department)->create(['name' => 'Quixotic Analyst']);
    $unmatched = Position::factory()->create(['name' => 'Nothing Here']);

    $this->get(route('positions.index', ['search' => 'umbrella']))
        ->assertOk()
        ->assertSee($position->name)
        ->assertDontSee($unmatched->name);
});

test('admin can create and update a position', function () {
    $department = Department::factory()->create();

    $this->post(route('positions.store'), [
        'department_id' => $department->id,
        'code' => 'POS-NEW',
        'name' => 'New Position',
        'is_active' => '1',
    ])->assertRedirect(route('positions.index'));

    $position = Position::query()->where('code', 'POS-NEW')->sole();

    $this->put(route('positions.update', $position), [
        'department_id' => null,
        'code' => 'POS-REN',
        'name' => 'Renamed Position',
        'is_active' => '0',
    ])->assertRedirect(route('positions.index'));

    expect($position->fresh()->name)->toBe('Renamed Position')
        ->and($position->fresh()->department_id)->toBeNull()
        ->and($position->fresh()->is_active)->toBeFalse();
});

test('position code must be unique', function () {
    $existing = Position::factory()->create(['code' => 'POS-TAKEN']);

    $this->post(route('positions.store'), [
        'code' => $existing->code,
        'name' => 'Another Position',
    ])->assertSessionHasErrors('code');
});

test('a position assigned to employees cannot be deleted', function () {
    $position = Position::factory()->create();
    Employee::factory()->create(['position_id' => $position->id]);

    $this->delete(route('positions.destroy', $position))
        ->assertSessionHasErrors('position');

    $this->assertDatabaseHas('positions', ['id' => $position->id]);
});

test('an unused position can be deleted', function () {
    $position = Position::factory()->create();

    $this->delete(route('positions.destroy', $position))
        ->assertRedirect(route('positions.index'));

    $this->assertModelMissing($position);
});
