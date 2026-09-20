<?php

use App\Models\Brand;
use App\Models\Menu;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

function actingBrandAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'brands']);

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
    $this->admin = actingBrandAdmin();
    $this->actingAs($this->admin);
});

test('brands index lists brands', function () {
    Brand::factory()->create(['name' => 'Northwind Trading']);

    $this->get(route('brands.index'))
        ->assertOk()
        ->assertSee('Northwind Trading');
});

test('brands index search matches name and slug', function () {
    $brand = Brand::factory()->create(['name' => 'Quixotic Goods']);
    $unmatched = Brand::factory()->create(['name' => 'Nothing Here']);

    $this->get(route('brands.index', ['search' => 'quixotic']))
        ->assertOk()
        ->assertSee($brand->name)
        ->assertDontSee($unmatched->name);
});

test('admin can create and update a brand', function () {
    $this->post(route('brands.store'), [
        'name' => 'New Brand',
        'slug' => 'new-brand',
        'description' => 'Brand description.',
        'is_active' => '1',
    ])->assertRedirect(route('brands.index'));

    $brand = Brand::query()->where('slug', 'new-brand')->sole();

    $this->put(route('brands.update', $brand), [
        'name' => 'Renamed Brand',
        'slug' => 'renamed-brand',
        'is_active' => '0',
    ])->assertRedirect(route('brands.index'));

    expect($brand->fresh()->name)->toBe('Renamed Brand')
        ->and($brand->fresh()->is_active)->toBeFalse();
});

test('brand slug must be unique', function () {
    $existing = Brand::factory()->create(['slug' => 'taken-brand']);

    $this->post(route('brands.store'), [
        'name' => 'Another Brand',
        'slug' => $existing->slug,
    ])->assertSessionHasErrors('slug');
});

test('a brand used by products cannot be deleted', function () {
    $brand = Brand::factory()->create();
    Product::factory()->create(['brand_id' => $brand->id]);

    $this->delete(route('brands.destroy', $brand))
        ->assertSessionHasErrors('brand');

    $this->assertDatabaseHas('brands', ['id' => $brand->id]);
});

test('an unused brand can be deleted', function () {
    $brand = Brand::factory()->create();

    $this->delete(route('brands.destroy', $brand))
        ->assertRedirect(route('brands.index'));

    $this->assertModelMissing($brand);
});
