<?php

use App\Models\ApprovalRequest;
use App\Models\Menu;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\PermissionService;

/**
 * Maker and approver wired to an active sites approval matrix.
 *
 * @return array{maker: User, approver: User}
 */
function sitesApprovalActors(): array
{
    $moduleMenu = Menu::factory()->create(['slug' => 'sites']);
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

    app(ApprovalWorkflowService::class)->replaceConfiguration('sites', [
        'is_active' => true,
        'maker_roles' => [$makerRole->id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$approverRole->id]],
        ],
    ], $maker);

    return ['maker' => $maker, 'approver' => $approver];
}

function sitesApprovalPendingRequest(string $action, ?Site $site = null): ApprovalRequest
{
    $query = ApprovalRequest::query()
        ->where('module_key', 'sites')
        ->where('action', $action)
        ->where('status', ApprovalRequest::STATUS_PENDING);

    if ($site !== null) {
        $query->where('target_id', $site->id);
    }

    return $query->sole();
}

test('a blank site code is generated when a site create is approved', function () {
    $actors = sitesApprovalActors();
    $this->actingAs($actors['maker']);

    $this->post(route('sites.store'), [
        'name' => 'Approval Warehouse',
        'type' => 'warehouse',
    ])->assertRedirect(route('sites.index'));

    $this->assertDatabaseMissing('sites', ['name' => 'Approval Warehouse']);

    $request = sitesApprovalPendingRequest(ApprovalRequest::ACTION_CREATE);

    expect($request->proposed_payload)->not->toHaveKey('code');

    $this->actingAs($actors['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    $site = Site::query()->where('name', 'Approval Warehouse')->sole();

    expect($site->code)->toStartWith(config('sites.site_code_prefix').'-')
        ->and($request->fresh()->status)->toBe(ApprovalRequest::STATUS_APPROVED);
});

test('an approved site create applies the submitted fields and parent', function () {
    $actors = sitesApprovalActors();
    $this->actingAs($actors['maker']);

    $company = Site::factory()->company()->create();

    $this->post(route('sites.store'), [
        'code' => 'SIT-APPROVAL-2',
        'name' => 'Approved Building',
        'type' => 'building',
        'parent_id' => $company->id,
        'city' => 'Jakarta',
    ])->assertRedirect(route('sites.index'));

    $this->assertDatabaseMissing('sites', ['code' => 'SIT-APPROVAL-2']);

    $request = sitesApprovalPendingRequest(ApprovalRequest::ACTION_CREATE);

    $this->actingAs($actors['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    $site = Site::query()->where('code', 'SIT-APPROVAL-2')->sole();

    expect($site->type)->toBe('building')
        ->and($site->parent_id)->toBe($company->id)
        ->and($site->city)->toBe('Jakarta');
});

test('an approved site delete soft-deletes the site', function () {
    $actors = sitesApprovalActors();
    $this->actingAs($actors['maker']);

    $site = Site::factory()->create(['name' => 'Doomed Site']);

    $this->delete(route('sites.destroy', $site))
        ->assertRedirect(route('sites.index'));

    $this->assertNotSoftDeleted('sites', ['id' => $site->id]);

    $request = sitesApprovalPendingRequest(ApprovalRequest::ACTION_DELETE, $site);

    $this->actingAs($actors['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    $this->assertSoftDeleted('sites', ['id' => $site->id]);
    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_APPROVED);
});

test('a site type outside the allowed set is rejected at submission', function () {
    $actors = sitesApprovalActors();
    $this->actingAs($actors['maker']);

    $this->post(route('sites.store'), [
        'name' => 'Invalid Type Site',
        'type' => 'island',
    ])->assertSessionHasErrors('type');

    $this->assertDatabaseCount('approval_requests', 0);
    $this->assertDatabaseMissing('sites', ['name' => 'Invalid Type Site']);
});

test('a site cannot be submitted under a parent category that cannot hold it', function () {
    $actors = sitesApprovalActors();
    $this->actingAs($actors['maker']);

    $branch = Site::factory()->branch()->create();

    $this->post(route('sites.store'), [
        'name' => 'Building Under A Branch',
        'type' => 'building',
        'parent_id' => $branch->id,
    ])->assertSessionHasErrors('parent_id');

    $this->assertDatabaseCount('approval_requests', 0);
    $this->assertDatabaseCount('sites', 1);
});

test('a site delete is refused at apply time when a child site appeared', function () {
    $actors = sitesApprovalActors();
    $this->actingAs($actors['maker']);

    $branch = Site::factory()->branch()->create();

    $this->delete(route('sites.destroy', $branch))
        ->assertRedirect(route('sites.index'));

    $request = sitesApprovalPendingRequest(ApprovalRequest::ACTION_DELETE, $branch);

    Site::factory()->workshop()->under($branch)->create();

    $this->actingAs($actors['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertSessionHasErrors('approval');

    $this->assertNotSoftDeleted('sites', ['id' => $branch->id]);
    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_PENDING);
});
