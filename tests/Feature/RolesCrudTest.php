<?php

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Support\Str;

function actingRolesAdmin(): array
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

    return [$admin, $role, $menus];
}

beforeEach(function () {
    [$this->admin, $this->adminRole] = actingRolesAdmin();
    $this->actingAs($this->admin);
});

test('roles index lists roles', function () {
    $this->get(route('roles.index'))->assertOk()->assertSee($this->adminRole->name);
});

test('admin can create a role with a permission matrix', function () {
    $menus = Menu::all();

    $payload = [
        'name' => 'Editor',
        'slug' => 'editor',
        'permissions' => [
            $menus->firstWhere('slug', 'users')->id => ['can_view' => '1', 'can_create' => '1'],
        ],
    ];

    $this->post(route('roles.store'), $payload)->assertRedirect(route('roles.index'));

    $role = Role::query()->where('slug', 'editor')->first();
    $pivot = $role->menus->firstWhere('slug', 'users')->pivot;

    expect((bool) $pivot->can_view)->toBeTrue()
        ->and((bool) $pivot->can_create)->toBeTrue()
        ->and((bool) $pivot->can_update)->toBeFalse()
        ->and($role->menus)->toHaveCount(1);
});

test('saving the matrix updates pivot flags and flushes cache', function () {
    $usersMenu = Menu::query()->where('slug', 'users')->first();

    $viewerRole = Role::factory()->create();

    $this->put(route('roles.update', $viewerRole), [
        'name' => $viewerRole->name,
        'slug' => $viewerRole->slug,
        'permissions' => [
            $usersMenu->id => ['can_view' => '1'],
        ],
    ])->assertRedirect(route('roles.index'));

    expect(app(PermissionService::class)->can($viewerRole->id, 'users', 'view'))->toBeTrue()
        ->and(app(PermissionService::class)->can($viewerRole->id, 'users', 'update'))->toBeFalse();
});

test('cannot save own role matrix removing own update access', function () {
    $rolesMenu = Menu::query()->where('slug', 'roles')->first();

    $this->put(route('roles.update', $this->adminRole), [
        'name' => $this->adminRole->name,
        'slug' => $this->adminRole->slug,
        'permissions' => [
            $rolesMenu->id => ['can_view' => '1'],
        ],
    ])->assertSessionHasErrors('permissions');

    expect((bool) $this->adminRole->fresh()->menus->firstWhere('slug', 'roles')->pivot->can_update)->toBeTrue();
});

test('cannot delete a role still assigned to users', function () {
    $this->delete(route('roles.destroy', $this->adminRole))->assertSessionHasErrors('role');

    $this->assertDatabaseHas('roles', ['id' => $this->adminRole->id]);
});

test('can delete an unassigned role', function () {
    $role = Role::factory()->create();

    $this->delete(route('roles.destroy', $role))->assertRedirect(route('roles.index'));

    $this->assertModelMissing($role);
});

test('name and slug must be unique', function () {
    $this->post(route('roles.store'), [
        'name' => $this->adminRole->name,
        'slug' => 'another-slug',
    ])->assertSessionHasErrors('name');

    $this->post(route('roles.store'), [
        'name' => 'Another Name',
        'slug' => $this->adminRole->slug,
    ])->assertSessionHasErrors('slug');
});

test('roles index sorts by a column', function () {
    $alpha = Role::factory()->create(['name' => 'Alpha Role', 'slug' => 'alpha-role']);
    $zulu = Role::factory()->create(['name' => 'Zulu Role', 'slug' => 'zulu-role']);

    $this->get(route('roles.index', ['sort' => 'name', 'direction' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder(['Zulu Role', 'Alpha Role']);

    $this->get(route('roles.index', ['sort' => 'slug', 'direction' => 'asc']))
        ->assertOk()
        ->assertSeeInOrder([$alpha->slug, $zulu->slug]);
});

test('roles index falls back to default order for unknown sort', function () {
    Role::factory()->create(['name' => 'Zulu Role', 'slug' => 'zulu-role']);
    Role::factory()->create(['name' => 'Alpha Role', 'slug' => 'alpha-role']);

    $this->get(route('roles.index', ['sort' => 'evil', 'direction' => 'drop table']))
        ->assertOk()
        ->assertSeeInOrder(['Alpha Role', 'Zulu Role']);
});

test('roles index search matches name and slug', function () {
    $byName = Role::factory()->create(['name' => 'Quixotic Role', 'slug' => 'quixotic-role']);
    $bySlug = Role::factory()->create(['name' => 'Unrelated', 'slug' => 'special-slug']);
    $unmatched = Role::factory()->create(['name' => 'Nothing Here', 'slug' => 'nowhere']);

    $this->get(route('roles.index', ['search' => 'quixotic']))
        ->assertOk()
        ->assertSee($byName->name)
        ->assertDontSee($unmatched->name);

    $this->get(route('roles.index', ['search' => 'special-slug']))
        ->assertOk()
        ->assertSee($bySlug->name)
        ->assertDontSee($unmatched->name);
});

test('roles index search is case insensitive', function () {
    $role = Role::factory()->create(['name' => 'Uppercase Target', 'slug' => 'uppercase-target']);
    $other = Role::factory()->create(['name' => 'Unrelated Role', 'slug' => 'unrelated-role']);

    $this->get(route('roles.index', ['search' => 'UPPERCASE']))
        ->assertOk()
        ->assertSee($role->name)
        ->assertDontSee($other->name);
});

test('roles index honors per page', function () {
    $this->adminRole->update(['name' => 'Keeper Role', 'slug' => 'keeper-role']);
    Role::factory()->count(15)->sequence(fn ($sequence) => ['name' => 'Role '.str_pad($sequence->index, 2, '0', STR_PAD_LEFT), 'slug' => 'role-'.str_pad($sequence->index, 2, '0', STR_PAD_LEFT)])->create();

    $this->get(route('roles.index', ['per_page' => 10]))
        ->assertOk()
        ->assertSee('Role 08')
        ->assertDontSee('Role 14');

    $this->get(route('roles.index', ['per_page' => 25]))
        ->assertOk()
        ->assertSee('Role 14');

    $this->get(route('roles.index', ['per_page' => 999]))
        ->assertOk()
        ->assertDontSee('Role 14');
});

test('roles index pagination preserves search in links', function () {
    $this->adminRole->update(['name' => 'Keeper Role', 'slug' => 'keeper-role']);
    Role::factory()->count(12)->sequence(fn ($sequence) => ['name' => 'Role '.str_pad($sequence->index, 2, '0', STR_PAD_LEFT), 'slug' => 'role-'.str_pad($sequence->index, 2, '0', STR_PAD_LEFT)])->create();

    $this->get(route('roles.index', ['search' => 'role', 'sort' => 'name', 'direction' => 'desc', 'per_page' => 10, 'page' => 2]))
        ->assertOk()
        ->assertSee('Role 01')
        ->assertSee('Role 00');
});

test('roles index marks the applied sort with a direction indicator', function () {
    $table = Str::between($this->get(route('roles.index'))->assertOk()->getContent(), '<table', '</table>');

    expect($table)->toMatch('/aria-sort="ascending"[^>]*>\s*<a[^>]*>\s*Name/')
        ->and(substr_count($table, 'aria-sort="none"'))->toBe(2)
        ->and(substr_count($table, 'm6 9 6 6 6-6'))->toBe(1)
        ->and(substr_count($table, 'rotate-180'))->toBe(1)
        ->and($table)->toContain('direction=desc');
});
