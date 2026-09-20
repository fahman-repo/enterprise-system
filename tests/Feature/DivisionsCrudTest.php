<?php

use App\Models\Department;
use App\Models\Division;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

function actingDivisionAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'divisions']);

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
    $this->actingAs(actingDivisionAdmin());
});

test('divisions index lists divisions', function () {
    Division::factory()->create(['name' => 'Northwind Division']);

    $this->get(route('divisions.index'))
        ->assertOk()
        ->assertSee('Northwind Division');
});

test('divisions index search matches code and name', function () {
    $division = Division::factory()->create(['name' => 'Quixotic Ventures', 'code' => 'DIV-QX']);
    $unmatched = Division::factory()->create(['name' => 'Nothing Here', 'code' => 'DIV-NH']);

    $this->get(route('divisions.index', ['search' => 'quixotic']))
        ->assertOk()
        ->assertSee($division->name)
        ->assertDontSee($unmatched->name);
});

test('admin can create and update a division', function () {
    $this->post(route('divisions.store'), [
        'code' => 'DIV-NEW',
        'name' => 'New Division',
        'description' => 'Division description.',
        'is_active' => '1',
    ])->assertRedirect(route('divisions.index'));

    $division = Division::query()->where('code', 'DIV-NEW')->sole();

    $this->put(route('divisions.update', $division), [
        'code' => 'DIV-REN',
        'name' => 'Renamed Division',
        'is_active' => '0',
    ])->assertRedirect(route('divisions.index'));

    expect($division->fresh()->name)->toBe('Renamed Division')
        ->and($division->fresh()->is_active)->toBeFalse();
});

test('division code must be unique', function () {
    $existing = Division::factory()->create(['code' => 'DIV-TAKEN']);

    $this->post(route('divisions.store'), [
        'code' => $existing->code,
        'name' => 'Another Division',
    ])->assertSessionHasErrors('code');
});

test('a division with departments cannot be deleted', function () {
    $division = Division::factory()->create();
    Department::factory()->forDivision($division)->create();

    $this->delete(route('divisions.destroy', $division))
        ->assertSessionHasErrors('division');

    $this->assertDatabaseHas('divisions', ['id' => $division->id]);
});

test('an unused division can be deleted', function () {
    $division = Division::factory()->create();

    $this->delete(route('divisions.destroy', $division))
        ->assertRedirect(route('divisions.index'));

    $this->assertModelMissing($division);
});
