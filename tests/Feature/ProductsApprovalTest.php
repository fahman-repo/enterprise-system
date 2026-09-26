<?php

use App\Models\ApprovalRequest;
use App\Models\Menu;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\PermissionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Maker and approver wired to an active products approval matrix.
 *
 * @return array{maker: User, approver: User}
 */
function productsApprovalActors(): array
{
    $moduleMenu = Menu::factory()->create(['slug' => 'products']);
    $approvalsMenu = Menu::factory()->create(['slug' => 'approvals']);

    $makerRole = Role::factory()->create();
    $makerRole->menus()->sync([
        $moduleMenu->id => ['can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true],
        $approvalsMenu->id => ['can_view' => true, 'can_delete' => true],
    ]);

    $approverRole = Role::factory()->create();
    $approverRole->menus()->sync([
        $approvalsMenu->id => ['can_view' => true, 'can_update' => true],
    ]);

    $maker = User::factory()->create(['role_id' => $makerRole->id]);
    $approver = User::factory()->create(['role_id' => $approverRole->id]);

    app(PermissionService::class)->flush();

    app(ApprovalWorkflowService::class)->replaceConfiguration('products', [
        'is_active' => true,
        'maker_roles' => [$makerRole->id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$approverRole->id]],
        ],
    ], $maker);

    return ['maker' => $maker, 'approver' => $approver];
}

function productsApprovalImage(string $name = 'approval-widget.png'): UploadedFile
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    return UploadedFile::fake()->createWithContent($name, $png);
}

/**
 * Update payload carrying every field the controller validates.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function productsApprovalUpdatePayload(Product $product, array $overrides = []): array
{
    return array_merge([
        'sku' => $product->sku,
        'barcode' => $product->barcode,
        'name' => $product->name,
        'cost_price' => '1',
        'selling_price' => '2',
        'tax_rate' => '0',
        'is_active' => '1',
    ], $overrides);
}

function productsApprovalPendingRequest(string $action, ?Product $product = null): ApprovalRequest
{
    $query = ApprovalRequest::query()
        ->where('module_key', 'products')
        ->where('action', $action)
        ->where('status', ApprovalRequest::STATUS_PENDING);

    if ($product !== null) {
        $query->where('target_id', $product->id);
    }

    return $query->sole();
}

test('a product create with an image submits a pending request and stores the image', function () {
    Storage::fake('public');
    $actors = productsApprovalActors();
    $this->actingAs($actors['maker']);

    $this->post(route('products.store'), [
        'sku' => 'APPROVAL-IMG-1',
        'name' => 'Approval Widget',
        'cost_price' => '1',
        'selling_price' => '2',
        'tax_rate' => '0',
        'image' => productsApprovalImage(),
    ])->assertRedirect(route('products.index'));

    $this->assertDatabaseMissing('products', ['sku' => 'APPROVAL-IMG-1']);

    $request = productsApprovalPendingRequest(ApprovalRequest::ACTION_CREATE);

    expect($request->proposed_payload['image_path'])->toBeString()->not->toBe('');
    Storage::disk('public')->assertExists($request->proposed_payload['image_path']);
});

test('approving a product create applies the submitted data and stored image', function () {
    Storage::fake('public');
    $actors = productsApprovalActors();
    $this->actingAs($actors['maker']);

    $this->post(route('products.store'), [
        'sku' => 'APPROVAL-IMG-2',
        'name' => 'Approved Widget',
        'cost_price' => '3',
        'selling_price' => '4',
        'tax_rate' => '5',
        'image' => productsApprovalImage('approved-widget.png'),
    ])->assertRedirect(route('products.index'));

    $request = productsApprovalPendingRequest(ApprovalRequest::ACTION_CREATE);
    $storedPath = $request->proposed_payload['image_path'];

    $this->actingAs($actors['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    $product = Product::query()->where('sku', 'APPROVAL-IMG-2')->sole();

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_APPROVED)
        ->and($product->name)->toBe('Approved Widget')
        ->and($product->image_path)->toBe($storedPath);
    Storage::disk('public')->assertExists($storedPath);
});

test('a product update with a new image keeps both files until approval then replaces the old one', function () {
    Storage::fake('public');
    $actors = productsApprovalActors();
    $this->actingAs($actors['maker']);

    $oldPath = productsApprovalImage('old-widget.png')->store('products', 'public');
    $product = Product::factory()->create([
        'sku' => 'APPROVAL-REPLACE-1',
        'name' => 'Original Widget',
        'image_path' => $oldPath,
    ]);

    $this->put(route('products.update', $product), productsApprovalUpdatePayload($product, [
        'name' => 'Updated Widget',
        'image' => productsApprovalImage('replacement-widget.png'),
    ]))->assertRedirect(route('products.index'));

    expect($product->fresh()->name)->toBe('Original Widget')
        ->and($product->fresh()->image_path)->toBe($oldPath);
    Storage::disk('public')->assertExists($oldPath);

    $request = productsApprovalPendingRequest(ApprovalRequest::ACTION_UPDATE, $product);
    $newPath = $request->proposed_payload['image_path'];

    expect($newPath)->not->toBe($oldPath);
    Storage::disk('public')->assertExists($newPath);

    $this->actingAs($actors['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    expect($product->fresh()->name)->toBe('Updated Widget')
        ->and($product->fresh()->image_path)->toBe($newPath);
    Storage::disk('public')->assertExists($newPath);
    Storage::disk('public')->assertMissing($oldPath);
});

test('removing a product image applies on approval and deletes the stored file', function () {
    Storage::fake('public');
    $actors = productsApprovalActors();
    $this->actingAs($actors['maker']);

    $oldPath = productsApprovalImage('removable-widget.png')->store('products', 'public');
    $product = Product::factory()->create([
        'sku' => 'APPROVAL-REMOVE-1',
        'name' => 'Removable Image Widget',
        'image_path' => $oldPath,
    ]);

    $this->put(route('products.update', $product), productsApprovalUpdatePayload($product, [
        'remove_image' => '1',
    ]))->assertRedirect(route('products.index'));

    $request = productsApprovalPendingRequest(ApprovalRequest::ACTION_UPDATE, $product);

    expect($request->proposed_payload['image_path'])->toBeNull()
        ->and($product->fresh()->image_path)->toBe($oldPath);
    Storage::disk('public')->assertExists($oldPath);

    $this->actingAs($actors['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    expect($product->fresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($oldPath);
});

test('rejecting a product update deletes the newly stored image and leaves the product untouched', function () {
    Storage::fake('public');
    $actors = productsApprovalActors();
    $this->actingAs($actors['maker']);

    $oldPath = productsApprovalImage('kept-widget.png')->store('products', 'public');
    $product = Product::factory()->create([
        'sku' => 'APPROVAL-REJECT-1',
        'name' => 'Rejected Widget',
        'image_path' => $oldPath,
    ]);

    $this->put(route('products.update', $product), productsApprovalUpdatePayload($product, [
        'name' => 'Never Applied Widget',
        'image' => productsApprovalImage('orphan-widget.png'),
    ]))->assertRedirect(route('products.index'));

    $request = productsApprovalPendingRequest(ApprovalRequest::ACTION_UPDATE, $product);
    $orphanPath = $request->proposed_payload['image_path'];

    expect($orphanPath)->not->toBe($oldPath);
    Storage::disk('public')->assertExists($orphanPath);

    $this->actingAs($actors['approver'])
        ->post(route('approvals.reject', $request), ['comment' => 'not this time'])
        ->assertRedirect(route('approvals.show', $request));

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_REJECTED)
        ->and($product->fresh()->name)->toBe('Rejected Widget')
        ->and($product->fresh()->image_path)->toBe($oldPath);
    Storage::disk('public')->assertMissing($orphanPath);
    Storage::disk('public')->assertExists($oldPath);
});

test('a product update without an upload carries the current image path', function () {
    Storage::fake('public');
    $actors = productsApprovalActors();
    $this->actingAs($actors['maker']);

    $oldPath = productsApprovalImage('unchanged-widget.png')->store('products', 'public');
    $product = Product::factory()->create([
        'sku' => 'APPROVAL-NO-UPLOAD-1',
        'name' => 'Unchanged Image Widget',
        'image_path' => $oldPath,
    ]);

    $this->put(route('products.update', $product), productsApprovalUpdatePayload($product, [
        'name' => 'Renamed Widget',
    ]))->assertRedirect(route('products.index'));

    $request = productsApprovalPendingRequest(ApprovalRequest::ACTION_UPDATE, $product);

    expect($request->proposed_payload['image_path'])->toBe($oldPath)
        ->and($product->fresh()->name)->toBe('Unchanged Image Widget');
    Storage::disk('public')->assertExists($oldPath);
});
