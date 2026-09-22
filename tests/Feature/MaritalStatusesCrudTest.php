<?php

use App\Models\Employee;
use App\Models\MaritalStatus;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

function actingMaritalStatusAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'marital-statuses']);

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
    $this->actingAs(actingMaritalStatusAdmin());
});

test('marital statuses index lists statuses', function () {
    MaritalStatus::factory()->create(['name' => 'Quixotic Status']);

    $this->get(route('marital-statuses.index'))
        ->assertOk()
        ->assertSee('Quixotic Status');
});

test('marital statuses index filters by status', function () {
    $active = MaritalStatus::factory()->create(['name' => 'Active Marital Status']);
    $inactive = MaritalStatus::factory()->inactive()->create(['name' => 'Inactive Marital Status']);

    $this->get(route('marital-statuses.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee($inactive->name)
        ->assertDontSee($active->name);
});

test('admin can create and update a marital status', function () {
    $this->post(route('marital-statuses.store'), [
        'name' => 'New Status',
        'sort_order' => 9,
        'is_active' => '1',
    ])->assertRedirect(route('marital-statuses.index'));

    $status = MaritalStatus::query()->where('name', 'New Status')->sole();

    $this->put(route('marital-statuses.update', $status), [
        'name' => 'Renamed Status',
        'is_active' => '0',
    ])->assertRedirect(route('marital-statuses.index'));

    expect($status->fresh()->name)->toBe('Renamed Status')
        ->and($status->fresh()->sort_order)->toBe(0)
        ->and($status->fresh()->is_active)->toBeFalse();
});

test('marital status name must be unique', function () {
    $existing = MaritalStatus::factory()->create();

    $this->post(route('marital-statuses.store'), [
        'name' => $existing->name,
    ])->assertSessionHasErrors('name');
});

test('a marital status assigned to employees cannot be deleted', function () {
    $status = MaritalStatus::factory()->create();
    Employee::factory()->create(['marital_status_id' => $status->id]);

    $this->delete(route('marital-statuses.destroy', $status))
        ->assertSessionHasErrors('marital_status');

    $this->assertDatabaseHas('marital_statuses', ['id' => $status->id]);
});

test('an unused marital status can be deleted', function () {
    $status = MaritalStatus::factory()->create();

    $this->delete(route('marital-statuses.destroy', $status))
        ->assertRedirect(route('marital-statuses.index'));

    $this->assertModelMissing($status);
});
