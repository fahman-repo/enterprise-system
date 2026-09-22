<?php

use App\Models\Category;
use App\Models\Menu;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

function actingCategoryAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'categories']);

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
    $this->admin = actingCategoryAdmin();
    $this->actingAs($this->admin);
});

test('categories index lists categories with parent and product counts', function () {
    $parent = Category::factory()->create(['name' => 'Hardware']);
    $child = Category::factory()->create(['name' => 'Laptops', 'parent_id' => $parent->id]);
    Product::factory()->count(2)->create(['category_id' => $child->id]);

    $this->get(route('categories.index'))
        ->assertOk()
        ->assertSee('Hardware')
        ->assertSee('Laptops');
});

test('categories index search matches name, slug and parent name', function () {
    $parent = Category::factory()->create(['name' => 'Vehicles']);
    $child = Category::factory()->create(['name' => 'Quixotic Scooters', 'parent_id' => $parent->id]);
    $unmatched = Category::factory()->create(['name' => 'Nothing Here']);

    $this->get(route('categories.index', ['search' => 'quixotic']))
        ->assertOk()
        ->assertSee($child->name)
        ->assertDontSee($unmatched->name);

    $this->get(route('categories.index', ['search' => 'Vehicles']))
        ->assertOk()
        ->assertSee($child->name);
});

test('categories index filters by status', function () {
    $active = Category::factory()->create(['name' => 'Active Category']);
    $inactive = Category::factory()->inactive()->create(['name' => 'Inactive Category']);

    $this->get(route('categories.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee($inactive->name)
        ->assertDontSee($active->name);
});

test('admin can create a category', function () {
    $parent = Category::factory()->create();

    $this->post(route('categories.store'), [
        'name' => 'New Category',
        'slug' => 'new-category',
        'parent_id' => $parent->id,
        'description' => 'A test category.',
        'sort_order' => '7',
        'is_active' => '1',
    ])->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('categories', [
        'slug' => 'new-category',
        'parent_id' => $parent->id,
        'sort_order' => 7,
    ]);
});

test('category slug must be unique', function () {
    $existing = Category::factory()->create(['slug' => 'taken-slug']);

    $this->post(route('categories.store'), [
        'name' => 'Another',
        'slug' => $existing->slug,
    ])->assertSessionHasErrors('slug');
});

test('category name and slug are required', function () {
    $this->post(route('categories.store'), [])
        ->assertSessionHasErrors(['name', 'slug']);
});

test('a category cannot be its own parent or a descendant parent', function () {
    $category = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $category->id]);

    $this->put(route('categories.update', $category), [
        'name' => $category->name,
        'slug' => $category->slug,
        'parent_id' => $category->id,
    ])->assertSessionHasErrors('parent_id');

    $this->put(route('categories.update', $category), [
        'name' => $category->name,
        'slug' => $category->slug,
        'parent_id' => $child->id,
    ])->assertSessionHasErrors('parent_id');
});

test('deleting a category moves its children up one level', function () {
    $grandParent = Category::factory()->create();
    $parent = Category::factory()->create(['parent_id' => $grandParent->id]);
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    $this->delete(route('categories.destroy', $parent))
        ->assertRedirect(route('categories.index'));

    $this->assertSoftDeleted($parent);
    expect($child->fresh()->parent_id)->toBe($grandParent->id);
});

test('a category used by products cannot be deleted', function () {
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id]);

    $this->delete(route('categories.destroy', $category))
        ->assertSessionHasErrors('category');

    $this->assertNotSoftDeleted($category);
});
