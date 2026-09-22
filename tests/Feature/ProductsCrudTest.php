<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function actingProductCatalogAdmin(): User
{
    $role = Role::factory()->create();
    $menus = collect(['products', 'categories', 'brands', 'units'])
        ->map(fn (string $slug) => Menu::factory()->create(['slug' => $slug]));

    $role->menus()->sync($menus->mapWithKeys(fn (Menu $menu) => [
        $menu->id => ['can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true],
    ]));

    app(PermissionService::class)->flush();

    return User::factory()->create(['role_id' => $role->id]);
}

function fakeProductImage(string $name = 'widget.png'): UploadedFile
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    return UploadedFile::fake()->createWithContent($name, $png);
}

beforeEach(function () {
    $this->admin = actingProductCatalogAdmin();
    $this->actingAs($this->admin);
});

test('products index lists products', function () {
    $product = Product::factory()->create(['name' => 'Super Widget']);

    $this->get(route('products.index'))
        ->assertOk()
        ->assertSee('Super Widget')
        ->assertSee($product->sku);
});

test('products index search matches name, sku, barcode, category and brand', function () {
    $category = Category::factory()->create(['name' => 'Zephyr Category']);
    $brand = Brand::factory()->create(['name' => 'Xanthic Brand']);

    $byName = Product::factory()->create(['name' => 'Quixotic Thing']);
    $bySku = Product::factory()->create(['sku' => 'SKU-SPECIAL-1']);
    $byBarcode = Product::factory()->create(['barcode' => '9990001112223']);
    $byCategory = Product::factory()->create(['category_id' => $category->id, 'name' => 'Unrelated Alpha']);
    $byBrand = Product::factory()->create(['brand_id' => $brand->id, 'name' => 'Unrelated Beta']);
    $unmatched = Product::factory()->create(['name' => 'Nothing Here']);

    $this->get(route('products.index', ['search' => 'quixotic']))
        ->assertOk()
        ->assertSee($byName->name)
        ->assertDontSee($unmatched->name);

    $this->get(route('products.index', ['search' => 'SKU-SPECIAL-1']))
        ->assertOk()
        ->assertSee($bySku->sku);

    $this->get(route('products.index', ['search' => '9990001112223']))
        ->assertOk()
        ->assertSee($byBarcode->barcode);

    $this->get(route('products.index', ['search' => 'Zephyr']))
        ->assertOk()
        ->assertSee($byCategory->name);

    $this->get(route('products.index', ['search' => 'Xanthic']))
        ->assertOk()
        ->assertSee($byBrand->name);
});

test('products index filters by a category including its descendants', function () {
    $parent = Category::factory()->create(['name' => 'Parent Cat']);
    $child = Category::factory()->create(['name' => 'Child Cat', 'parent_id' => $parent->id]);

    $inChild = Product::factory()->create(['category_id' => $child->id, 'name' => 'Child Product']);
    $elsewhere = Product::factory()->create(['name' => 'Other Product']);

    $this->get(route('products.index', ['category_id' => $parent->id]))
        ->assertOk()
        ->assertSee($inChild->name)
        ->assertDontSee($elsewhere->name);
});

test('products index filters by brand, status and stock', function () {
    $brand = Brand::factory()->create(['name' => 'Filter Brand']);

    $byBrand = Product::factory()->create(['brand_id' => $brand->id, 'name' => 'Brand Product']);
    $inactive = Product::factory()->inactive()->create(['name' => 'Inactive Product']);
    $outOfStock = Product::factory()->outOfStock()->create(['name' => 'Out Product']);
    $lowStock = Product::factory()->lowStock()->create(['name' => 'Low Product']);
    $inStock = Product::factory()->create(['name' => 'Stocked Product', 'stock_quantity' => 50]);

    $this->get(route('products.index', ['brand_id' => $brand->id]))
        ->assertOk()
        ->assertSee($byBrand->name)
        ->assertDontSee($inStock->name);

    $this->get(route('products.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee($inactive->name)
        ->assertDontSee($inStock->name);

    $this->get(route('products.index', ['stock' => 'out']))
        ->assertOk()
        ->assertSee($outOfStock->name)
        ->assertDontSee($inStock->name)
        ->assertDontSee($lowStock->name);

    $this->get(route('products.index', ['stock' => 'low']))
        ->assertOk()
        ->assertSee($lowStock->name)
        ->assertDontSee($outOfStock->name);
});

test('products index ignores status when a category filter is applied', function () {
    $category = Category::factory()->create();
    $inCategory = Product::factory()->create(['category_id' => $category->id, 'name' => 'Categorised Product']);
    $inactive = Product::factory()->inactive()->create(['name' => 'Inactive Elsewhere']);

    $this->get(route('products.index', ['category_id' => $category->id, 'status' => 'inactive']))
        ->assertOk()
        ->assertSee($inCategory->name)
        ->assertDontSee($inactive->name);
});

test('products index applies only the first matching filter', function () {
    $category = Category::factory()->create();
    $inCategory = Product::factory()->create(['category_id' => $category->id, 'name' => 'In First Category']);

    $brand = Brand::factory()->create();
    $byBrand = Product::factory()->create(['brand_id' => $brand->id, 'name' => 'In Branded Category']);

    $this->get(route('products.index', [
        'category_id' => $category->id,
        'brand_id' => $brand->id,
    ]))
        ->assertOk()
        ->assertSee($inCategory->name)
        ->assertDontSee($byBrand->name);
});

test('products index sorts by a column', function () {
    Product::factory()->create(['name' => 'Alpha Product', 'selling_price' => 10]);
    Product::factory()->create(['name' => 'Zulu Product', 'selling_price' => 99]);

    $this->get(route('products.index', ['sort' => 'name', 'direction' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder(['Zulu Product', 'Alpha Product']);

    $this->get(route('products.index', ['sort' => 'selling_price', 'direction' => 'asc']))
        ->assertOk()
        ->assertSeeInOrder(['Alpha Product', 'Zulu Product']);
});

test('products index falls back to default order for unknown sort', function () {
    Product::factory()->create(['name' => 'Zulu Product']);
    Product::factory()->create(['name' => 'Alpha Product']);

    $this->get(route('products.index', ['sort' => 'evil', 'direction' => 'drop table']))
        ->assertOk()
        ->assertSeeInOrder(['Alpha Product', 'Zulu Product']);
});

test('admin can create a product', function () {
    $category = Category::factory()->create();
    $brand = Brand::factory()->create();
    $unit = Unit::factory()->create();

    $this->post(route('products.store'), [
        'sku' => 'NEW-001',
        'barcode' => '1234567890123',
        'name' => 'New Product',
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'unit_id' => $unit->id,
        'cost_price' => '10.50',
        'selling_price' => '15.00',
        'tax_rate' => '10',
        'track_stock' => '1',
        'stock_quantity' => '25',
        'reorder_level' => '5',
        'is_active' => '1',
    ])->assertRedirect(route('products.index'));

    $product = Product::query()->where('sku', 'NEW-001')->sole();

    expect($product->name)->toBe('New Product')
        ->and((float) $product->stock_quantity)->toBe(25.0)
        ->and($product->category_id)->toBe($category->id)
        ->and($product->unit_id)->toBe($unit->id);
});

test('product sku and barcode must be unique', function () {
    $existing = Product::factory()->create();

    $this->post(route('products.store'), [
        'sku' => $existing->sku,
        'barcode' => $existing->barcode,
        'name' => 'Duplicate',
        'cost_price' => '1',
        'selling_price' => '2',
        'tax_rate' => '0',
    ])->assertSessionHasErrors(['sku', 'barcode']);
});

test('product name, sku and prices are required', function () {
    $this->post(route('products.store'), [])
        ->assertSessionHasErrors(['sku', 'name', 'cost_price', 'selling_price', 'tax_rate']);
});

test('untracked products zero out stock and reorder level', function () {
    $this->post(route('products.store'), [
        'sku' => 'UNTRACKED-1',
        'name' => 'Service Item',
        'cost_price' => '0',
        'selling_price' => '0',
        'tax_rate' => '0',
        'track_stock' => '0',
        'stock_quantity' => '12',
        'reorder_level' => '5',
    ])->assertRedirect(route('products.index'));

    $product = Product::query()->where('sku', 'UNTRACKED-1')->sole();

    expect($product->track_stock)->toBeFalse()
        ->and((float) $product->stock_quantity)->toBe(0.0)
        ->and($product->reorder_level)->toBeNull();
});

test('admin can create a product with an image', function () {
    Storage::fake('public');

    $this->post(route('products.store'), [
        'sku' => 'IMAGE-1',
        'name' => 'Photographed Product',
        'cost_price' => '1',
        'selling_price' => '2',
        'tax_rate' => '0',
        'image' => fakeProductImage(),
    ])->assertRedirect(route('products.index'));

    $product = Product::query()->where('sku', 'IMAGE-1')->sole();

    expect($product->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($product->image_path);
});

test('admin can update a product without touching the image', function () {
    Storage::fake('public');
    $product = Product::factory()->create(['name' => 'Before Update']);

    $this->put(route('products.update', $product), [
        'sku' => $product->sku,
        'barcode' => $product->barcode,
        'name' => 'After Update',
        'cost_price' => '5',
        'selling_price' => '9',
        'tax_rate' => '15',
        'track_stock' => '1',
        'stock_quantity' => '7',
    ])->assertRedirect(route('products.index'));

    expect($product->fresh()->name)->toBe('After Update')
        ->and($product->fresh()->image_path)->toBeNull();
});

test('updating a product replaces the old image', function () {
    Storage::fake('public');

    $product = Product::factory()->create([
        'sku' => 'REPLACE-1',
        'image_path' => fakeProductImage('old.png')->store('products', 'public'),
    ]);

    $oldPath = $product->image_path;

    $this->put(route('products.update', $product), [
        'sku' => $product->sku,
        'name' => 'Replaced Image',
        'cost_price' => '1',
        'selling_price' => '2',
        'tax_rate' => '0',
        'image' => fakeProductImage('new.png'),
    ])->assertRedirect(route('products.index'));

    $newPath = $product->fresh()->image_path;

    expect($newPath)->not->toBeNull()
        ->and($newPath)->not->toBe($oldPath);
    Storage::disk('public')->assertExists($newPath);
    Storage::disk('public')->assertMissing($oldPath);
});

test('admin can remove a product image', function () {
    Storage::fake('public');

    $product = Product::factory()->create([
        'sku' => 'REMOVE-1',
        'image_path' => fakeProductImage('remove.png')->store('products', 'public'),
    ]);

    $oldPath = $product->image_path;

    $this->put(route('products.update', $product), [
        'sku' => $product->sku,
        'name' => 'No Image',
        'cost_price' => '1',
        'selling_price' => '2',
        'tax_rate' => '0',
        'remove_image' => '1',
    ])->assertRedirect(route('products.index'));

    expect($product->fresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($oldPath);
});

test('admin can soft delete a product', function () {
    $product = Product::factory()->create(['name' => 'Doomed Product']);

    $this->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'));

    $this->assertSoftDeleted($product);

    $this->get(route('products.index'))->assertDontSee('Doomed Product');
});
