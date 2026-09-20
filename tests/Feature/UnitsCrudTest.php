<?php

use App\Models\Menu;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Services\PermissionService;

function actingUnitAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'units']);

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
    $this->admin = actingUnitAdmin();
    $this->actingAs($this->admin);
});

test('units index lists units', function () {
    Unit::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'kg']);

    $this->get(route('units.index'))
        ->assertOk()
        ->assertSee('Kilogram')
        ->assertSee('kg');
});

test('units index search matches name and abbreviation', function () {
    $unit = Unit::factory()->create(['name' => 'Quixotic Unit', 'abbreviation' => 'qxu']);
    $unmatched = Unit::factory()->create(['name' => 'Nothing Here']);

    $this->get(route('units.index', ['search' => 'quixotic']))
        ->assertOk()
        ->assertSee($unit->name)
        ->assertDontSee($unmatched->name);

    $this->get(route('units.index', ['search' => 'qxu']))
        ->assertOk()
        ->assertSee($unit->abbreviation);
});

test('admin can create and update a unit', function () {
    $this->post(route('units.store'), [
        'name' => 'Kilogram',
        'abbreviation' => 'kg',
        'allows_decimal' => '1',
        'is_active' => '1',
    ])->assertRedirect(route('units.index'));

    $unit = Unit::query()->where('abbreviation', 'kg')->sole();

    expect($unit->allows_decimal)->toBeTrue();

    $this->put(route('units.update', $unit), [
        'name' => 'Kilogram (metric)',
        'abbreviation' => 'kg',
        'is_active' => '1',
    ])->assertRedirect(route('units.index'));

    expect($unit->fresh()->name)->toBe('Kilogram (metric)')
        ->and($unit->fresh()->allows_decimal)->toBeFalse();
});

test('unit abbreviation must be unique', function () {
    $existing = Unit::factory()->create(['abbreviation' => 'taken-abbr']);

    $this->post(route('units.store'), [
        'name' => 'Another Unit',
        'abbreviation' => $existing->abbreviation,
    ])->assertSessionHasErrors('abbreviation');
});

test('a unit used by products cannot be deleted', function () {
    $unit = Unit::factory()->create();
    Product::factory()->create(['unit_id' => $unit->id]);

    $this->delete(route('units.destroy', $unit))
        ->assertSessionHasErrors('unit');

    $this->assertDatabaseHas('units', ['id' => $unit->id]);
});

test('an unused unit can be deleted', function () {
    $unit = Unit::factory()->create();

    $this->delete(route('units.destroy', $unit))
        ->assertRedirect(route('units.index'));

    $this->assertModelMissing($unit);
});
