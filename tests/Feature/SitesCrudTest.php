<?php

use App\Models\Employee;
use App\Models\Menu;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use App\Services\PermissionService;

function actingSiteAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'sites']);

    $role->menus()->sync([
        $menu->id => ['can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true],
    ]);

    app(PermissionService::class)->flush();

    return User::factory()->create(['role_id' => $role->id]);
}

beforeEach(function () {
    $this->actingAs(actingSiteAdmin());
});

test('sites index lists sites with their type and parent', function () {
    $company = Site::factory()->company()->create(['name' => 'Nusantara Industri']);
    $branch = Site::factory()->branch()->under($company)->create(['name' => 'Jakarta Branch']);

    $this->get(route('sites.index'))
        ->assertOk()
        ->assertSee(route('sites.show', $company), false)
        ->assertSee(route('sites.show', $branch), false)
        ->assertSee('Company')
        ->assertSee('Branch');
});

test('sites index search matches code, name, city, province, phone and email', function () {
    $byCode = Site::factory()->create(['code' => 'SIT-SEARCH-1']);
    $byName = Site::factory()->create(['name' => 'Quixotic Warehouse']);
    $byCity = Site::factory()->create(['city' => 'Cikarang']);
    $byProvince = Site::factory()->create(['province' => 'Jawa Tengah']);
    $byPhone = Site::factory()->create(['phone' => '08999888777']);
    $byEmail = Site::factory()->create(['email' => 'site.contact@example.test']);
    $unmatched = Site::factory()->create(['name' => 'Nothing Here']);

    $cases = [
        'SIT-SEARCH-1' => $byCode,
        'quixotic' => $byName,
        'cikarang' => $byCity,
        'Jawa Tengah' => $byProvince,
        '08999888777' => $byPhone,
        'site.contact' => $byEmail,
    ];

    foreach ($cases as $term => $expected) {
        $this->get(route('sites.index', ['search' => $term]))
            ->assertOk()
            ->assertSee(route('sites.show', $expected), false)
            ->assertDontSee(route('sites.show', $unmatched), false);
    }
});

test('sites index filters by type, parent and status', function () {
    $company = Site::factory()->company()->create(['name' => 'Filter Company']);
    $branch = Site::factory()->branch()->under($company)->create(['name' => 'Child Branch']);
    $standalone = Site::factory()->warehouse()->create(['name' => 'Loose Warehouse']);
    $inactive = Site::factory()->inactive()->branch()->create(['name' => 'Gone Branch']);

    $this->get(route('sites.index', ['type' => 'warehouse']))
        ->assertOk()
        ->assertSee(route('sites.show', $standalone), false)
        ->assertDontSee(route('sites.show', $branch), false);

    $this->get(route('sites.index', ['parent_id' => $company->id]))
        ->assertOk()
        ->assertSee(route('sites.show', $branch), false)
        ->assertDontSee(route('sites.show', $standalone), false);

    $this->get(route('sites.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee(route('sites.show', $inactive), false)
        ->assertDontSee(route('sites.show', $branch), false);

    $this->get(route('sites.index', ['type' => 'warehouse', 'parent_id' => $company->id]))
        ->assertOk()
        ->assertSee(route('sites.show', $standalone), false)
        ->assertDontSee(route('sites.show', $branch), false);
});

test('admin can create a site with a generated code', function () {
    $this->post(route('sites.store'), [
        'name' => 'Cikarang Warehouse',
        'type' => 'warehouse',
        'city' => 'Cikarang',
        'is_active' => '1',
    ])->assertRedirect(route('sites.index'));

    $site = Site::query()->where('name', 'Cikarang Warehouse')->sole();

    expect($site->code)->toBe('SIT-00001')
        ->and($site->is_active)->toBeTrue();
});

test('admin can create a site with an explicit code and a parent', function () {
    $company = Site::factory()->company()->create();

    $this->post(route('sites.store'), [
        'code' => 'SIT-CUSTOM',
        'name' => 'Head Office Tower',
        'type' => 'building',
        'parent_id' => $company->id,
        'is_active' => '1',
    ])->assertRedirect(route('sites.index'));

    expect(Site::query()->where('code', 'SIT-CUSTOM')->sole()->parent_id)->toBe($company->id);
});

test('admin can update a site', function () {
    $site = Site::factory()->create(['name' => 'Old Name']);

    $this->put(route('sites.update', $site), [
        'name' => 'Renamed Site',
        'type' => 'factory',
        'is_active' => '0',
    ])->assertRedirect(route('sites.index'));

    expect($site->fresh()->name)->toBe('Renamed Site')
        ->and($site->fresh()->type)->toBe('factory')
        ->and($site->fresh()->is_active)->toBeFalse();
});

test('site code must be unique', function () {
    $existing = Site::factory()->create(['code' => 'SIT-TAKEN']);

    $this->post(route('sites.store'), [
        'code' => $existing->code,
        'name' => 'Another Site',
        'type' => 'branch',
    ])->assertSessionHasErrors('code');
});

test('a site type outside the allowed set is rejected', function () {
    $this->post(route('sites.store'), [
        'name' => 'Invalid Type Site',
        'type' => 'island',
    ])->assertSessionHasErrors('type');

    $this->assertDatabaseCount('sites', 0);
});

test('a site cannot be nested under a parent category that cannot hold it', function () {
    $branch = Site::factory()->branch()->create();

    $this->post(route('sites.store'), [
        'name' => 'Building Under A Branch',
        'type' => 'building',
        'parent_id' => $branch->id,
    ])->assertSessionHasErrors('parent_id');

    $this->assertDatabaseCount('sites', 1);
});

test('a site cannot be its own parent nor nested under its own descendant', function () {
    $company = Site::factory()->company()->create();
    $building = Site::factory()->building()->under($company)->create();

    $this->put(route('sites.update', $company), [
        'name' => $company->name,
        'type' => 'company',
        'parent_id' => $building->id,
    ])->assertSessionHasErrors('parent_id');

    $this->put(route('sites.update', $building), [
        'name' => $building->name,
        'type' => 'building',
        'parent_id' => $building->id,
    ])->assertSessionHasErrors('parent_id');

    expect($company->fresh()->parent_id)->toBeNull()
        ->and($building->fresh()->parent_id)->toBe($company->id);
});

test('admin can soft delete an unused site', function () {
    $site = Site::factory()->create(['name' => 'Doomed Site']);

    $this->delete(route('sites.destroy', $site))
        ->assertRedirect(route('sites.index'));

    $this->assertSoftDeleted('sites', ['id' => $site->id]);

    $this->get(route('sites.index'))->assertDontSee('Doomed Site');
});

test('a site with child sites cannot be deleted', function () {
    $company = Site::factory()->company()->create();
    Site::factory()->building()->under($company)->create();

    $this->delete(route('sites.destroy', $company))->assertSessionHasErrors('site');

    $this->assertDatabaseHas('sites', ['id' => $company->id]);
});

test('a site assigned to employees cannot be deleted', function () {
    $site = Site::factory()->create();
    Employee::factory()->create(['site_id' => $site->id]);

    $this->delete(route('sites.destroy', $site))->assertSessionHasErrors('site');

    $this->assertDatabaseHas('sites', ['id' => $site->id]);
});

test('sites show renders the details, children and employees', function () {
    $company = Site::factory()->company()->create(['name' => 'Nusantara Industri']);
    $branch = Site::factory()->branch()->under($company)->create([
        'name' => 'Jakarta Branch',
        'description' => 'Sales and service branch',
    ]);
    $employee = Employee::factory()->create(['site_id' => $branch->id]);

    $this->get(route('sites.show', $branch))
        ->assertOk()
        ->assertSee('Jakarta Branch')
        ->assertSee($branch->code)
        ->assertSee('Sales and service branch')
        ->assertSee($company->name)
        ->assertSee($employee->name);
});
