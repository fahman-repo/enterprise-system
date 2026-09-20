<?php

use App\Models\Employee;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkLocation;
use App\Services\PermissionService;

function actingWorkLocationAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'work-locations']);

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
    $this->actingAs(actingWorkLocationAdmin());
});

test('work locations index lists locations', function () {
    WorkLocation::factory()->create(['name' => 'Quixotic Office']);

    $this->get(route('work-locations.index'))
        ->assertOk()
        ->assertSee('Quixotic Office');
});

test('admin can create and update a work location', function () {
    $this->post(route('work-locations.store'), [
        'code' => 'LOC-NEW',
        'name' => 'New Office',
        'city' => 'Jakarta',
        'province' => 'DKI Jakarta',
        'is_active' => '1',
    ])->assertRedirect(route('work-locations.index'));

    $location = WorkLocation::query()->where('code', 'LOC-NEW')->sole();

    $this->put(route('work-locations.update', $location), [
        'code' => 'LOC-REN',
        'name' => 'Renamed Office',
        'is_active' => '0',
    ])->assertRedirect(route('work-locations.index'));

    expect($location->fresh()->name)->toBe('Renamed Office')
        ->and($location->fresh()->is_active)->toBeFalse();
});

test('work location code must be unique', function () {
    $existing = WorkLocation::factory()->create(['code' => 'LOC-TAKEN']);

    $this->post(route('work-locations.store'), [
        'code' => $existing->code,
        'name' => 'Another Office',
    ])->assertSessionHasErrors('code');
});

test('a work location assigned to employees cannot be deleted', function () {
    $location = WorkLocation::factory()->create();
    Employee::factory()->create(['work_location_id' => $location->id]);

    $this->delete(route('work-locations.destroy', $location))
        ->assertSessionHasErrors('work_location');

    $this->assertDatabaseHas('work_locations', ['id' => $location->id]);
});

test('an unused work location can be deleted', function () {
    $location = WorkLocation::factory()->create();

    $this->delete(route('work-locations.destroy', $location))
        ->assertRedirect(route('work-locations.index'));

    $this->assertModelMissing($location);
});
