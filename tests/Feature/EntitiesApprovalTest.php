<?php

use App\Models\ApprovalRequest;
use App\Models\Entity;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\PermissionService;

/**
 * Maker and approver wired to an active entities approval matrix.
 *
 * @return array{maker: User, approver: User}
 */
function entitiesApprovalActors(): array
{
    $moduleMenu = Menu::factory()->create(['slug' => 'entities']);
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

    app(ApprovalWorkflowService::class)->replaceConfiguration('entities', [
        'is_active' => true,
        'maker_roles' => [$makerRole->id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$approverRole->id]],
        ],
    ], $maker);

    return ['maker' => $maker, 'approver' => $approver];
}

function entitiesApprovalPendingRequest(string $action, ?Entity $entity = null): ApprovalRequest
{
    $query = ApprovalRequest::query()
        ->where('module_key', 'entities')
        ->where('action', $action)
        ->where('status', ApprovalRequest::STATUS_PENDING);

    if ($entity !== null) {
        $query->where('target_id', $entity->id);
    }

    return $query->sole();
}

test('a blank entity code is generated when an entity create is approved', function () {
    $actors = entitiesApprovalActors();
    $this->actingAs($actors['maker']);

    $this->post(route('entities.store'), [
        'name' => 'Approval Vendor',
        'type' => 'company',
        'role' => 'vendor',
    ])->assertRedirect(route('entities.index'));

    $this->assertDatabaseMissing('entities', ['name' => 'Approval Vendor']);

    $request = entitiesApprovalPendingRequest(ApprovalRequest::ACTION_CREATE);

    expect($request->proposed_payload)->not->toHaveKey('code');

    $this->actingAs($actors['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    $entity = Entity::query()->where('name', 'Approval Vendor')->sole();

    expect($entity->code)->toStartWith(config('entities.entity_code_prefix').'-')
        ->and($request->fresh()->status)->toBe(ApprovalRequest::STATUS_APPROVED);
});

test('an approved entity create applies the submitted fields', function () {
    $actors = entitiesApprovalActors();
    $this->actingAs($actors['maker']);

    $this->post(route('entities.store'), [
        'code' => 'ENT-APPROVAL-2',
        'name' => 'Approved Personal Entity',
        'type' => 'personal',
        'role' => 'customer',
        'identity_number' => '3201234567890123',
        'email' => 'approved.entity@example.test',
    ])->assertRedirect(route('entities.index'));

    $this->assertDatabaseMissing('entities', ['code' => 'ENT-APPROVAL-2']);

    $request = entitiesApprovalPendingRequest(ApprovalRequest::ACTION_CREATE);

    $this->actingAs($actors['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    $entity = Entity::query()->where('code', 'ENT-APPROVAL-2')->sole();

    expect($entity->name)->toBe('Approved Personal Entity')
        ->and($entity->type)->toBe('personal')
        ->and($entity->role)->toBe('customer')
        ->and($entity->identity_number)->toBe('3201234567890123')
        ->and($entity->email)->toBe('approved.entity@example.test');
});

test('an approved entity delete soft-deletes the entity', function () {
    $actors = entitiesApprovalActors();
    $this->actingAs($actors['maker']);

    $entity = Entity::factory()->create(['name' => 'Doomed Entity']);

    $this->delete(route('entities.destroy', $entity))
        ->assertRedirect(route('entities.index'));

    $this->assertNotSoftDeleted('entities', ['id' => $entity->id]);
    $request = entitiesApprovalPendingRequest(ApprovalRequest::ACTION_DELETE, $entity);

    $this->actingAs($actors['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    $this->assertSoftDeleted('entities', ['id' => $entity->id]);
    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_APPROVED);
});

test('an entity type outside the allowed set is rejected at submission', function () {
    $actors = entitiesApprovalActors();
    $this->actingAs($actors['maker']);

    $this->post(route('entities.store'), [
        'name' => 'Invalid Type Entity',
        'type' => 'NGO',
        'role' => 'vendor',
    ])->assertSessionHasErrors('type');

    $this->assertDatabaseCount('approval_requests', 0);
    $this->assertDatabaseMissing('entities', ['name' => 'Invalid Type Entity']);
});
