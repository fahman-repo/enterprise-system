<?php

use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

function actingEducationLevelAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'education-levels']);

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
    $this->actingAs(actingEducationLevelAdmin());
});

test('education levels index lists levels', function () {
    EducationLevel::factory()->create(['name' => 'Quixotic Studies']);

    $this->get(route('education-levels.index'))
        ->assertOk()
        ->assertSee('Quixotic Studies');
});

test('admin can create and update an education level', function () {
    $this->post(route('education-levels.store'), [
        'name' => 'New Level',
        'level' => 4,
        'sort_order' => 4,
        'is_active' => '1',
    ])->assertRedirect(route('education-levels.index'));

    $level = EducationLevel::query()->where('name', 'New Level')->sole();

    $this->put(route('education-levels.update', $level), [
        'name' => 'Renamed Level',
        'level' => 6,
        'is_active' => '0',
    ])->assertRedirect(route('education-levels.index'));

    expect($level->fresh()->name)->toBe('Renamed Level')
        ->and($level->fresh()->level)->toBe(6)
        ->and($level->fresh()->is_active)->toBeFalse();
});

test('education level name must be unique', function () {
    $existing = EducationLevel::factory()->create();

    $this->post(route('education-levels.store'), [
        'name' => $existing->name,
        'level' => 1,
    ])->assertSessionHasErrors('name');
});

test('an education level assigned to employees cannot be deleted', function () {
    $level = EducationLevel::factory()->create();
    Employee::factory()->create(['education_level_id' => $level->id]);

    $this->delete(route('education-levels.destroy', $level))
        ->assertSessionHasErrors('education_level');

    $this->assertDatabaseHas('education_levels', ['id' => $level->id]);
});

test('an unused education level can be deleted', function () {
    $level = EducationLevel::factory()->create();

    $this->delete(route('education-levels.destroy', $level))
        ->assertRedirect(route('education-levels.index'));

    $this->assertModelMissing($level);
});
