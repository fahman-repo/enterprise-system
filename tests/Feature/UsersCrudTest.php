<?php

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Support\Str;

function actingAdmin(): array
{
    $role = Role::factory()->create();
    $menus = collect([
        Menu::factory()->create(['slug' => 'users']),
        Menu::factory()->create(['slug' => 'roles']),
        Menu::factory()->create(['slug' => 'menus']),
    ]);

    $role->menus()->sync($menus->mapWithKeys(fn (Menu $menu) => [
        $menu->id => ['can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true],
    ]));

    app(PermissionService::class)->flush();

    $admin = User::factory()->create(['role_id' => $role->id]);

    return [$admin, $role];
}

beforeEach(function () {
    [$this->admin] = actingAdmin();
    $this->actingAs($this->admin);
});

test('users index lists users', function () {
    $this->get(route('users.index'))
        ->assertOk()
        ->assertSee($this->admin->email);
});

test('users index filters by status', function () {
    $active = User::factory()->create(['name' => 'Active Person', 'email' => 'active-person@example.com']);
    $inactive = User::factory()->inactive()->create(['name' => 'Dormant Person', 'email' => 'dormant-person@example.com']);

    $this->get(route('users.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee($inactive->email)
        ->assertDontSee($active->email);
});

test('admin can create a user', function () {
    $role = Role::factory()->create();

    $this->post(route('users.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'super-secret',
        'role_id' => $role->id,
        'is_active' => '1',
    ])->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'role_id' => $role->id, 'is_active' => true]);
});

test('admin can update a user without changing the password', function () {
    $user = User::factory()->create();
    $oldHash = $user->password;

    $this->put(route('users.update', $user), [
        'name' => 'Renamed',
        'email' => $user->email,
        'role_id' => $this->admin->role_id,
        'is_active' => '0',
    ])->assertRedirect(route('users.index'));

    expect($user->fresh()->name)->toBe('Renamed')
        ->and($user->fresh()->password)->toBe($oldHash)
        ->and($user->fresh()->is_active)->toBeFalse();
});

test('admin cannot delete own account', function () {
    $this->delete(route('users.destroy', $this->admin))->assertSessionHasErrors('user');

    $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
});

test('admin can delete another user', function () {
    $other = User::factory()->create();

    $this->delete(route('users.destroy', $other))->assertRedirect(route('users.index'));

    $this->assertModelMissing($other);
});

test('email must be unique', function () {
    $this->post(route('users.store'), [
        'name' => 'Dup',
        'email' => $this->admin->email,
        'password' => 'password123',
    ])->assertSessionHasErrors('email');
});

test('password is required and hashed on create', function () {
    $this->post(route('users.store'), [
        'name' => 'No Pass',
        'email' => 'nopass@example.com',
    ])->assertSessionHasErrors('password');

    $this->post(route('users.store'), [
        'name' => 'With Pass',
        'email' => 'withpass@example.com',
        'password' => 'password123',
    ])->assertRedirect(route('users.index'));

    $created = User::query()->where('email', 'withpass@example.com')->first();

    expect($created->password)->not->toBe('password123');
});

test('users index sorts by a column', function () {
    $alpha = User::factory()->create(['name' => 'Alpha Person', 'email' => 'alpha-person@example.com']);
    $zulu = User::factory()->create(['name' => 'Zulu Person', 'email' => 'zulu-person@example.com']);

    $this->get(route('users.index', ['sort' => 'name', 'direction' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder(['Zulu Person', 'Alpha Person']);

    $this->get(route('users.index', ['sort' => 'email', 'direction' => 'asc']))
        ->assertOk()
        ->assertSeeInOrder([$alpha->email, $zulu->email]);
});

test('users index falls back to default order for unknown sort', function () {
    $zulu = User::factory()->create(['name' => 'Zulu Person']);
    $alpha = User::factory()->create(['name' => 'Alpha Person']);

    $this->get(route('users.index', ['sort' => 'evil', 'direction' => 'drop table']))
        ->assertOk()
        ->assertSeeInOrder(['Alpha Person', 'Zulu Person']);
});

test('users index sorts by role column', function () {
    $alphaRole = Role::factory()->create(['name' => 'Alpha', 'slug' => 'alpha-sort']);
    $zuluRole = Role::factory()->create(['name' => 'Zulu', 'slug' => 'zulu-sort']);
    User::factory()->create(['name' => 'Alpha User', 'email' => 'aaa-role-probe@example.com', 'role_id' => $alphaRole->id]);
    User::factory()->create(['name' => 'Zulu User', 'email' => 'zzz-role-probe@example.com', 'role_id' => $zuluRole->id]);
    User::factory()->create(['name' => 'No Role User', 'email' => 'mmm-role-probe@example.com', 'role_id' => null]);

    $this->get(route('users.index', ['sort' => 'role', 'direction' => 'asc']))
        ->assertOk()
        ->assertSeeInOrder(['aaa-role-probe@example.com', 'zzz-role-probe@example.com'])
        ->assertSee('No Role User');

    $this->get(route('users.index', ['sort' => 'role', 'direction' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder(['zzz-role-probe@example.com', 'aaa-role-probe@example.com']);
});

test('users index treats invalid direction as ascending', function () {
    User::factory()->create(['name' => 'Alpha Person', 'email' => 'aaa-sort-probe@example.com']);
    User::factory()->create(['name' => 'Zulu Person', 'email' => 'zzz-sort-probe@example.com']);

    $this->get(route('users.index', ['sort' => 'email', 'direction' => 'drop table']))
        ->assertOk()
        ->assertSeeInOrder(['aaa-sort-probe@example.com', 'zzz-sort-probe@example.com']);
});

test('users index search matches name, email and role name', function () {
    $role = Role::factory()->create(['name' => 'Wachtmaster', 'slug' => 'wachtmaster']);
    $byName = User::factory()->create(['name' => 'Quixotic Person']);
    $byEmail = User::factory()->create(['name' => 'Unrelated', 'email' => 'special@example.com']);
    $byRole = User::factory()->create(['name' => 'Unrelated Too', 'role_id' => $role->id]);
    $unmatched = User::factory()->create(['name' => 'Nothing Here', 'email' => 'nowhere@example.com']);

    $this->get(route('users.index', ['search' => 'quixotic']))
        ->assertOk()
        ->assertSee($byName->name)
        ->assertDontSee($unmatched->name);

    $this->get(route('users.index', ['search' => 'special@example.com']))
        ->assertOk()
        ->assertSee($byEmail->name)
        ->assertDontSee($unmatched->name);

    $this->get(route('users.index', ['search' => 'wachtmaster']))
        ->assertOk()
        ->assertSee($byRole->name)
        ->assertDontSee($unmatched->name);
});

test('users index search is case insensitive', function () {
    $user = User::factory()->create(['name' => 'Uppercase Target']);
    $other = User::factory()->create(['name' => 'Unrelated Person']);

    $this->get(route('users.index', ['search' => 'UPPERCASE']))
        ->assertOk()
        ->assertSee($user->name)
        ->assertDontSee($other->name);
});

test('users index honors per page', function () {
    $this->admin->update(['name' => 'Zzz Admin']);
    User::query()->whereKeyNot($this->admin->id)->delete();
    User::factory()->count(15)->sequence(fn ($sequence) => ['name' => 'Person '.str_pad($sequence->index, 2, '0', STR_PAD_LEFT), 'email' => 'person'.str_pad($sequence->index, 2, '0', STR_PAD_LEFT).'@example.com'])->create();

    $this->get(route('users.index', ['per_page' => 10]))
        ->assertOk()
        ->assertSee('Person 09')
        ->assertDontSee('Person 14');

    $this->get(route('users.index', ['per_page' => 25]))
        ->assertOk()
        ->assertSee('Person 14');

    $this->get(route('users.index', ['per_page' => 999]))
        ->assertOk()
        ->assertDontSee('Person 14');
});

test('users index pagination preserves search in links', function () {
    $this->admin->update(['name' => 'Zzz Admin']);
    User::query()->whereKeyNot($this->admin->id)->delete();
    User::factory()->count(12)->sequence(fn ($sequence) => ['name' => 'Person '.str_pad($sequence->index, 2, '0', STR_PAD_LEFT), 'email' => 'person'.str_pad($sequence->index, 2, '0', STR_PAD_LEFT).'@example.com'])->create();

    $this->get(route('users.index', ['search' => 'person', 'sort' => 'name', 'direction' => 'desc', 'per_page' => 10, 'page' => 2]))
        ->assertOk()
        ->assertSee('Person 01')
        ->assertSee('Person 00');
});

test('users index marks the applied sort with a direction indicator', function () {
    $table = Str::between($this->get(route('users.index'))->assertOk()->getContent(), '<table', '</table>');

    expect($table)->toMatch('/aria-sort="ascending"[^>]*>\s*<a[^>]*>\s*Name/')
        ->and(substr_count($table, 'aria-sort="none"'))->toBe(3)
        ->and(substr_count($table, 'm6 9 6 6 6-6'))->toBe(1)
        ->and(substr_count($table, 'rotate-180'))->toBe(1)
        ->and($table)->toContain('direction=desc');

    $table = Str::between(
        $this->get(route('users.index', ['sort' => 'email', 'direction' => 'desc']))->assertOk()->getContent(),
        '<table', '</table>'
    );

    expect($table)->toMatch('/aria-sort="descending"[^>]*>\s*<a[^>]*>\s*Email/')
        ->and(substr_count($table, 'aria-sort="none"'))->toBe(3)
        ->and(substr_count($table, 'm6 9 6 6 6-6'))->toBe(1)
        ->and(substr_count($table, 'rotate-180'))->toBe(0);
});
