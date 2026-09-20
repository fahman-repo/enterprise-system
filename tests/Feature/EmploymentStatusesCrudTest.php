<?php

use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

function actingEmploymentStatusAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'employment-statuses']);

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
    $this->actingAs(actingEmploymentStatusAdmin());
});

test('employment statuses index lists statuses', function () {
    EmploymentStatus::factory()->create(['name' => 'Quixotic Status']);

    $this->get(route('employment-statuses.index'))
        ->assertOk()
        ->assertSee('Quixotic Status');
});

test('admin can create and update an employment status', function () {
    $this->post(route('employment-statuses.store'), [
        'code' => 'NEW',
        'name' => 'New Status',
        'sort_order' => 9,
        'is_active' => '1',
    ])->assertRedirect(route('employment-statuses.index'));

    $status = EmploymentStatus::query()->where('code', 'NEW')->sole();

    $this->put(route('employment-statuses.update', $status), [
        'code' => 'REN',
        'name' => 'Renamed Status',
        'is_active' => '0',
    ])->assertRedirect(route('employment-statuses.index'));

    expect($status->fresh()->name)->toBe('Renamed Status')
        ->and($status->fresh()->sort_order)->toBe(0)
        ->and($status->fresh()->is_active)->toBeFalse();
});

test('employment status code must be unique', function () {
    $existing = EmploymentStatus::factory()->create(['code' => 'TAKEN']);

    $this->post(route('employment-statuses.store'), [
        'code' => $existing->code,
        'name' => 'Another Status',
    ])->assertSessionHasErrors('code');
});

test('employment status name must be unique', function () {
    $existing = EmploymentStatus::factory()->create();

    $this->post(route('employment-statuses.store'), [
        'code' => 'OTHER',
        'name' => $existing->name,
    ])->assertSessionHasErrors('name');
});

test('an employment status assigned to employees cannot be deleted', function () {
    $status = EmploymentStatus::factory()->create();
    Employee::factory()->create(['employment_status_id' => $status->id]);

    $this->delete(route('employment-statuses.destroy', $status))
        ->assertSessionHasErrors('employment_status');

    $this->assertDatabaseHas('employment_statuses', ['id' => $status->id]);
});

test('an unused employment status can be deleted', function () {
    $status = EmploymentStatus::factory()->create();

    $this->delete(route('employment-statuses.destroy', $status))
        ->assertRedirect(route('employment-statuses.index'));

    $this->assertModelMissing($status);
});
