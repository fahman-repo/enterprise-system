<?php

use App\Models\ApprovalRequest;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\PermissionService;

/**
 * Maker and approver wired to an active categories approval matrix.
 *
 * @return array{maker: User, approver: User}
 */
function categoriesApprovalActors(): array
{
    $moduleMenu = Menu::factory()->create(['slug' => 'categories']);
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

    app(ApprovalWorkflowService::class)->replaceConfiguration('categories', [
        'is_active' => true,
        'maker_roles' => [$makerRole->id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$approverRole->id]],
        ],
    ], $maker);

    return ['maker' => $maker, 'approver' => $approver];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function categoriesApprovalUpdatePayload(Category $category, array $overrides = []): array
{
    return array_merge([
        'name' => $category->name,
        'slug' => $category->slug,
        'parent_id' => $category->parent_id,
        'is_active' => $category->is_active ? '1' : '0',
    ], $overrides);
}

function categoriesApprovalPendingRequest(string $action, ?Category $category = null): ApprovalRequest
{
    $query = ApprovalRequest::query()
        ->where('module_key', 'categories')
        ->where('action', $action)
        ->where('status', ApprovalRequest::STATUS_PENDING);

    if ($category !== null) {
        $query->where('target_id', $category->id);
    }

    return $query->sole();
}

test('approving a category delete reparents its children to the grandparent', function () {
    $actors = categoriesApprovalActors();
    $this->actingAs($actors['maker']);

    $grandparent = Category::factory()->create(['name' => 'Grandparent Category']);
    $parent = Category::factory()->create(['name' => 'Parent Category', 'parent_id' => $grandparent->id]);
    $child = Category::factory()->create(['name' => 'Child Category', 'parent_id' => $parent->id]);

    $this->delete(route('categories.destroy', $parent))
        ->assertRedirect(route('categories.index'));

    $this->assertNotSoftDeleted($parent);
    $request = categoriesApprovalPendingRequest(ApprovalRequest::ACTION_DELETE, $parent);

    $this->actingAs($actors['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    $this->assertSoftDeleted($parent);
    expect($child->fresh()->parent_id)->toBe($grandparent->id)
        ->and($request->fresh()->status)->toBe(ApprovalRequest::STATUS_APPROVED);
});

test('a category cannot be submitted under its own descendant', function () {
    $actors = categoriesApprovalActors();
    $this->actingAs($actors['maker']);

    $parent = Category::factory()->create(['name' => 'Move Source']);
    $child = Category::factory()->create(['name' => 'Move Target', 'parent_id' => $parent->id]);

    $this->put(route('categories.update', $parent), categoriesApprovalUpdatePayload($parent, [
        'parent_id' => $child->id,
    ]))->assertSessionHasErrors('parent_id');

    $this->assertDatabaseCount('approval_requests', 0);
    expect($parent->fresh()->parent_id)->toBeNull();
});

test('a descendant moved under the category after submission blocks the approval', function () {
    $actors = categoriesApprovalActors();
    $this->actingAs($actors['maker']);

    $target = Category::factory()->create(['name' => 'Cycle Source']);
    $candidate = Category::factory()->create(['name' => 'Later Descendant']);

    $this->put(route('categories.update', $target), categoriesApprovalUpdatePayload($target, [
        'parent_id' => $candidate->id,
    ]))->assertRedirect(route('categories.index'));

    $request = categoriesApprovalPendingRequest(ApprovalRequest::ACTION_UPDATE, $target);

    $candidate->update(['parent_id' => $target->id]);

    $this->actingAs($actors['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertSessionHasErrors('parent_id');

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_PENDING)
        ->and($target->fresh()->parent_id)->toBeNull()
        ->and($candidate->fresh()->parent_id)->toBe($target->id);
});

test('an approved category create applies the submitted parent, name and slug', function () {
    $actors = categoriesApprovalActors();
    $this->actingAs($actors['maker']);

    $parent = Category::factory()->create(['name' => 'Create Parent']);

    $this->post(route('categories.store'), [
        'name' => 'Approved Child',
        'slug' => 'approved-child',
        'parent_id' => $parent->id,
        'is_active' => '1',
    ])->assertRedirect(route('categories.index'));

    $this->assertDatabaseMissing('categories', ['slug' => 'approved-child']);

    $request = categoriesApprovalPendingRequest(ApprovalRequest::ACTION_CREATE);

    $this->actingAs($actors['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    $created = Category::query()->where('slug', 'approved-child')->sole();

    expect($created->name)->toBe('Approved Child')
        ->and($created->parent_id)->toBe($parent->id)
        ->and($created->is_active)->toBeTrue();
});
