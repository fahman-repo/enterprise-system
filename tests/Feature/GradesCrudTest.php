<?php

use App\Models\Employee;
use App\Models\Grade;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

function actingGradeAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'grades']);

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
    $this->actingAs(actingGradeAdmin());
});

test('grades index lists grades', function () {
    Grade::factory()->create(['name' => 'Quixotic Grade']);

    $this->get(route('grades.index'))
        ->assertOk()
        ->assertSee('Quixotic Grade');
});

test('grades index filters by status', function () {
    $active = Grade::factory()->create(['name' => 'Active Grade']);
    $inactive = Grade::factory()->inactive()->create(['name' => 'Inactive Grade']);

    $this->get(route('grades.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee($inactive->name)
        ->assertDontSee($active->name);
});

test('admin can create and update a grade', function () {
    $this->post(route('grades.store'), [
        'name' => 'New Grade',
        'level' => 3,
        'is_active' => '1',
    ])->assertRedirect(route('grades.index'));

    $grade = Grade::query()->where('name', 'New Grade')->sole();

    $this->put(route('grades.update', $grade), [
        'name' => 'Renamed Grade',
        'level' => 5,
        'is_active' => '0',
    ])->assertRedirect(route('grades.index'));

    expect($grade->fresh()->name)->toBe('Renamed Grade')
        ->and($grade->fresh()->level)->toBe(5)
        ->and($grade->fresh()->is_active)->toBeFalse();
});

test('grade name must be unique', function () {
    $existing = Grade::factory()->create(['name' => 'Taken Grade']);

    $this->post(route('grades.store'), [
        'name' => $existing->name,
        'level' => 1,
    ])->assertSessionHasErrors('name');
});

test('a grade assigned to employees cannot be deleted', function () {
    $grade = Grade::factory()->create();
    Employee::factory()->create(['grade_id' => $grade->id]);

    $this->delete(route('grades.destroy', $grade))
        ->assertSessionHasErrors('grade');

    $this->assertDatabaseHas('grades', ['id' => $grade->id]);
});

test('an unused grade can be deleted', function () {
    $grade = Grade::factory()->create();

    $this->delete(route('grades.destroy', $grade))
        ->assertRedirect(route('grades.index'));

    $this->assertModelMissing($grade);
});
