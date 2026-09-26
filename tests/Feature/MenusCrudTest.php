<?php

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Support\Str;

function actingMenusAdmin(): array
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

/**
 * The menus.index route requires the seeded 'menus' menu permission rows,
 * so replace them with deterministic rows instead of deleting them. Menus
 * inserted by migrations are pushed out of the sort range the pagination
 * tests rely on, so those assertions stay independent of seeded data.
 */
function neutralizeMenusSeed(): void
{
    Menu::query()->update([
        'name' => 'Keeper Menu',
        'sort_order' => 500,
    ]);
}

beforeEach(function () {
    [$this->admin] = actingMenusAdmin();
    $this->actingAs($this->admin);
});

test('menus index lists menus', function () {
    $this->get(route('menus.index'))
        ->assertOk()
        ->assertSee('users');
});

test('menus index filters by status', function () {
    $active = Menu::factory()->create(['name' => 'Active Menu', 'slug' => 'active-menu']);
    $inactive = Menu::factory()->inactive()->create(['name' => 'Inactive Menu', 'slug' => 'inactive-menu']);

    $this->get(route('menus.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee($inactive->name)
        ->assertDontSee($active->name);
});

test('admin can create a menu item', function () {
    $this->post(route('menus.store'), [
        'name' => 'Reports',
        'slug' => 'reports',
        'icon' => 'users',
        'route_name' => 'dashboard',
        'sort_order' => 5,
        'is_active' => '1',
    ])->assertRedirect(route('menus.index'));

    $this->assertDatabaseHas('menus', [
        'slug' => 'reports',
        'route_name' => 'dashboard',
        'sort_order' => 5,
    ]);
});

test('route_name must exist in the router', function () {
    $this->post(route('menus.store'), [
        'name' => 'Broken',
        'slug' => 'broken',
        'route_name' => 'does.not.exist',
    ])->assertSessionHasErrors('route_name');
});

test('slug must be unique', function () {
    $this->post(route('menus.store'), [
        'name' => 'Dup',
        'slug' => 'users',
    ])->assertSessionHasErrors('slug');
});

test('icon must come from the known icon set', function () {
    $this->post(route('menus.store'), [
        'name' => 'Bad Icon',
        'slug' => 'bad-icon',
        'icon' => 'not-an-icon',
    ])->assertSessionHasErrors('icon');
});

test('parent options exclude self and descendants', function () {
    $parent = Menu::query()->where('slug', 'users')->first();
    $child = Menu::factory()->create(['slug' => 'child', 'parent_id' => $parent->id]);
    $grandchild = Menu::factory()->create(['slug' => 'grandchild', 'parent_id' => $child->id]);

    $this->get(route('menus.edit', $parent))
        ->assertOk()
        ->assertSee('Edit menu item');

    $response = $this->get(route('menus.edit', $child));

    $response->assertOk();

    expect(Menu::query()->where('slug', 'child')->first()->descendantIds()->all())
        ->toContain($grandchild->id);
});

test('deleting a menu reparents children and cascades pivot rows', function () {
    $parent = Menu::factory()->create(['slug' => 'parent']);
    $child = Menu::factory()->create(['slug' => 'menu-child', 'parent_id' => $parent->id]);

    $role = Role::factory()->create();
    $role->menus()->syncWithoutDetaching([$parent->id => ['can_view' => true]]);

    $this->delete(route('menus.destroy', $parent))->assertRedirect(route('menus.index'));

    expect($child->fresh()->parent_id)->toBeNull()
        ->and($role->menus()->count())->toBe(0);
});

test('inactive menus are excluded from the sidebar tree', function () {
    $role = Role::factory()->create();
    $menu = Menu::factory()->inactive()->create(['slug' => 'hidden']);
    $role->menus()->syncWithoutDetaching([$menu->id => ['can_view' => true]]);
    app(PermissionService::class)->flush();

    $user = User::factory()->create(['role_id' => $role->id]);

    expect(app(PermissionService::class)->sidebarForUser($user))->toHaveCount(0);
});

test('menus index sorts by a column', function () {
    $alpha = Menu::factory()->create(['name' => 'Alpha', 'slug' => 'alpha', 'sort_order' => 1]);
    $zulu = Menu::factory()->create(['name' => 'Zulu', 'slug' => 'zulu', 'sort_order' => 2]);

    $this->get(route('menus.index', ['sort' => 'name', 'direction' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder(['Zulu', 'Alpha']);

    $this->get(route('menus.index', ['sort' => 'sort_order', 'direction' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder([$zulu->name, $alpha->name]);
});

test('menus index falls back to default order for unknown sort', function () {
    $last = Menu::factory()->create(['name' => 'Zulu', 'slug' => 'zulu', 'sort_order' => 5]);
    $first = Menu::factory()->create(['name' => 'Alpha', 'slug' => 'alpha', 'sort_order' => 2]);

    $this->get(route('menus.index', ['sort' => 'evil', 'direction' => 'drop table']))
        ->assertOk()
        ->assertSeeInOrder([$first->name, $last->name]);
});

test('menus index search matches name, slug, route and parent name', function () {
    $parent = Menu::factory()->create(['name' => 'Wachtmother', 'slug' => 'wachtmother']);
    $byName = Menu::factory()->create(['name' => 'Quixotic', 'slug' => 'quixotic-item']);
    $bySlug = Menu::factory()->create(['name' => 'Unrelated', 'slug' => 'special-slug']);
    $byRoute = Menu::factory()->create(['name' => 'Unrelated Too', 'slug' => 'another', 'route_name' => 'dashboard']);
    $byParent = Menu::factory()->create(['name' => 'Unrelated Three', 'slug' => 'third', 'parent_id' => $parent->id]);
    $unmatched = Menu::factory()->create(['name' => 'Nothing Here', 'slug' => 'nowhere']);

    $this->get(route('menus.index', ['search' => 'quixotic']))
        ->assertOk()
        ->assertSee($byName->name)
        ->assertDontSee($unmatched->name);

    $this->get(route('menus.index', ['search' => 'special-slug']))
        ->assertOk()
        ->assertSee($bySlug->name)
        ->assertDontSee($unmatched->name);

    $this->get(route('menus.index', ['search' => 'dashboard']))
        ->assertOk()
        ->assertSee($byRoute->name)
        ->assertDontSee($unmatched->name);

    $this->get(route('menus.index', ['search' => 'wachtmother']))
        ->assertOk()
        ->assertSee($byParent->name)
        ->assertDontSee($unmatched->name);
});

test('menus index search is case insensitive', function () {
    $menu = Menu::factory()->create(['name' => 'Uppercase Target', 'slug' => 'uppercase-target']);
    $other = Menu::factory()->create(['name' => 'Unrelated', 'slug' => 'unrelated']);

    $this->get(route('menus.index', ['search' => 'UPPERCASE']))
        ->assertOk()
        ->assertSee($menu->name)
        ->assertDontSee($other->name);
});

test('menus index honors per page', function () {
    neutralizeMenusSeed();
    Menu::factory()->count(15)->sequence(fn ($sequence) => ['name' => 'Menu '.$sequence->index, 'slug' => 'menu-'.$sequence->index, 'sort_order' => $sequence->index])->create();

    $this->get(route('menus.index', ['per_page' => 10]))
        ->assertOk()
        ->assertSee('Menu 9')
        ->assertDontSee('Menu 14');

    $this->get(route('menus.index', ['per_page' => 25]))
        ->assertOk()
        ->assertSee('Menu 14');

    $this->get(route('menus.index', ['per_page' => 999]))
        ->assertOk()
        ->assertDontSee('Menu 14');
});

test('menus index pagination preserves search in links', function () {
    neutralizeMenusSeed();
    Menu::factory()->count(12)->sequence(fn ($sequence) => ['name' => 'Item '.str_pad($sequence->index, 2, '0', STR_PAD_LEFT), 'slug' => 'item-'.str_pad($sequence->index, 2, '0', STR_PAD_LEFT), 'sort_order' => 100 - $sequence->index])->create();

    $this->get(route('menus.index', ['search' => 'item', 'sort' => 'name', 'direction' => 'desc', 'per_page' => 10, 'page' => 2]))
        ->assertOk()
        ->assertSee('Item 01')
        ->assertSee('Item 00');
});

test('menus index marks the applied sort with a direction indicator', function () {
    $table = Str::between($this->get(route('menus.index'))->assertOk()->getContent(), '<table', '</table>');

    expect($table)->toMatch('/aria-sort="ascending"[^>]*>\s*<a[^>]*>\s*Sort/')
        ->and(substr_count($table, 'aria-sort="none"'))->toBe(5)
        ->and(substr_count($table, 'm6 9 6 6 6-6'))->toBe(1)
        ->and(substr_count($table, 'rotate-180'))->toBe(1)
        ->and($table)->toContain('direction=desc');
});
