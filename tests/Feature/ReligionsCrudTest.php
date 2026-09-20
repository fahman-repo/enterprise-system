<?php

use App\Models\Employee;
use App\Models\Menu;
use App\Models\Religion;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

function actingReligionAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'religions']);

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
    $this->actingAs(actingReligionAdmin());
});

test('religions index lists religions', function () {
    Religion::factory()->create(['name' => 'Quixotism']);

    $this->get(route('religions.index'))
        ->assertOk()
        ->assertSee('Quixotism');
});

test('admin can create and update a religion', function () {
    $this->post(route('religions.store'), [
        'name' => 'New Religion',
        'sort_order' => 9,
        'is_active' => '1',
    ])->assertRedirect(route('religions.index'));

    $religion = Religion::query()->where('name', 'New Religion')->sole();

    $this->put(route('religions.update', $religion), [
        'name' => 'Renamed Religion',
        'is_active' => '0',
    ])->assertRedirect(route('religions.index'));

    expect($religion->fresh()->name)->toBe('Renamed Religion')
        ->and($religion->fresh()->sort_order)->toBe(0)
        ->and($religion->fresh()->is_active)->toBeFalse();
});

test('religion name must be unique', function () {
    $existing = Religion::factory()->create();

    $this->post(route('religions.store'), [
        'name' => $existing->name,
    ])->assertSessionHasErrors('name');
});

test('a religion assigned to employees cannot be deleted', function () {
    $religion = Religion::factory()->create();
    Employee::factory()->create(['religion_id' => $religion->id]);

    $this->delete(route('religions.destroy', $religion))
        ->assertSessionHasErrors('religion');

    $this->assertDatabaseHas('religions', ['id' => $religion->id]);
});

test('an unused religion can be deleted', function () {
    $religion = Religion::factory()->create();

    $this->delete(route('religions.destroy', $religion))
        ->assertRedirect(route('religions.index'));

    $this->assertModelMissing($religion);
});
